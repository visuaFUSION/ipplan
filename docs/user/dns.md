# DNS Records

IPplan includes DNS zone management for forward and reverse DNS records.

*Note: DNS features may not be enabled in your installation. Contact your administrator if you don't see DNS options.*

## Overview

IPplan DNS management provides:
- Forward zone records (hostname → IP)
- Reverse zone records (IP → hostname)
- Integration with IP address management

## Viewing DNS Records

### Forward Zones

1. Go to **DNS > Create/Modify Zone DNS Records**
2. Select the zone (domain)
3. View all records in the zone

### Reverse Records

1. Go to **DNS > Create/Modify Reverse DNS Records**
2. Select the subnet
3. View PTR records for that subnet

## Adding DNS Records

*Note: This may require additional permissions.*

### Add a Forward Record

1. Go to **DNS > Create/Modify Zone DNS Records**
2. Select the zone
3. Enter record details:
   - **Hostname**: Just the hostname (e.g., `www`)
   - **Record Type**: A, CNAME, MX, etc.
   - **Value**: IP address or target
4. Click Add

### Record Types

| Type | Purpose | Example |
|------|---------|---------|
| A | IPv4 address | `www → 192.168.1.10` |
| AAAA | IPv6 address | `www → 2001:db8::1` |
| CNAME | Alias | `mail → mailserver.example.com` |
| MX | Mail server | `@ → mail.example.com` |
| TXT | Text record | SPF, DKIM, etc. |

### Add a Reverse Record (PTR)

1. Go to **DNS > Create/Modify Reverse DNS Records**
2. Select the subnet
3. For each IP, enter the hostname
4. Click Modify

IPplan creates the proper PTR record format automatically.

## Modifying DNS Records

1. Navigate to the zone or subnet
2. Find the record to modify
3. Update the values
4. Save changes

## Deleting DNS Records

1. Navigate to the zone or subnet
2. Find the record to delete
3. Clear the values or use the delete option
4. Save changes

## Integration with IP Addresses

When you assign an IP address with a hostname, IPplan can automatically:
- Create/update the forward A record
- Create/update the reverse PTR record

This keeps DNS and IP records synchronized.

## Best Practices

### Naming
- Use lowercase for hostnames
- Avoid special characters
- Be consistent

### TTL (Time to Live)
- Lower TTL = faster updates, more DNS queries
- Higher TTL = slower updates, fewer queries
- Common values: 300-3600 seconds

### Documentation
- Note the purpose of each record
- Keep records for decommissioned entries temporarily
- Document any special configurations

## Troubleshooting

### Can't see DNS options?
- DNS features may be disabled
- Contact your administrator

### Changes not taking effect?
- DNS changes take time to propagate
- Check the TTL value
- Clear local DNS cache

### Need to add a zone?
- Contact your administrator
- Zone creation typically requires admin privileges
