# User FAQ

Frequently asked questions for IPplan users.

## Getting Started

### How do I log in?

Navigate to your organization's IPplan URL and enter your username and password. Contact your administrator if you don't have credentials.

### How do I change my password?

1. Go to **Options > Change my Password**
2. Enter your current password
3. Enter your new password twice
4. Click Change

### Why can't I see certain subnets or customers?

Your access is controlled by your administrator. You can only see resources you've been granted access to. Contact your administrator if you need additional access.

### How long does my session last?

Sessions typically last until you log out or close your browser. Extended inactivity may also end your session. Simply log in again if this happens.

## Finding Information

### How do I find a specific IP address?

1. Go to **Network > Subnets > Search All Subnets**
2. Enter the IP address (full or partial)
3. Click Search

### How do I see what IPs are available?

1. Navigate to a subnet
2. Available IPs are shown without highlighting
3. Or use **Network > Subnets > Find Free Space** for larger blocks

### How do I find who's using an IP?

1. Search for the IP address
2. Or navigate to the subnet and find the IP
3. The User/Device field shows the assignment

### Can I search by device name?

Yes! The search function searches across:
- IP addresses
- User/device names
- Descriptions
- Location information

## Making Changes

### How do I assign an IP address?

1. Navigate to the subnet
2. Click on an available IP
3. Fill in User, Location, and Description
4. Click Modify

### How do I update an IP record?

1. Navigate to or search for the IP
2. Click on it
3. Update the fields
4. Click Modify

### How do I release an IP address?

1. Navigate to the assigned IP
2. Clear all the fields
3. Click Modify

The IP will then show as available.

### I can't modify an IP - why?

Possible reasons:
- You don't have permission for that subnet
- Your session expired (try logging in again)
- The record is locked
- Contact your administrator

## Understanding the Interface

### What do the colors/highlighting mean?

- **Highlighted rows**: IP is assigned
- **Plain rows**: IP is available
- Colors may vary by your organization's configuration

### What's the difference between subnets and ranges?

- **Subnets**: Individual networks (e.g., 192.168.1.0/24)
- **Ranges**: Larger blocks containing multiple subnets
- **Areas**: Logical groupings (by location, department, etc.)

### What does the size number mean?

The size is the number of addresses in a subnet:
- 256 = /24 subnet
- 128 = /25 subnet
- 64 = /26 subnet
- And so on

## Common Tasks

### How do I find free space for a new subnet?

1. Go to **Network > Subnets > Find Free Space**
2. Select the customer
3. Enter the size you need
4. View available blocks

### How do I see all IPs in a subnet?

1. Go to **Network > Subnets > Display Subnet Information**
2. Select the customer
3. Click on the subnet

### How do I print or export data?

Use your browser's print function for screen content. For larger exports, contact your administrator who can export data to CSV.

## Problems

### I forgot my password

Contact your administrator to reset it.

### The page isn't loading correctly

1. Try refreshing (Ctrl+F5)
2. Clear your browser cache
3. Try a different browser
4. Contact your administrator

### I made a mistake - can I undo?

IPplan doesn't have an undo function, but:
- Changes are logged in the audit trail
- Your administrator can review history
- Simply update the record to correct it

### Something seems wrong with the data

Report it to your administrator. They can review the audit log to see what changed and when.

## Best Practices

### What information should I include?

At minimum:
- User/Device: Who or what is using this IP
- Location: Where is it physically
- Description: What is it used for

### How should I name devices?

Follow your organization's naming convention. Common patterns:
- `username-devicetype` (jsmith-laptop)
- `function-number` (webserver-01)
- `location-function` (dc1-switch-01)

### When should I update records?

- When a device is assigned an IP
- When a device moves
- When a device is decommissioned
- When ownership changes

## Still Need Help?

Contact your system administrator for:
- Access issues
- Permission requests
- Technical problems
- Questions about your organization's policies
