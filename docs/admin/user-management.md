# User Management

IPplan uses a group-based permission system designed for multi-tenant environments like MSPs managing multiple clients.

## Permission Model Overview

```
Users
  └── belong to → Groups
                    ├── have → Permissions (create customers, view all)
                    ├── have → Authority Boundaries (IP ranges)
                    └── are assigned to → Customers
```

## Managing Users

### Creating Users

1. Go to **Admin > Users > Create a new User**
2. Enter user details:
   - **Username**: Login name (case-sensitive)
   - **Password**: Initial password
   - **Email**: Contact email
   - **Description**: Full name or role
3. Click Create

### Editing Users

1. Go to **Admin > Users > Display/Edit Users**
2. Select the user to modify
3. Available actions:
   - Change password
   - Update email/description
   - View group memberships

### Disabling Users

To disable a user without deleting:
1. Remove them from all groups
2. Or change their password and notify them

## Managing Groups

Groups control what users can see and do.

### Creating Groups

1. Go to **Admin > Groups > Create a new Group**
2. Enter:
   - **Group Name**: Identifier (e.g., "acme_admins")
   - **Description**: Purpose of the group
3. Click Create

### Group Permissions

Each group has two key permission settings:

| Permission | Effect |
|------------|--------|
| **Can create/modify/delete customers** | Full customer management |
| **Can see all customers** | Override customer-group restrictions |

To modify:
1. Go to **Admin > Users > Display/Edit Users**
2. Select group to edit
3. Toggle permissions as needed

### Adding Users to Groups

1. Go to **Admin > Users > Add a user to Group**
2. Select the user
3. Select group(s) to add
4. Click Add

A user can belong to multiple groups - permissions are combined.

## Authority Boundaries

Authority boundaries restrict which IP address ranges a group can manage.

### How Boundaries Work

- **No boundaries defined**: Group can access all IP ranges
- **Boundaries defined**: Group can ONLY access specified ranges
- **Read-only boundary**: Group can view but not modify

### Setting Boundaries

1. Go to **Admin > Groups > Add Authority Boundaries to Group**
2. Select the group
3. Enter boundary:
   - **Start Address**: Beginning of IP range
   - **Size**: Number of addresses
4. Click Add

**Example:**
```
Group: "engineering_team"
Boundaries:
  - 10.0.0.0 / 256 addresses (/24)
  - 10.1.0.0 / 256 addresses (/24)
Result: Can only manage IPs within these two /24 blocks
```

### Viewing Boundaries

**Admin > Groups > Display/Modify Authority Boundary Info**

## Customer-Group Association

Each customer is assigned to an "admin group". Users only see customers where:
- Their group matches the customer's admin group, OR
- They have the "can see all customers" permission

### MSP/Multi-Tenant Setup Example

**Scenario**: MSP with three clients: Acme, TechCorp, and BigCo

**Groups:**
```
msp_admins     - Can see all customers, can create customers
acme_users     - Regular group for Acme staff
techcorp_users - Regular group for TechCorp staff
bigco_users    - Regular group for BigCo staff
```

**Customers:**
```
Acme Corp     - Admin Group: acme_users
TechCorp Inc  - Admin Group: techcorp_users
BigCo LLC     - Admin Group: bigco_users
```

**Users:**
```
msp_admin1    - Member of: msp_admins
              → Can see: All customers

acme_john     - Member of: acme_users
              → Can see: Only Acme Corp

techcorp_jane - Member of: techcorp_users
              → Can see: Only TechCorp Inc
```

## Static Admin Account

The admin account defined in config.php always has full access:

```php
define("ADMINUSER", 'admin');
define("ADMINPASSWD", 'password');
```

This account:
- Bypasses all group restrictions
- Can always access admin functions
- Cannot be disabled via the UI
- Should use a very strong password

## Authentication Methods

### Internal Authentication

Default method - users authenticate against IPplan's database.

```php
define("AUTH_INTERNAL", TRUE);
```

### External Authentication (Apache/LDAP)

IPplan can use Apache's authentication modules:

```php
define("AUTH_INTERNAL", FALSE);
define("AUTH_VAR", 'REMOTE_USER');
```

With external auth:
- Apache handles authentication (LDAP, etc.)
- Users must still exist in IPplan's database
- Group memberships managed in IPplan

See [Planned Enhancements](planned-enhancements.md) for upcoming native LDAP integration.

## Best Practices

### Security
- Use strong passwords
- Regularly audit group memberships
- Remove access promptly when users leave
- Use authority boundaries to limit blast radius

### Organization
- Name groups consistently (e.g., `{client}_users`, `{client}_admins`)
- Document group purposes in descriptions
- Review permissions quarterly

### Multi-Tenant
- Create separate groups for each tenant
- Assign appropriate boundaries
- Test visibility before giving client access
- Consider read-only groups for auditors
