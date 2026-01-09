<?php

// IPplan Dashboard
// Current Branch themes only
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

require_once("config.php");
require_once("ipplanlib.php");
require_once("adodb/adodb.inc.php");
require_once("class.dbflib.php");
require_once("layout/class.layout");
require_once("auth.php");

// Authenticate user first
$auth = new SQLAuthenticator(REALM, REALMERROR);
$grps = $auth->authenticate();

// Check if using Current Branch theme - redirect to index if not
if (!isCurrentBranchTheme()) {
    header("Location: index.php");
    exit;
}

// Set language
isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);

// Initialize database
$ds = new IPplanDbf();

// Get authenticated username
$username = getAuthUsername();

// Get user's dashboard card preferences (default: all enabled)
// Default: all dashboard cards enabled
$dashboardCards = array(
    'quick_stats' => true,
    'recent_activity' => true,
    'subnet_usage' => true,
    'quick_actions' => true,
    'system_info' => true
);

// Override with saved preferences if cookie exists
if (isset($_COOKIE['ipplanDashboardCards'])) {
    $savedCards = json_decode($_COOKIE['ipplanDashboardCards'], true);
    if (is_array($savedCards)) {
        foreach ($savedCards as $cardKey => $enabled) {
            if (array_key_exists($cardKey, $dashboardCards)) {
                $dashboardCards[$cardKey] = (bool)$enabled;
            }
        }
    }
}

// Gather statistics
$stats = array(
    'customers' => 0,
    'subnets' => 0,
    'ip_addresses' => 0,
    'dns_zones' => 0
);

// Count customers
$result = $ds->ds->Execute("SELECT COUNT(*) as cnt FROM customer");
if ($result && $row = $result->FetchRow()) {
    $stats['customers'] = $row['cnt'];
}

// Count subnets
$result = $ds->ds->Execute("SELECT COUNT(*) as cnt FROM base");
if ($result && $row = $result->FetchRow()) {
    $stats['subnets'] = $row['cnt'];
}

// Count IP addresses (records with data)
$result = $ds->ds->Execute("SELECT COUNT(*) as cnt FROM ipaddr WHERE descrip != '' OR hname != ''");
if ($result && $row = $result->FetchRow()) {
    $stats['ip_addresses'] = $row['cnt'];
}

// Count DNS zones (if DNS enabled)
if (defined('DNSENABLED') && DNSENABLED) {
    $result = $ds->ds->Execute("SELECT COUNT(*) as cnt FROM fwdzone");
    if ($result && $row = $result->FetchRow()) {
        $stats['dns_zones'] = $row['cnt'];
    }
}

// Get recent audit log entries (last 5)
$recentActivity = array();
$result = $ds->ds->Execute("SELECT userid, action, dt FROM auditlog ORDER BY dt DESC LIMIT 5");
if ($result) {
    while ($row = $result->FetchRow()) {
        $recentActivity[] = array(
            'user' => $row['userid'],
            'action' => $row['action'],
            'date' => $row['dt']
        );
    }
}

// Create page
newhtml($p);
$w = myheading($p, my_("Dashboard"));

