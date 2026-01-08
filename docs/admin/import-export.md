# Import/Export Operations

IPplan provides tools for bulk data operations and integration with other systems.

## Importing Data

### Import Subnet Descriptions

Bulk import subnets from a CSV file.

**File Format:**
```csv
baseaddr,subnetsize,description
192.168.1.0,256,Office LAN
192.168.2.0,256,Server Network
10.0.0.0,65536,Corporate WAN
```

**Fields:**
- `baseaddr`: Network base address
- `subnetsize`: Number of addresses (not CIDR notation)
- `description`: Subnet description

**Size Reference:**

| CIDR | Size Value |
|------|------------|
| /30  | 4          |
| /29  | 8          |
| /28  | 16         |
| /27  | 32         |
| /26  | 64         |
| /25  | 128        |
| /24  | 256        |
| /23  | 512        |
| /22  | 1024       |
| /16  | 65536      |

**Process:**
1. Go to **Admin > Import > Import Subnet Descriptions**
2. Select the customer
3. Upload the CSV file
4. Review the preview
5. Confirm import

### Import IP Address Records

Import individual IP address assignments.

**File Format:**
```csv
ipaddr,user,location,description,telephone
192.168.1.10,jsmith,Building A,Development Workstation,555-1234
192.168.1.11,mwilson,Building A,Development Workstation,555-1235
192.168.1.20,printer01,Lobby,HP LaserJet,
```

**Requirements:**
- IP addresses must be within existing subnets
- Create subnets before importing IPs

**Process:**
1. Go to **Admin > Import > Import IP Address Detail Records**
2. Select the customer
3. Select subnet (or all)
4. Upload the CSV file
5. Review and confirm

### Import Tips

- **Test first**: Import to a test customer before production
- **Validate data**: Check for typos in IP addresses
- **Use UTF-8**: Save files with UTF-8 encoding
- **No headers**: Some imports expect no header row

## Exporting Data

### Export Subnet Descriptions

**Process:**
1. Go to **Admin > Export > Export Subnet Descriptions**
2. Select customer (or all)
3. Choose format (CSV, tab-delimited)
4. Download the file

**Output:**
```csv
baseaddr,subnetsize,description,customer
192.168.1.0,256,"Office LAN","ACME Corp"
```

### Export IP Address Records

**Process:**
1. Go to **Admin > Export > Export IP Address Detail Records**
2. Select customer and subnet
3. Choose format
4. Download the file

**Output fields:**
- IP address
- User/device name
- Location
- Description
- Telephone
- MAC address
- Last modified date

## Automation

### Scheduled Exports

Create scripts for regular exports:

```bash
#!/bin/bash
# export_ipplan.sh
DATE=$(date +%Y%m%d)
EXPORT_DIR="/backup/ipplan"

# Export via curl (requires auth)
curl -s -u admin:password \
  "http://ipplan/admin/exportbase.php?customer=all&format=csv" \
  > "$EXPORT_DIR/subnets_$DATE.csv"

curl -s -u admin:password \
  "http://ipplan/admin/exportip.php?customer=all&format=csv" \
  > "$EXPORT_DIR/ipaddr_$DATE.csv"
```

### Integration with Other Systems

Export data for:
- CMDB integration
- Network monitoring tools
- Asset management systems
- Spreadsheet reporting

## Database Backup

For complete backup, use database tools directly:

**MySQL/MariaDB:**
```bash
# Full backup
mysqldump -u root -p ipplan > ipplan_backup_$(date +%Y%m%d).sql

# Compressed
mysqldump -u root -p ipplan | gzip > ipplan_backup_$(date +%Y%m%d).sql.gz
```

**Restore:**
```bash
mysql -u root -p ipplan < ipplan_backup.sql
# Or compressed:
gunzip < ipplan_backup.sql.gz | mysql -u root -p ipplan
```

## Migration

To migrate IPplan to a new server:

1. **Backup everything:**
   - Database dump
   - config.php
   - Any customizations

2. **On new server:**
   - Install IPplan files
   - Create database
   - Import database dump
   - Copy config.php (update if needed)
   - Test thoroughly

## Troubleshooting Imports

| Error | Solution |
|-------|----------|
| "IP address not in any subnet" | Create the subnet first |
| "Invalid subnet size" | Use address count, not CIDR |
| "Duplicate entry" | Record already exists |
| "Character encoding issues" | Save as UTF-8 without BOM |
