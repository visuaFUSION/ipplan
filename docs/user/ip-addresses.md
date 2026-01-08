# Working with IP Addresses

This guide covers assigning, modifying, and managing individual IP addresses.

## Viewing IP Addresses

### In a Subnet

1. Go to **Network > Subnets > Display Subnet Information**
2. Select a customer
3. Click on a subnet
4. All IP addresses are displayed

### Understanding the Display

| Element | Meaning |
|---------|---------|
| Highlighted row | IP is assigned |
| Plain row | IP is available |
| User column | Device or person assigned |
| Description | Additional information |

## Assigning an IP Address

1. Navigate to a subnet
2. Click on an available (unassigned) IP
3. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| User/Device | Who or what uses this IP | `jsmith-laptop` |
| Location | Physical or logical location | `Building A, Floor 2` |
| Description | Additional details | `Development workstation` |
| Telephone | Contact number | `x1234` |

4. Click **Modify** to save

## Modifying an IP Address

1. Navigate to the subnet containing the IP
2. Click on the assigned IP
3. Update the fields as needed
4. Click **Modify** to save

## Releasing an IP Address

To mark an IP as available again:

1. Navigate to the assigned IP
2. Clear all fields (User, Location, Description)
3. Click **Modify** to save

The IP will now show as available.

## Searching for IP Addresses

### Quick Search

1. Go to **Network > Subnets > Search All Subnets**
2. Enter search criteria:
   - Full IP: `192.168.1.50`
   - Partial IP: `192.168.1`
   - User name: `jsmith`
   - Any description text
3. Click Search

### Search Results

Results show:
- IP address
- Subnet it belongs to
- Assigned user/device
- Description

Click any result to go directly to that IP.

## Requesting an IP Address

If your organization uses IP requests:

1. Go to **Network > Request an IP address**
2. Fill out the request form
3. Submit for approval
4. Administrator will assign and notify you

## Best Practices

### Naming Conventions

Use consistent naming for easier searching:

| Type | Convention | Example |
|------|------------|---------|
| Workstation | `username-device` | `jsmith-laptop` |
| Server | `function-number` | `webserver-01` |
| Printer | `location-type` | `lobby-printer` |
| Network device | `type-location` | `switch-floor2` |

### Documentation

Include relevant information:
- Primary user or purpose
- Physical location
- Team responsible
- Any special notes

### Keeping Records Current

- Update when devices are moved
- Clear records when devices are decommissioned
- Note temporary assignments

## Special Addresses

Some addresses have special purposes:

| Address | Purpose |
|---------|---------|
| First in subnet | Network address (not assignable) |
| Last in subnet | Broadcast address (not assignable) |
| .1 | Often used for gateway |
| .2-.10 | Often reserved for network equipment |

Your organization may have specific conventions.

## Troubleshooting

### Can't modify an IP?

- Check if you have permission for that subnet
- Verify you're logged in
- Contact your administrator

### IP shows assigned but device is gone?

- Update the record to release the IP
- Or mark as "decommissioned" in description
- Clean up during regular maintenance

### Need an IP in a full subnet?

- Check for unused assignments that can be released
- Use Find Free Space to locate alternatives
- Request a new subnet from your administrator
