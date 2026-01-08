# Maintenance

Regular maintenance keeps IPplan running smoothly.

## Database Maintenance

### Built-in Maintenance

1. Go to **Admin > Maintenance**
2. Available functions:
   - Optimize database tables
   - Clean orphaned records
   - Recalculate statistics

Run maintenance monthly or after large data changes.

### Manual Database Optimization

**MySQL/MariaDB:**
```sql
-- Optimize all IPplan tables
OPTIMIZE TABLE base, ipaddr, custinfo, users, grp, usergrp, bounds;

-- Analyze tables for query optimization
ANALYZE TABLE base, ipaddr, custinfo;
```

**PostgreSQL:**
```sql
VACUUM ANALYZE;
```

## Backup Strategy

### What to Backup

| Item | Frequency | Method |
|------|-----------|--------|
| Database | Daily | mysqldump |
| config.php | After changes | File copy |
| Customizations | After changes | File copy |

### Backup Script Example

```bash
#!/bin/bash
# ipplan_backup.sh

BACKUP_DIR="/backup/ipplan"
DATE=$(date +%Y%m%d_%H%M)
RETENTION_DAYS=30

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Database backup
mysqldump -u ipplan -p'password' ipplan | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Config backup
cp /var/www/html/ipplan/config.php "$BACKUP_DIR/config_$DATE.php"

# Clean old backups
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "config_*.php" -mtime +$RETENTION_DAYS -delete

echo "Backup completed: $DATE"
```

Schedule with cron:
```cron
0 2 * * * /usr/local/bin/ipplan_backup.sh >> /var/log/ipplan_backup.log 2>&1
```

### Restore Procedure

1. **Stop web server** (optional, prevents changes during restore)
2. **Restore database:**
   ```bash
   gunzip < db_20260108.sql.gz | mysql -u root -p ipplan
   ```
3. **Restore config if needed:**
   ```bash
   cp config_20260108.php /var/www/html/ipplan/config.php
   ```
4. **Test** the application

## Audit Log

### Reviewing the Audit Log

1. Go to **Admin > Display Audit Log**
2. Filter by:
   - Date range
   - User
   - Action type
3. Review changes with before/after values

### Audit Log Maintenance

The audit log can grow large. Consider periodic archival:

```sql
-- Archive old audit entries (example: over 1 year old)
CREATE TABLE audit_archive AS
SELECT * FROM audit WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);

DELETE FROM audit WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

## Performance Monitoring

### Signs of Performance Issues

- Slow page loads
- Timeout errors
- Database connection errors

### Diagnostic Queries

**MySQL/MariaDB:**
```sql
-- Check table sizes
SELECT table_name,
       ROUND(data_length/1024/1024, 2) AS data_mb,
       ROUND(index_length/1024/1024, 2) AS index_mb
FROM information_schema.tables
WHERE table_schema = 'ipplan'
ORDER BY data_length DESC;

-- Check for slow queries (if slow query log enabled)
SHOW FULL PROCESSLIST;
```

### PHP Performance

```php
// Add to config.php for debugging
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
```

## Security Maintenance

### Regular Security Tasks

- [ ] Review user accounts quarterly
- [ ] Remove inactive users
- [ ] Audit group permissions
- [ ] Check for software updates
- [ ] Review access logs
- [ ] Test backup restoration

### Log Review

**Apache:**
```bash
# Check for suspicious activity
grep -i "ipplan" /var/log/apache2/access.log | grep -v "200\|302"
```

**IIS:**
Check Event Viewer and IIS logs for failed requests.

## Upgrading IPplan

See [Upgrading](upgrading.md) for detailed upgrade procedures.

### Pre-Upgrade Checklist

- [ ] Full database backup
- [ ] config.php backup
- [ ] Document customizations
- [ ] Test environment ready
- [ ] Maintenance window scheduled

## Troubleshooting

See [Troubleshooting](troubleshooting.md) for common issues.

### Quick Diagnostics

```bash
# Check PHP version
php -v

# Check PHP extensions
php -m | grep -E "mysqli|session|mbstring"

# Test database connection
mysql -u ipplan -p -h localhost ipplan -e "SELECT COUNT(*) FROM base;"

# Check disk space
df -h

# Check web server status
systemctl status apache2  # or httpd, nginx, etc.
```
