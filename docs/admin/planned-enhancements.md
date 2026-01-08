# Planned Enhancements

This document outlines planned features and improvements for IPplan.

---

## Native LDAP Integration

**Status:** Planned
**Priority:** High

### Overview

IPplan currently supports LDAP authentication through Apache modules (mod_auth_ldap). We plan to implement native LDAP integration similar to Drupal's LDAP module, providing a more seamless experience for enterprise environments.

### Planned Features

#### LDAP Service Account Configuration

- Configure an LDAP "check account" (bind DN) for directory queries
- Support for Active Directory, OpenLDAP, and other LDAP-compatible directories
- Secure credential storage for the service account
- Connection testing from the admin interface

#### Automatic User Provisioning

- **First-login auto-creation**: When a user authenticates via LDAP for the first time, their IPplan account is automatically created
- User attributes (email, display name) populated from LDAP
- No manual account creation required for LDAP users

#### LDAP Group Synchronization

- Map LDAP groups to IPplan groups
- **Group filtering**: Only sync groups matching specific patterns:
  - `Ent-IPAM-*` - Enterprise IPAM groups
  - `Org-*` - Organization groups
- **Auto-create groups**: If a user is a member of a matching LDAP group that doesn't exist in IPplan, automatically create it
- Configurable group prefix filters via config.php:
  ```php
  define("LDAP_GROUP_PREFIXES", ['Ent-IPAM-', 'Org-']);
  ```

#### Configuration Options

Planned config.php settings:

```php
// LDAP Connection
define("LDAP_ENABLED", TRUE);
define("LDAP_SERVER", 'ldap://ldap.example.com');
define("LDAP_PORT", 389);
define("LDAP_USE_TLS", TRUE);

// Bind Account (service account for queries)
define("LDAP_BIND_DN", 'cn=ipplan-svc,ou=Service Accounts,dc=example,dc=com');
define("LDAP_BIND_PASSWORD", 'service_account_password');

// User Search
define("LDAP_USER_BASE_DN", 'ou=Users,dc=example,dc=com');
define("LDAP_USER_FILTER", '(&(objectClass=person)(sAMAccountName=%username%))');
define("LDAP_USER_ATTR_USERNAME", 'sAMAccountName');
define("LDAP_USER_ATTR_EMAIL", 'mail');
define("LDAP_USER_ATTR_DISPLAYNAME", 'displayName');

// Group Synchronization
define("LDAP_GROUP_SYNC", TRUE);
define("LDAP_GROUP_BASE_DN", 'ou=Groups,dc=example,dc=com');
define("LDAP_GROUP_PREFIXES", ['Ent-IPAM-', 'Org-']);
define("LDAP_GROUP_AUTO_CREATE", TRUE);

// User Provisioning
define("LDAP_AUTO_CREATE_USERS", TRUE);
```

### Implementation Notes

This enhancement mirrors the behavior of Drupal's LDAP module, which has proven effective for enterprise deployments. Key aspects:

1. **Authentication flow**:
   - User enters credentials
   - IPplan binds to LDAP using service account
   - Searches for user DN
   - Attempts bind with user credentials
   - On success, syncs user/group data

2. **Group sync logic**:
   - Query user's LDAP group memberships
   - Filter to groups matching configured prefixes
   - Create missing IPplan groups
   - Update user's IPplan group memberships

3. **Graceful degradation**:
   - If LDAP unavailable, fall back to local auth for admin account
   - Log LDAP connection issues
   - Cache group memberships to reduce LDAP queries

### Migration Path

For environments currently using Apache LDAP:
1. Enable native LDAP alongside Apache auth
2. Test with pilot users
3. Disable Apache LDAP auth
4. Enable auto-provisioning

---

## API Enhancements

**Status:** Under Consideration

### REST API

Modern REST API for programmatic access:
- JSON responses
- Token-based authentication
- CRUD operations for subnets and IPs
- Bulk operations support

### Webhook Support

- Notify external systems on changes
- Integration with ticketing systems
- Automation triggers

---

## UI Modernization

**Status:** Under Consideration

### Planned Improvements

- Responsive design for mobile access
- Modern JavaScript framework integration
- Improved search with auto-complete
- Dashboard with utilization charts

---

## IPv6 Improvements

**Status:** Under Consideration

### Planned Features

- Full IPv6 subnet management
- Dual-stack views
- IPv6 address notation improvements

---

## Feature Requests

Have a feature request? Document it and submit for consideration. Priority is given to:

1. Security improvements
2. Enterprise integration (LDAP, SSO)
3. Usability improvements
4. Scalability enhancements

---

*Last updated: January 2026*
