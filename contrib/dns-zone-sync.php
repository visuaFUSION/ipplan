#!/usr/bin/env php
<?php
/**
 * IPplan DNS Zone Sync
 *
 * This CLI script periodically synchronizes DNS zones from configured DNS servers
 * using AXFR (zone transfer) and updates IPplan's database.
 *
 * Usage:
 *   php dns-zone-sync.php [options]
 *
 * Options:
 *   -h, --help     Show this help message
 *   -v, --verbose  Enable verbose output
 *   -q, --quiet    Suppress all output except errors
 *   -d, --dry-run  Show what would be done without making changes
 *   -f, --force    Force sync even if not due yet
 *   --config=PATH  Path to config file (default: ../data/dns-sync-config.json)
 *
 * The script reads configuration from data/dns-sync-config.json which is
 * managed by the IPplan web interface.
 *
 * Install as scheduled task:
 *   Windows: Use contrib/dns-sync-setup.ps1 (run as Administrator)
 *   Linux:   Use contrib/dns-sync-setup.sh (run as root)
 */

// Prevent web execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Change to script directory for relative paths
chdir(dirname(__FILE__));

// Include required files
require_once("../adodb/adodb.inc.php");
require_once("../config.php");
require_once("../class.dnslib.php");
require_once("../ipplanlib.php");

// Parse command line arguments
$options = getopt('hvqdf', ['help', 'verbose', 'quiet', 'dry-run', 'force', 'config:']);

if (isset($options['h']) || isset($options['help'])) {
    showHelp();
    exit(0);
}

$verbose = isset($options['v']) || isset($options['verbose']);
$quiet = isset($options['q']) || isset($options['quiet']);
$dryRun = isset($options['d']) || isset($options['dry-run']);
$force = isset($options['f']) || isset($options['force']);
$configPath = isset($options['config']) ? $options['config'] : '../data/dns-sync-config.json';

// Resolve relative path
if ($configPath[0] !== '/') {
    $configPath = dirname(__FILE__) . '/' . $configPath;
}

// Main execution
try {
    $sync = new DNSZoneSync($configPath, $verbose, $quiet, $dryRun, $force);
    $exitCode = $sync->run();
    exit($exitCode);
} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * DNS Zone Synchronization Class
 */
class DNSZoneSync {
    private $config;
    private $configPath;
    private $verbose;
    private $quiet;
    private $dryRun;
    private $force;
    private $ds;
    private $stats = [
        'zones_checked' => 0,
        'zones_synced' => 0,
        'zones_skipped' => 0,
        'zones_failed' => 0,
        'records_added' => 0,
        'records_updated' => 0
    ];

    public function __construct($configPath, $verbose = false, $quiet = false, $dryRun = false, $force = false) {
        $this->configPath = $configPath;
        $this->verbose = $verbose;
        $this->quiet = $quiet;
        $this->dryRun = $dryRun;
        $this->force = $force;
    }

    /**
     * Main execution method
     */
    public function run() {
        $this->log("IPplan DNS Zone Sync started at " . date('Y-m-d H:i:s'));

        // Load configuration
        if (!$this->loadConfig()) {
            return 1;
        }

        // Check if sync is enabled
        if (!isset($this->config['enabled']) || !$this->config['enabled']) {
            $this->log("DNS sync is disabled in configuration. Exiting.");
            return 0;
        }

        // Connect to database
        if (!$this->connectDatabase()) {
            return 1;
        }

        // Process each zone
        $zones = isset($this->config['zones']) ? $this->config['zones'] : [];

        if (empty($zones)) {
            $this->log("No zones configured for synchronization.");
            $this->closeDatabase();
            return 0;
        }

        foreach ($zones as $zoneConfig) {
            $this->processZone($zoneConfig);
        }

        // Save updated config with last sync times
        $this->saveConfig();

        // Close database
        $this->closeDatabase();

        // Print summary
        $this->printSummary();

        return $this->stats['zones_failed'] > 0 ? 1 : 0;
    }

    /**
     * Load configuration from JSON file
     */
    private function loadConfig() {
        if (!file_exists($this->configPath)) {
            // Create default config if it doesn't exist
            $this->config = [
                'enabled' => false,
                'sync_interval_minutes' => 60,
                'zones' => [],
                'last_run' => null
            ];
            $this->log("Config file not found. Creating default config at: " . $this->configPath);
            $this->saveConfig();
            return true;
        }

        $json = file_get_contents($this->configPath);
        if ($json === false) {
            $this->error("Failed to read config file: " . $this->configPath);
            return false;
        }

        $this->config = json_decode($json, true);
        if ($this->config === null) {
            $this->error("Failed to parse config file: " . json_last_error_msg());
            return false;
        }

        $this->verbose("Loaded configuration from: " . $this->configPath);
        return true;
    }

