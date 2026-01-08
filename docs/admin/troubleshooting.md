# Troubleshooting

This guide helps resolve common IPplan issues.

## Installation Issues

### Blank/White Page

**Symptoms:** Page loads but shows nothing

**Solutions:**

1. Enable error display:
   ```php
   // Add to top of config.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

2. Check PHP error log:
   ```bash
   tail -f /var/log/apache2/error.log
   ```

3. Verify PHP extensions:
   ```bash
   php -m | grep -E "mysqli|session|mbstring"
   ```

### Database Connection Failed

**Solutions:**

1. Verify config.php credentials
2. Test connection:
   ```bash
   mysql -u ipplan -p -h localhost ipplan
   ```
3. Check MySQL is running:
   ```bash
   systemctl status mysql
   ```

### Missing Database Driver

**Error:** "Missing file: drivers/adodb-mysqlt.inc.php"

**Solution:** Change `DBF_TYPE` to `'mysqli'` in config.php

### Permission Denied

**Solutions:**

Linux:
```bash
chown -R www-data:www-data /var/www/html/ipplan
chmod -R 755 /var/www/html/ipplan
```

Windows/IIS:
- Ensure IIS_IUSRS has Read & Execute permissions

## Display Issues

### Menu Shows as Flat List

**Solutions:**
1. Clear browser cache (Ctrl+F5)
2. Enable JavaScript
3. Check browser console for errors
4. Verify JavaScript files load correctly

### Broken Layout

**Solutions:**
1. Check for 404 errors in browser network tab
2. Verify BASE_URL in config.php (usually empty)

### Character Encoding Issues

**Solutions:**
```sql
ALTER DATABASE ipplan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Runtime Errors

### 500 Internal Server Error

**Diagnostic steps:**

1. Check PHP error log
2. Enable debug mode:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Common causes:
   - Undefined variables
   - Null pointer errors
   - Deprecated functions

### Session Issues

**Symptoms:** Logged out unexpectedly

**Solutions:**
1. Check session directory permissions
2. Increase session timeout in php.ini:
   ```ini
   session.gc_maxlifetime = 3600
   ```
3. Verify cookies are enabled

### Slow Performance

**Solutions:**
1. Optimize database tables:
   ```sql
   OPTIMIZE TABLE base, ipaddr, custinfo;
   ```
2. Increase PHP memory:
   ```php
   ini_set('memory_limit', '256M');
   ```
3. Check for slow database queries

## Authentication Issues

### Can't Log In

**Solutions:**
1. Check password (case-sensitive)
2. Clear browser cookies
3. Try incognito window
4. Reset via config.php admin credentials

### External Auth Not Working

**Solutions:**
1. Verify Apache auth module configuration
2. Check AUTH_INTERNAL setting
3. Review Apache error logs
4. Test LDAP connection independently

## IIS-Specific Issues

### INET_E_DATA_NOT_AVAILABLE

**Solution:** Add to config.php:
```php
define("NOCOMPRESS", TRUE);
```

### Path/URL Issues

**Symptoms:** Links go to wrong URLs

**Solution:** The updated version handles Windows paths automatically. Ensure you're running the latest version.

### FastCGI Timeout

**Solution:** Increase timeout in IIS:
1. IIS Manager > FastCGI Settings
2. Increase Activity Timeout and Request Timeout

## Database Issues

### Duplicate Key Error

**Cause:** Importing duplicate records

**Solution:** Check for existing entries before import

### Table Corruption

**Solution:**
```sql
REPAIR TABLE base;
REPAIR TABLE ipaddr;
-- etc.
```

### Connection Pool Exhausted

**Solution:**
```sql
SET GLOBAL max_connections = 200;
```

## Diagnostic Commands

### Quick System Check

```bash
# PHP version and extensions
php -v
php -m | grep -E "mysqli|session|mbstring|ldap"

# Database connection
mysql -u ipplan -p ipplan -e "SELECT COUNT(*) FROM base;"

# Web server
systemctl status apache2

# Disk space
df -h

# Error logs
tail -50 /var/log/apache2/error.log
```

### Enable Debug Logging

Add to config.php:
```php
define("DEBUG", TRUE);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/ipplan_debug.log');
```

Remember to disable debug mode in production.

## Getting Help

If you can't resolve an issue:

1. Check the [Admin FAQ](faq.md)
2. Collect diagnostic information:
   - PHP version
   - Database version
   - Web server type/version
   - Error messages (exact text)
   - Steps to reproduce
3. Search existing issues
4. Report new issues with full details
