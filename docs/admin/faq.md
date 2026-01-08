# System Administration FAQ

Frequently asked questions for IPplan system administrators.

## Installation & Setup

### What are the system requirements?

- PHP 8.0 or higher (8.2+ recommended)
- MySQL 8.x, MariaDB 10.x, or PostgreSQL
- Apache with mod_php or IIS with PHP
- mysqli PHP extension (required)
- SNMP extension (optional, for router imports)

### What database driver should I use?

Use `mysqli` in config.php:
```php
define("DBF_TYPE", 'mysqli');
```

The old `mysql` and `maxsql` drivers are not available in PHP 7+.

### How do I reset the admin password?

Edit config.php and change:
```php
define("ADMINPASSWD", 'new_password');
```

The static admin account always works regardless of database state.

### Can I run IPplan on Windows/IIS?

Yes. The current version includes fixes for:
- gzip compression issues (auto-disabled on IIS)
- Path separator handling
- URL generation

## Authentication

### How does external authentication work?

IPplan can use Apache's authentication modules:

```php
define("AUTH_INTERNAL", FALSE);
define("AUTH_VAR", 'REMOTE_USER');
```

Apache handles authentication (LDAP, etc.), and IPplan uses the authenticated username.

### Do users need accounts even with LDAP?

Currently, yes. Users must exist in IPplan's database even when using external authentication. See [Planned Enhancements](planned-enhancements.md) for upcoming auto-provisioning.

### Can I use Single Sign-On (SSO)?

IPplan supports CAS (Central Authentication Service):
```php
define("AUTH_CAS", TRUE);
define("AUTH_CAS_SERVER", 'cas.yourdomain.com');
```

## Multi-Tenant / MSP Setup

### How do I isolate client data?

Use groups and customer assignments:
1. Create a group for each client
2. Create the customer with that group as admin group
3. Users in that group only see that customer

### Can different clients have overlapping IPs?

Yes. Each customer has independent address space. Multiple customers can use the same RFC1918 ranges without conflict.

### How do I give clients read-only access?

Create a group with:
- Authority boundary: 0.0.0.0 / 0 (read-only flag)
- No "create customer" permission
- Assigned to specific customer

## Performance

### How many subnets can IPplan handle?

IPplan scales well to tens of thousands of subnets. For very large deployments:
- Ensure proper database indexing
- Regular maintenance/optimization
- Adequate server resources

### How do I improve performance?

1. Run database optimization regularly
2. Increase PHP memory limit if needed
3. Use appropriate hardware
4. Index custom fields if heavily used

## Security

### How do I secure my installation?

1. Use HTTPS
2. Change default admin password
3. Remove install.php after setup
4. Restrict file permissions
5. Keep PHP and database updated
6. Regular security audits

### Is the audit log tamper-proof?

The audit log records changes but is stored in the database. For compliance requirements, consider:
- Regular audit log exports
- Database-level auditing
- Backup retention policies

## Backup & Recovery

### What should I backup?

| Item | Priority | Frequency |
|------|----------|-----------|
| Database | Critical | Daily |
| config.php | Critical | After changes |
| Customizations | High | After changes |

### How do I restore from backup?

```bash
# Restore database
mysql -u root -p ipplan < backup.sql

# Restore config
cp config.backup.php config.php
```

## Upgrades

### Will upgrading lose my data?

No. Database schema is preserved during upgrades. Always backup before upgrading as a precaution.

### Can I skip versions when upgrading?

Generally yes, from v4.92b to current. Very old versions may need intermediate steps.

## Troubleshooting

### Where are the log files?

IPplan uses PHP's error logging. Location depends on your php.ini:
- Apache default: `/var/log/apache2/error.log`
- IIS: PHP error log or Event Viewer
- Custom: Set `error_log` in php.ini

### Why do I get a blank page?

Usually a PHP fatal error. Enable error display:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Why can't users see certain customers?

Check:
1. User's group memberships
2. Customer's admin group setting
3. Group's "view all" permission
4. Authority boundaries

## Integration

### Is there an API?

IPplan includes basic XML-RPC API. REST API is under consideration for future versions.

### Can I import from other IPAM tools?

Yes, via CSV import. Export from your current tool and format to match IPplan's import requirements.

### Does IPplan support webhooks?

Not currently. This is under consideration for future versions.

## Licensing

### Is IPplan free?

Yes. IPplan is open source under the GNU General Public License (GPL).

### Can I modify IPplan?

Yes, under GPL terms. Modifications must also be GPL licensed if distributed.

### Can I use IPplan commercially?

Yes. The GPL allows commercial use. There's no separate commercial license.
