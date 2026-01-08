# Installation Guide

This guide covers the initial installation and configuration of IPplan.

## System Requirements

### Required
- **PHP**: 8.0 or higher (8.2+ recommended)
- **Database**: MySQL 8.x / MariaDB 10.x (recommended) or PostgreSQL
- **Web Server**: Apache with mod_php or IIS with PHP

### Required PHP Extensions
- `mysqli` - MySQL database connectivity
- `session` - User session management
- `mbstring` - Multi-byte string support

### Optional PHP Extensions
- `snmp` - For importing from router tables
- `ldap` - For future LDAP integration
- `zlib` - For gzip compression (not used on IIS)

## Installation Steps

### Step 1: Extract Files

Extract the IPplan archive to your web server's document root:

**Linux/Apache:**
```bash
tar -xzf ipplan-2026.x.x.x.tar.gz -C /var/www/html/
mv /var/www/html/ipplan-2026.x.x.x /var/www/html/ipplan
```

**Windows/IIS:**
Extract to `C:\inetpub\wwwroot\IPplan\`

### Step 2: Set File Permissions

**Linux:**
```bash
chown -R www-data:www-data /var/www/html/ipplan
chmod -R 755 /var/www/html/ipplan
```

**Windows:**
Ensure `IIS_IUSRS` has Read & Execute permissions on the IPplan folder.

### Step 3: Create the Database

**MySQL/MariaDB:**
```sql
CREATE DATABASE ipplan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ipplan'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON ipplan.* TO 'ipplan'@'localhost';
FLUSH PRIVILEGES;
```

**PostgreSQL:**
```sql
CREATE DATABASE ipplan WITH ENCODING 'UTF8';
CREATE USER ipplan WITH PASSWORD 'your_secure_password';
GRANT ALL PRIVILEGES ON DATABASE ipplan TO ipplan;
```

### Step 4: Configure IPplan

1. Copy the sample configuration:
   ```bash
   cp config-dist.php config.php
   ```

2. Edit `config.php` with your settings:
   ```php
   // Database connection
   define("DBF_TYPE", 'mysqli');
   define("DBF_HOST", 'localhost');
   define("DBF_USER", 'ipplan');
   define("DBF_NAME", 'ipplan');
   define("DBF_PASSWORD", 'your_secure_password');

   // Admin credentials (for initial setup)
   define("ADMINUSER", 'admin');
   define("ADMINPASSWD", 'change_this_password');
   ```

See [Configuration Reference](configuration.md) for all available options.

### Step 5: Initialize the Database

1. Navigate to: `http://your-server/ipplan/admin/install.php`
2. Follow the on-screen instructions
3. The installer creates all necessary database tables
4. **Delete or rename install.php after installation** for security

### Step 6: First Login

1. Navigate to `http://your-server/ipplan/`
2. Log in with the admin credentials from config.php
3. Immediately change the admin password via **Options > Change my Password**

## Post-Installation

### Security Checklist

- [ ] Changed default admin password
- [ ] Removed/renamed `admin/install.php`
- [ ] Configured HTTPS (strongly recommended)
- [ ] Set appropriate file permissions
- [ ] Configured firewall rules

### Initial Configuration

1. **Create your first customer**: Customers > Create a New Customer
2. **Set up network hierarchy**: Create Areas and Ranges
3. **Create user accounts**: Admin > Users > Create a new User
4. **Configure groups**: Admin > Groups for permission management

### Web Server Configuration

**Apache - Recommended .htaccess:**
```apache
# Deny access to sensitive files
<FilesMatch "\.(inc|php\.bak|log)$">
    Require all denied
</FilesMatch>
```

**IIS - web.config:**
```xml
<configuration>
  <system.webServer>
    <security>
      <requestFiltering>
        <fileExtensions>
          <add fileExtension=".inc" allowed="false" />
        </fileExtensions>
      </requestFiltering>
    </security>
  </system.webServer>
</configuration>
```

## Troubleshooting Installation

### "Missing file: drivers/adodb-mysqlt.inc.php"
Change `DBF_TYPE` from `mysql` or `maxsql` to `mysqli` in config.php.

### Blank page after installation
Enable PHP error display temporarily to diagnose:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Database connection failed
- Verify credentials in config.php
- Ensure database server is running
- Check that the user has proper privileges

See [Troubleshooting](troubleshooting.md) for more solutions.