// Dashboard CSS
$dashboardCss = <<<'CSS'
<style>
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.dashboard-card {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius-lg, 8px);
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dashboard-card h3 {
    margin: 0 0 15px 0;
    padding: 0 0 10px 0;
    border-bottom: 2px solid var(--accent-primary, #0097a7);
    color: var(--text-primary, #333);
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dashboard-card h3 svg {
    color: var(--accent-primary, #0097a7);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: var(--bg-card-alt, #f5f5f5);
    border-radius: var(--radius-md, 6px);
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--accent-primary, #0097a7);
}

.stat-label {
    font-size: 12px;
    color: var(--text-muted, #666);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-list li {
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color, #eee);
    font-size: 13px;
}

.activity-list li:last-child {
    border-bottom: none;
}

.activity-user {
    font-weight: 600;
    color: var(--accent-primary, #0097a7);
}

.activity-date {
    float: right;
    color: var(--text-muted, #999);
    font-size: 11px;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 15px;
    background: var(--accent-bg, #e0f7fa);
    border: 1px solid var(--accent-primary, #0097a7);
    border-radius: var(--radius-md, 6px);
    color: var(--accent-primary, #0097a7);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: background 0.15s ease;
}

.quick-action-btn:hover {
    background: var(--accent-primary, #0097a7);
    color: #fff;
}

.quick-action-btn svg {
    flex-shrink: 0;
}

.system-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.system-info-list li {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color, #eee);
    font-size: 13px;
}

.system-info-list li:last-child {
    border-bottom: none;
}

.system-info-label {
    color: var(--text-muted, #666);
}

.system-info-value {
    font-weight: 500;
    color: var(--text-primary, #333);
}

.no-activity {
    color: var(--text-muted, #999);
    font-style: italic;
    text-align: center;
    padding: 20px;
}
</style>
CSS;

insert($w, block($dashboardCss));

// Start dashboard grid
$dashboardHtml = '<div class="dashboard-grid">';

// Quick Stats Card
if ($dashboardCards['quick_stats']) {
    $dashboardHtml .= '
    <div class="dashboard-card">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none">
                <path d="M3 3v14h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                <path d="M7 10l3-3 3 3 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            ' . my_("Quick Stats") . '
        </h3>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['customers']) . '</div>
                <div class="stat-label">' . my_("Customers") . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['subnets']) . '</div>
                <div class="stat-label">' . my_("Subnets") . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['ip_addresses']) . '</div>
                <div class="stat-label">' . my_("IP Records") . '</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">' . number_format($stats['dns_zones']) . '</div>
                <div class="stat-label">' . my_("DNS Zones") . '</div>
            </div>
        </div>
    </div>';
}

// Recent Activity Card
if ($dashboardCards['recent_activity']) {
    $dashboardHtml .= '
    <div class="dashboard-card">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5" fill="none"/>
                <path d="M10 5v5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/>
            </svg>
            ' . my_("Recent Activity") . '
        </h3>';

    if (!empty($recentActivity)) {
        $dashboardHtml .= '<ul class="activity-list">';
        foreach ($recentActivity as $activity) {
            $actionShort = strlen($activity['action']) > 50 ? substr($activity['action'], 0, 50) . '...' : $activity['action'];
            $dashboardHtml .= '
            <li>
                <span class="activity-date">' . htmlspecialchars($activity['date']) . '</span>
                <span class="activity-user">' . htmlspecialchars($activity['user']) . '</span>:
                ' . htmlspecialchars($actionShort) . '
            </li>';
        }
        $dashboardHtml .= '</ul>';
    } else {
        $dashboardHtml .= '<p class="no-activity">' . my_("No recent activity recorded") . '</p>';
    }

    $dashboardHtml .= '</div>';
}

// Quick Actions Card
if ($dashboardCards['quick_actions']) {
    $BASE_URL = base_url();
    $dashboardHtml .= '
    <div class="dashboard-card">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none">
                <path d="M13 3l4 4-10 10H3v-4L13 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" fill="none"/>
            </svg>
            ' . my_("Quick Actions") . '
        </h3>
        <div class="quick-actions">
            <a href="' . $BASE_URL . '/user/displaybaseform.php" class="quick-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="none">
                    <rect x="7" y="1" width="6" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <rect x="1" y="15" width="5" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <rect x="14" y="15" width="5" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <path d="M10 5v7m0 0h-6.5v3m6.5-3h6.5v3" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
                ' . my_("Browse Subnets") . '
            </a>
            <a href="' . $BASE_URL . '/user/searchallform.php" class="quick-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="none">
                    <circle cx="8.5" cy="8.5" r="6" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <path d="M13 13l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                ' . my_("Search All") . '
            </a>
            <a href="' . $BASE_URL . '/user/displaycustomerform.php" class="quick-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="6" r="4" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <path d="M3 18c0-3.5 3.5-6 7-6s7 2.5 7 6" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
                ' . my_("View Customers") . '
            </a>
            <a href="' . $BASE_URL . '/user/changesettings.php" class="quick-action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="10" r="2" stroke="currentColor" stroke-width="1.5" fill="none"/>
                    <path d="M16 10c0-.3-.02-.6-.07-.9l1.5-1.2-1.5-2.6-1.8.7c-.4-.35-.9-.65-1.5-.85L12.4 3h-3l-.3 1.9c-.6.2-1.1.5-1.5.85l-1.8-.7-1.5 2.6 1.5 1.2c-.1.6-.1 1.2 0 1.8l-1.5 1.2 1.5 2.6 1.8-.7c.4.35.9.65 1.5.85l.3 1.9h3l.3-1.9c.6-.2 1.1-.5 1.5-.85l1.8.7 1.5-2.6-1.5-1.2c.05-.3.07-.6.07-.9z" stroke="currentColor" stroke-width="1.5" fill="none"/>
                </svg>
                ' . my_("Settings") . '
            </a>
        </div>
    </div>';
}

// System Info Card
if ($dashboardCards['system_info']) {
    // Get web server info
    $webServer = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : my_("Unknown");
    // Simplify web server string (e.g., "Apache/2.4.41 (Ubuntu)" -> "Apache/2.4.41")
    if (preg_match('/^([^\s]+)/', $webServer, $matches)) {
        $webServer = $matches[1];
    }

    // Get database version
    $dbVersion = "";
    $dbServerInfo = $ds->ds->ServerInfo();
    if (is_array($dbServerInfo) && isset($dbServerInfo['version'])) {
        $dbVersion = $dbServerInfo['version'];
    }

    $dashboardHtml .= '
    <div class="dashboard-card">
        <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5" fill="none"/>
                <path d="M10 6v5M10 13v1" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
            </svg>
            ' . my_("System Information") . '
        </h3>
        <ul class="system-info-list">
            <li>
                <span class="system-info-label">' . my_("Version") . '</span>
                <span class="system-info-value">' . IPPLAN_VERSION . '</span>
            </li>
            <li>
                <span class="system-info-label">' . my_("PHP Version") . '</span>
                <span class="system-info-value">' . PHP_VERSION . '</span>
            </li>
            <li>
                <span class="system-info-label">' . my_("Web Server") . '</span>
                <span class="system-info-value">' . htmlspecialchars($webServer) . '</span>
            </li>
            <li>
                <span class="system-info-label">' . my_("Database") . '</span>
                <span class="system-info-value">' . strtoupper(DBF_TYPE) . ($dbVersion ? ' ' . htmlspecialchars($dbVersion) : '') . '</span>
            </li>
            <li>
                <span class="system-info-label">' . my_("Logged In As") . '</span>
                <span class="system-info-value">' . htmlspecialchars($username) . '</span>
            </li>
            <li>
                <span class="system-info-label">' . my_("Theme") . '</span>
                <span class="system-info-value">' . htmlspecialchars(getCurrentTheme()) . '</span>
            </li>
        </ul>
    </div>';
}

$dashboardHtml .= '</div>';

// Check if all cards are disabled - show empty state message
$allCardsDisabled = true;
foreach ($dashboardCards as $enabled) {
    if ($enabled) {
        $allCardsDisabled = false;
        break;
    }
}

if ($allCardsDisabled) {
    $settingsUrl = base_url() . '/user/changesettings.php';
    $dashboardHtml = '
    <div class="dashboard-empty-state" style="text-align: center; padding: 60px 20px; color: var(--text-muted, #666);">
        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 20 20" fill="none" style="margin-bottom: 20px; opacity: 0.5;">
            <path d="M7.7 6.67H1.46C.65 6.67 0 6.01 0 5.21V1.46C0 .65.65 0 1.46 0h6.25c.8 0 1.46.65 1.46 1.46v3.75c0 .8-.65 1.46-1.46 1.46zM1.46 1.25c-.12 0-.21.09-.21.21v3.75c0 .12.09.21.21.21h6.25c.12 0 .21-.09.21-.21V1.46c0-.12-.09-.21-.21-.21H1.46z" fill="currentColor"/>
            <path d="M18.54 20h-6.25c-.8 0-1.46-.65-1.46-1.46v-3.75c0-.8.65-1.46 1.46-1.46h6.25c.8 0 1.46.65 1.46 1.46v3.75c0 .8-.65 1.46-1.46 1.46z" fill="currentColor" opacity="0.3"/>
        </svg>
        <h3 style="margin: 0 0 10px 0; color: var(--text-primary, #333);">' . my_("No Dashboard Cards Enabled") . '</h3>
        <p style="margin: 0 0 20px 0; max-width: 400px; margin-left: auto; margin-right: auto;">
            ' . my_("Dashboard cards provide quick access to system statistics and commonly used actions.") . '
        </p>
        <a href="' . $settingsUrl . '" style="display: inline-block; padding: 10px 20px; background: var(--accent-primary, #0097a7); color: #fff; text-decoration: none; border-radius: 4px;">
            ' . my_("Enable Cards in Settings") . '
        </a>
    </div>';
}

insert($w, block($dashboardHtml));

printhtml($p);

?>
