# Managing Subnets

This guide covers viewing, searching, and working with subnets in IPplan.

## Viewing Subnets

### Display Subnet Information

1. Go to **Network > Subnets > Display Subnet Information**
2. Select a customer from the dropdown
3. The subnet list shows:
   - Base address
   - Size (number of IPs)
   - Description
   - Utilization

### Tree View

For a hierarchical view:

1. Go to **Network > Subnets > Display Subnet Information - Tree**
2. Expand/collapse branches to navigate
3. Click any subnet to see details

## Searching Subnets

### Basic Search

1. Go to **Network > Subnets > Search All Subnets**
2. Enter your search term
3. Click Search

**You can search for:**
- Full or partial IP address
- Subnet description
- Device or user name
- Any text in IP records

### Search Tips

- Use partial IPs: `192.168.1` finds all in that range
- Search spans all customers you can access
- Results link directly to the full record

## Viewing Subnet Details

Click on any subnet to see:
- All IP addresses in the subnet
- Used vs. available addresses
- Individual IP assignments
- Quick links to modify

### Understanding the IP List

| Indicator | Meaning |
|-----------|---------|
| Highlighted | IP is assigned |
| Plain | IP is available |
| First IP | Usually network address |
| Last IP | Usually broadcast |

## Finding Free Space

Need to find available IP blocks?

1. Go to **Network > Subnets > Find Free Space**
2. Select the customer
3. Specify minimum size needed
4. Review results

The tool shows gaps between existing subnets where new allocations can fit.

## Checking for Overlaps

To verify no subnets overlap:

1. Go to **Network > Subnets > Display Subnet Overlap**
2. Select customer
3. View any overlapping allocations

This helps identify configuration issues.

## Creating Subnets

*Note: This may require additional permissions.*

1. Go to **Network > Subnets > Create Subnet**
2. Select the customer
3. Enter subnet details:
   - **Base Address**: Network address (e.g., 192.168.1.0)
   - **Subnet Size**: Select from dropdown
   - **Description**: Purpose of the subnet
4. Click Create

### Understanding Subnet Sizes

| CIDR | Addresses | Usable |
|------|-----------|--------|
| /30  | 4         | 2      |
| /29  | 8         | 6      |
| /28  | 16        | 14     |
| /27  | 32        | 30     |
| /26  | 64        | 62     |
| /25  | 128       | 126    |
| /24  | 256       | 254    |

*Usable = Total minus network and broadcast addresses*

## Modifying Subnets

*Note: This may require additional permissions.*

1. Go to **Network > Subnets > Delete/Edit/Modify/Split/Join Subnet**
2. Find and select the subnet
3. Available actions:
   - **Edit**: Change description
   - **Split**: Divide into smaller subnets
   - **Join**: Combine with adjacent subnet
   - **Delete**: Remove the subnet

### Splitting a Subnet

1. Select the subnet
2. Click Split
3. Choose the new size
4. Existing IP assignments are preserved

Example: A /24 can split into:
- Two /25 subnets, or
- Four /26 subnets, or
- Eight /27 subnets

### Joining Subnets

Subnets can be joined if they are:
- Adjacent in address space
- Same customer
- Combined size is valid CIDR

## Best Practices

### Documentation
- Use clear, consistent descriptions
- Include purpose and location
- Note the responsible team

### Organization
- Group related subnets logically
- Use areas and ranges for hierarchy
- Plan for growth

### Maintenance
- Review unused subnets periodically
- Update descriptions when purpose changes
- Clean up decommissioned subnets
