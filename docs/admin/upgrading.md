# Upgrading IPplan

This guide covers upgrading from previous versions to the current PHP 8.x compatible release.

## Important: Do Not Reuse Your Old config.php

**Your old config.php file will not work with this version.** The new version includes required configuration options that do not exist in older versions. You must use the new config.php and transfer your settings to it.

## Upgrading from v4.92b or Earlier

### Pre-Upgrade Checklist

- [ ] **Backup your database**
  ```bash
  mysqldump -u root -p ipplan > ipplan_backup_$(date +%Y%m%d).sql
  ```
- [ ] **Backup your entire IPplan directory** (including config.php and any custom templates)
  ```bash
  cp -r /var/www/html/ipplan /var/www/html/ipplan.backup
  ```
- [ ] **Document your current settings** from config.php:
  - Database credentials: `DBF_HOST`, `DBF_USER`, `DBF_PASSWORD`, `DBF_NAME`
  - Admin credentials: `ADMINUSER`, `ADMINPASSWD`
  - Any other customizations you have made
- [ ] **Verify PHP 8.0+ is installed**
  ```bash
  php -v
  ```

### Upgrade Steps

#### Step 1: Record Your Settings

Open your current `config.php` and note these values:

```php
// Database settings - WRITE THESE DOWN
define("DBF_HOST", 'your_host');
define("DBF_USER", 'your_db_user');
define("DBF_PASSWORD", 'your_db_password');
define("DBF_NAME", 'your_db_name');

// Admin credentials - WRITE THESE DOWN
define("ADMINUSER", 'your_admin');
define("ADMINPASSWD", 'your_admin_password');
```

Also note any other settings you have customized (MAXTABLESIZE, MAILTO, etc.).

#### Step 2: Clear the Old Installation

After backing up (Step 1 of the checklist), remove the old files from your web directory:

```bash
# Remove old installation
rm -rf /var/www/html/ipplan/*
```

#### Step 3: Install the New Version

Extract the new version directly to your web directory:

```bash
# Extract new version
tar -xzf ipplan-2026.x.x.x.tar.gz -C /var/www/html/ipplan/

# Or if the archive extracts to a subdirectory:
tar -xzf ipplan-2026.x.x.x.tar.gz
mv ipplan-2026.x.x.x/* /var/www/html/ipplan/
```

#### Step 4: Configure the New Installation

Edit the NEW `config.php` file and update it with your settings:

```php
// Database connection - use YOUR values from Step 1
define("DBF_TYPE", 'mysqli');  // REQUIRED: must be 'mysqli', not 'mysql'
define("DBF_HOST", 'your_host');
define("DBF_USER", 'your_db_user');
define("DBF_PASSWORD", 'your_db_password');
define("DBF_NAME", 'your_db_name');

// Admin credentials - use YOUR values from Step 1
define("ADMINUSER", 'your_admin');
define("ADMINPASSWD", 'your_admin_password');
```

**Critical:** The `DBF_TYPE` must be set to `'mysqli'`. The old `'mysql'` and `'maxsql'` drivers are no longer available in PHP 7+.

#### Step 5: Restore Custom Templates (if applicable)

If you had custom templates in your old installation:

```bash
cp /var/www/html/ipplan.backup/templates/display/*.xml /var/www/html/ipplan/templates/display/
```

#### Step 6: Set Permissions

```bash
chown -R www-data:www-data /var/www/html/ipplan
chmod -R 755 /var/www/html/ipplan
```

#### Step 7: Test the Installation

1. Navigate to IPplan in your browser
2. Log in with your admin credentials
3. Verify:
   - Menu displays correctly
   - Customers/subnets are visible
   - IP addresses load properly
   - Search functionality works

### What Changed in This Version

#### Database Driver (Required Change)
- Old `mysql` and `maxsql` drivers were removed in PHP 7+
- `mysqli` driver is now required
- No database schema changes are needed - your existing data works as-is

#### PHP 8 Compatibility
- Class property declarations updated
- Constructor methods modernized
- Deprecated function calls replaced

#### New Features
- Integrated help documentation system
- About page with version info
- Configurable issue tracker and community links
- Editable main page content (docs/main-menu.md)

#### Updated Dependencies
- ADOdb upgraded to v5.22.8
- PHPLayersMenu updated for modern browsers
- Net/DNS library updated for PHP 8

## Troubleshooting Upgrades

### "Your config.php file is inconsistent"

**Cause:** You are using an old config.php that is missing required settings.

**Solution:** Use the new config.php file and transfer your database/admin credentials to it. Do not copy your old config.php over the new one.

### "Missing file: drivers/adodb-mysql.inc.php" or similar

**Cause:** `DBF_TYPE` is set to `'mysql'` or `'maxsql'` instead of `'mysqli'`.

**Solution:** Edit config.php and change `DBF_TYPE` to `'mysqli'`.

### Menu displays as a flat list or looks wrong

**Solution:** Clear your browser cache (Ctrl+F5 or Cmd+Shift+R).

### 500 Internal Server Error

**Check:**
1. PHP error log for the specific error message
2. File permissions are correct (755 for directories, 644 for files)
3. config.php contains valid PHP syntax
4. All required PHP extensions are installed (mysqli, gd, etc.)

### White/blank page

**Enable debugging temporarily:**
```php
// Add to the top of config.php (after <?php)
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### IIS-Specific Issues

- **gzip compression issues**: The new version auto-detects IIS and adjusts accordingly
- **Path separator issues**: Backslash handling has been fixed
- **Timeouts**: Increase FastCGI timeout in IIS Manager if needed

## Rolling Back

If you need to revert to your previous installation:

```bash
# Remove failed upgrade
rm -rf /var/www/html/ipplan/*

# Restore backup
cp -r /var/www/html/ipplan.backup/* /var/www/html/ipplan/

# Restore database if needed
mysql -u root -p ipplan < ipplan_backup_YYYYMMDD.sql
```

Note: Rolling back also requires reverting to a PHP version that supports the old mysql driver (PHP 5.x), which is not recommended for security reasons.

## Version History

| Version | Date | Notes |
|---------|------|-------|
| 2026.1.x | Jan 2026 | PHP 8.x compatibility, help system, modernization |
| 4.92b | 2009 | Last original release |

See CHANGELOG for detailed version history.
