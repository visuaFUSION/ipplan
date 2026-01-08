# Configuration Reference

All IPplan configuration is managed through `config.php` in the application root.

## Database Settings

```php
// Database driver - use 'mysqli' for MySQL/MariaDB
// Options: mysqli, postgres9, postgres8, oci8po, mssql
define("DBF_TYPE", 'mysqli');

// Database connection
define("DBF_HOST", 'localhost');
define("DBF_USER", 'ipplan');
define("DBF_NAME", 'ipplan');
define("DBF_PASSWORD", 'your_password');
```

**Important:** The old `mysql` and `maxsql` drivers are not available in PHP 7+. Always use `mysqli`.

## Admin Account

```php
// Static admin account (bypasses database authentication)
define("ADMINUSER", 'admin');
define("ADMINPASSWD", 'admin_password');
```

This account always has full system access regardless of group settings.

## Authentication Settings

```php
// Use internal (database) authentication
define("AUTH_INTERNAL", TRUE);

// For external authentication (Apache LDAP, CAS, etc.)
// define("AUTH_INTERNAL", FALSE);
// define("AUTH_VAR", 'REMOTE_USER');

// Show logout button in menu
define("AUTH_LOGOUT", TRUE);

// CAS Server settings (if using CAS)
define("AUTH_CAS", FALSE);
define("AUTH_CAS_SERVER", 'cas.yourdomain.com');
define("AUTH_CAS_PORT", 443);
```

## Feature Toggles

```php
// Enable DNS zone management features
define("DNSENABLED", TRUE);

// Enable registrar/SWIP features
define("REGENABLED", TRUE);

// Enable SNMP features for router imports
define("SNMPENABLED", TRUE);
```

## Display Settings

```php
// Rows per page in listings
define("ROWS_PER_PAGE", 50);

// Base URL (usually leave empty for auto-detection)
define("BASE_URL", '');

// Default language
define("DEFAULT_LANG", 'en');
```

## Security Settings

```php
// Session timeout in seconds (0 = use PHP default)
define("SESSION_TIMEOUT", 0);

// Restrict system administration documentation to privileged users
define("RESTRICT_ADMIN_DOCS", TRUE);
```

## Email Settings

```php
// SMTP server for notifications
define("SMTP_SERVER", 'localhost');
define("SMTP_PORT", 25);

// From address for system emails
define("EMAIL_FROM", 'ipplan@yourdomain.com');
```

## Menu Extensions

```php
// Enable custom menu items
define("MENU_PRIV", FALSE);

// Custom menu items (when MENU_PRIV is TRUE)
define("MENU_EXTENSION", '
.|Custom Menu
..|Custom Item|http://example.com
');
```

## Advanced Settings

```php
// Text domain for translations
define("MYDOMAIN", 'ipplan');

// Debug mode (enables verbose error output)
define("DEBUG", FALSE);

// Disable output compression (auto-disabled on IIS)
define("NOCOMPRESS", FALSE);
```

## Environment-Specific Configuration

You can include environment-specific overrides:

```php
// At the end of config.php
$env_config = __DIR__ . '/config.' . gethostname() . '.php';
if (file_exists($env_config)) {
    include($env_config);
}
```

This allows maintaining different settings for development, staging, and production.

## Configuration Best Practices

1. **Never commit config.php to version control** - it contains passwords
2. **Use strong passwords** for database and admin accounts
3. **Keep a backup** of your config.php
4. **Document custom settings** with comments
5. **Review settings after upgrades** - new options may be available