    /**
     * Save configuration back to JSON file
     */
    private function saveConfig() {
        if ($this->dryRun) {
            $this->verbose("Dry run: Would save config to " . $this->configPath);
            return true;
        }

        $this->config['last_run'] = date('Y-m-d H:i:s');

        $json = json_encode($this->config, JSON_PRETTY_PRINT);
        if (file_put_contents($this->configPath, $json) === false) {
            $this->error("Failed to save config file: " . $this->configPath);
            return false;
        }

        return true;
    }

    /**
     * Connect to IPplan database
     */
    private function connectDatabase() {
        $this->ds = ADONewConnection(DBF_TYPE);
        $this->ds->debug = DBF_DEBUG;

        if (!$this->ds->Connect(DBF_HOST, DBF_USER, DBF_PASSWORD, DBF_NAME)) {
            $this->error("Failed to connect to database");
            return false;
        }

        $this->ds->SetFetchMode(ADODB_FETCH_ASSOC);
        $this->verbose("Connected to database: " . DBF_NAME . "@" . DBF_HOST);
        return true;
    }

    /**
     * Close database connection
     */
    private function closeDatabase() {
        if ($this->ds) {
            $this->ds->Close();
        }
    }

    /**
     * Process a single zone for synchronization
     */
    private function processZone($zoneConfig) {
        $this->stats['zones_checked']++;

        // Validate zone config
        if (!isset($zoneConfig['enabled']) || !$zoneConfig['enabled']) {
            $this->verbose("Zone {$zoneConfig['domain']} is disabled, skipping.");
            $this->stats['zones_skipped']++;
            return;
        }

        if (!isset($zoneConfig['domain']) || !isset($zoneConfig['server'])) {
            $this->error("Invalid zone configuration - missing domain or server");
            $this->stats['zones_failed']++;
            return;
        }

        $domain = $zoneConfig['domain'];
        $server = $zoneConfig['server'];
        $customer = isset($zoneConfig['customer']) ? intval($zoneConfig['customer']) : 0;
        $zoneType = isset($zoneConfig['type']) ? $zoneConfig['type'] : 'forward';
        $syncInterval = isset($zoneConfig['sync_interval_minutes'])
            ? intval($zoneConfig['sync_interval_minutes'])
            : (isset($this->config['sync_interval_minutes']) ? intval($this->config['sync_interval_minutes']) : 60);

        // Check if sync is due
        if (!$this->force && isset($zoneConfig['last_sync'])) {
            $lastSync = strtotime($zoneConfig['last_sync']);
            $nextSync = $lastSync + ($syncInterval * 60);
            if (time() < $nextSync) {
                $this->verbose("Zone $domain not due for sync until " . date('Y-m-d H:i:s', $nextSync));
                $this->stats['zones_skipped']++;
                return;
            }
        }

        $this->log("Syncing zone: $domain from server: $server");

        if ($this->dryRun) {
            $this->log("  Dry run: Would perform AXFR for $domain from $server");
            $this->stats['zones_synced']++;
            return;
        }

        try {
            if ($zoneType === 'reverse') {
                $result = $this->syncReverseZone($domain, $server, $customer, $zoneConfig);
            } else {
                $result = $this->syncForwardZone($domain, $server, $customer, $zoneConfig);
            }

            if ($result) {
                $this->stats['zones_synced']++;
                // Update last_sync time in zone config
                foreach ($this->config['zones'] as &$z) {
                    if ($z['domain'] === $domain && $z['server'] === $server) {
                        $z['last_sync'] = date('Y-m-d H:i:s');
                        $z['last_sync_status'] = 'success';
                        break;
                    }
                }
            } else {
                $this->stats['zones_failed']++;
                foreach ($this->config['zones'] as &$z) {
                    if ($z['domain'] === $domain && $z['server'] === $server) {
                        $z['last_sync_status'] = 'failed';
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $this->error("Exception syncing zone $domain: " . $e->getMessage());
            $this->stats['zones_failed']++;
        }
    }

    /**
     * Sync a forward DNS zone
     */
    private function syncForwardZone($domain, $server, $customer, $zoneConfig) {
        // Check if zone exists in database
        $result = $this->ds->Execute("SELECT data_id, slaveonly
            FROM fwdzone
            WHERE customer = ? AND domain = ?",
            [$customer, $domain]);

        if (!$result) {
            $this->error("Database error checking for zone $domain");
            return false;
        }

        $row = $result->FetchRow();
        if (!$row) {
            $this->error("Zone $domain not found in database for customer $customer");
            return false;
        }

        $dataId = $row['data_id'];
        $slaveOnly = ($row['slaveonly'] === 'Y');

        // Create DNS zone object
        $zone = new DNSfwdZone();
        $zone->ds = $this->ds;
        $zone->domain = $domain;
        $zone->cust = $customer;

        // Perform zone transfer
        $this->verbose("  Performing AXFR for $domain from $server");
        $answer = $zone->ZoneAXFR($domain, $server);

        if ($zone->err > 0) {
            $this->error("  Zone transfer failed: " . $zone->errstr);
            return false;
        }

        if (empty($answer)) {
            $this->log("  Zone transfer returned no records (using defaults)");
            if ($zone->err < 0) {
                $this->verbose("  Warning: " . $zone->errstr);
            }
        }

        // Update SOA info
        $this->verbose("  Updating SOA information");

        // Get current serial
        $serialResult = $this->ds->Execute("SELECT serialdate, serialnum
            FROM fwdzone WHERE data_id = ?", [$dataId]);
        if ($serialRow = $serialResult->FetchRow()) {
            $zone->SetSerial($serialRow['serialdate'], $serialRow['serialnum']);
        }

        $zone->Serial();

        // Update the zone SOA
        $updateResult = $this->ds->Execute("UPDATE fwdzone SET
            serialdate = ?,
            serialnum = ?,
            ttl = ?,
            refresh = ?,
            retry = ?,
            expire = ?,
            minimum = ?,
            responsiblemail = ?,
            lastmod = ?,
            userid = ?
            WHERE data_id = ?",
            [
                $zone->serialdate,
                $zone->serialnum,
                $zone->ttl,
                $zone->refresh,
                $zone->retry,
                $zone->expire,
                $zone->minimum,
                $zone->responsiblemail,
                $this->ds->DBTimeStamp(time()),
                'DNS-SYNC',
                $dataId
            ]);

        if (!$updateResult) {
            $this->error("  Failed to update zone SOA");
            return false;
        }

        // If not slave-only and we have records, update zone records
        if (!$slaveOnly && !empty($answer)) {
            $this->verbose("  Updating zone records");

            // Delete existing records
            $this->ds->Execute("DELETE FROM fwdzonerec WHERE data_id = ?", [$dataId]);

            // Add new records from AXFR
            if ($zone->FwdZoneAddRR($dataId, $answer)) {
                $this->log("  Zone records updated successfully");
            } else {
                $this->error("  Failed to add zone records");
            }
        } elseif ($slaveOnly) {
            $this->verbose("  Slave-only zone - skipping record updates");
        }

        $this->log("  Zone $domain synced successfully");
        return true;
    }

    /**
     * Sync a reverse DNS zone
     */
    private function syncReverseZone($zone, $server, $customer, $zoneConfig) {
        // Check if zone exists in database
        $result = $this->ds->Execute("SELECT id, slaveonly, zoneip, zonesize
            FROM zones
            WHERE customer = ? AND zone = ?",
            [$customer, $zone]);

        if (!$result) {
            $this->error("Database error checking for reverse zone $zone");
            return false;
        }

        $row = $result->FetchRow();
        if (!$row) {
            $this->error("Reverse zone $zone not found in database for customer $customer");
            return false;
        }

        $zoneId = $row['id'];
        $slaveOnly = ($row['slaveonly'] === 'Y');

        // Create DNS zone object
        $zoneObj = new DNSrevZone();
        $zoneObj->ds = $this->ds;
        $zoneObj->zone = $zone;
        $zoneObj->cust = $customer;
        $zoneObj->zoneip = $row['zoneip'];
        $zoneObj->size = $row['zonesize'];

        // Perform zone transfer
        $this->verbose("  Performing AXFR for reverse zone $zone from $server");
        $answer = $zoneObj->ZoneAXFR($zone, $server);

        if ($zoneObj->err > 0) {
            $this->error("  Zone transfer failed: " . $zoneObj->errstr);
            return false;
        }

        if (empty($answer)) {
            $this->log("  Zone transfer returned no records (using defaults)");
        }

        // Update SOA info
        $this->verbose("  Updating SOA information");

        // Get current serial
        $serialResult = $this->ds->Execute("SELECT serialdate, serialnum
            FROM zones WHERE id = ?", [$zoneId]);
        if ($serialRow = $serialResult->FetchRow()) {
            $zoneObj->SetSerial($serialRow['serialdate'], $serialRow['serialnum']);
        }

        $zoneObj->Serial();

        // Update the zone SOA
        $updateResult = $this->ds->Execute("UPDATE zones SET
            serialdate = ?,
            serialnum = ?,
            ttl = ?,
            refresh = ?,
            retry = ?,
            expire = ?,
            minimum = ?,
            responsiblemail = ?,
            lastmod = ?,
            userid = ?
            WHERE id = ?",
            [
                $zoneObj->serialdate,
                $zoneObj->serialnum,
                $zoneObj->ttl,
                $zoneObj->refresh,
                $zoneObj->retry,
                $zoneObj->expire,
                $zoneObj->minimum,
                $zoneObj->responsiblemail,
                $this->ds->DBTimeStamp(time()),
                'DNS-SYNC',
                $zoneId
            ]);

        if (!$updateResult) {
            $this->error("  Failed to update reverse zone SOA");
            return false;
        }

        // If not slave-only and we have records, update PTR records
        if (!$slaveOnly && !empty($answer)) {
            $this->verbose("  Updating PTR records in IP address table");
            // Use the existing RevZoneAddRR method which updates ipaddr table
            if ($zoneObj->RevZoneAddRR($zoneId, $answer)) {
                $this->log("  PTR records updated successfully");
            }
            if (!empty($zoneObj->errstr)) {
                $this->verbose("  Warnings: " . $zoneObj->errstr);
            }
        } elseif ($slaveOnly) {
            $this->verbose("  Slave-only zone - skipping PTR record updates");
        }

        $this->log("  Reverse zone $zone synced successfully");
        return true;
    }

    /**
     * Print summary of sync operation
     */
    private function printSummary() {
        if ($this->quiet) {
            return;
        }

        $this->log("");
        $this->log("=== DNS Zone Sync Summary ===");
        $this->log("Zones checked:  " . $this->stats['zones_checked']);
        $this->log("Zones synced:   " . $this->stats['zones_synced']);
        $this->log("Zones skipped:  " . $this->stats['zones_skipped']);
        $this->log("Zones failed:   " . $this->stats['zones_failed']);
        $this->log("Completed at:   " . date('Y-m-d H:i:s'));
        $this->log("");
    }

    /**
     * Log a message (unless quiet mode)
     */
    private function log($message) {
        if (!$this->quiet) {
            echo $message . "\n";
        }
    }

    /**
     * Log a verbose message
     */
    private function verbose($message) {
        if ($this->verbose && !$this->quiet) {
            echo "  [verbose] " . $message . "\n";
        }
    }

    /**
     * Log an error message
     */
    private function error($message) {
        fwrite(STDERR, "ERROR: " . $message . "\n");
    }
}

/**
 * Show help message
 */
function showHelp() {
    echo <<<HELP
IPplan DNS Zone Sync

Synchronizes DNS zones from configured DNS servers using AXFR (zone transfer)
and updates IPplan's database.

Usage:
  php dns-zone-sync.php [options]

Options:
  -h, --help      Show this help message
  -v, --verbose   Enable verbose output
  -q, --quiet     Suppress all output except errors
  -d, --dry-run   Show what would be done without making changes
  -f, --force     Force sync even if not due yet
  --config=PATH   Path to config file (default: ../data/dns-sync-config.json)

Configuration:
  The script reads from data/dns-sync-config.json which can be managed via
  the IPplan web interface or manually edited.

  Example configuration:
  {
    "enabled": true,
    "sync_interval_minutes": 60,
    "zones": [
      {
        "enabled": true,
        "domain": "example.com",
        "server": "ns1.example.com",
        "customer": 1,
        "type": "forward",
        "sync_interval_minutes": 30
      }
    ]
  }

Scheduling:
  Windows: Use contrib/dns-sync-setup.ps1 (run as Administrator)
  Linux:   Use contrib/dns-sync-setup.sh (run as root)

HELP;
}

?>
