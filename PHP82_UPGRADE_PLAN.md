# IPPlan PHP 8.2 Upgrade Plan

## Overview

Upgrade IPPlan v4.92b from PHP 4 to PHP 8.2 while maintaining full database compatibility.

**Scope:** ~263 PHP files, ~72,000 lines of code
**Primary Goal:** PHP 8.2 compatibility
**Critical Constraint:** Maintain compatibility with existing databases

---

## Phase 1: Fix Version Gates and Immediate Blockers

**Files:** `schema.php`, `ipplanlib.php`

### 1.1 Remove PHP Version Blocking (schema.php:31-36)

Current code blocks PHP 6+:
```php
if (phpversion() < "4.1.0") {
    die("You need php version 4.1.0 or later");
}
if (phpversion() >= "6") {
    die("This version of IPplan will not work with PHP 6.x");
}
```

**Action:** Update to require PHP 8.0+ minimum:
```php
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die("You need PHP version 8.0.0 or later");
}
```

### 1.2 Remove Magic Quotes Handling (ipplanlib.php:42-44, 62-64)

`get_magic_quotes_gpc()` and `set_magic_quotes_runtime()` removed in PHP 5.4+.

**Action:** Remove magic quotes checks entirely - they no longer exist.

### 1.3 Fix Version-Conditional Error Reporting (ipplanlib.php:52-57)

**Action:** Simplify to PHP 8.2 compatible error reporting.

---

## Phase 2: Update ADODB Library

**Directory:** `adodb/` (currently v4.54 from 2004)

### 2.1 Download and Install Modern ADODB

**Action:** Replace with ADODB 5.22+ which supports PHP 8.x

**Critical:** The ADODB API is largely backward compatible, so existing code using:
- `ADONewConnection()`
- `$ds->Execute()`
- `$ds->FetchRow()`
- `$ds->qstr()`
- Transactions (BeginTrans, CommitTrans, RollBackTrans)

...will continue to work.

### 2.2 Verify Database Compatibility

**Databases to test:**
- MySQL/MariaDB (maxsql driver)
- PostgreSQL (postgres7 driver)
- Oracle (oci8po driver)
- MSSQL (various drivers)

---

## Phase 3: Convert `var` to Visibility Modifiers

**Files affected:** 102 files

### 3.1 Core Class Files

| File | Classes |
|------|---------|
| `auth.php` | BasicAuthenticator, SQLAuthenticator |
| `class.dbflib.php` | IPplanDbf |
| `class.templib.php` | IPplanIPTemplate |
| `class.dnslib.php` | DNSZone and related |
| `class.xptlib.php` | Export classes |
| `xmllib.php` | xml, xmlnmap, myTemplate |
| `ipplanlib.php` | mySearch |
| `layout/class.layout` | Template_PHPLIB, layout classes |
| `menus/lib/PHPLIB.php` | PHPLIB template class |
| `menus/lib/layersmenu*.php` | Menu classes |
| `class.phpmailer.php` | PHPMailer |

### 3.2 ADODB Classes (handled by library update)

The ADODB update in Phase 2 will handle all `var` declarations in `adodb/` directory.

### 3.3 Conversion Rule

```php
// Before
var $property = "value";

// After
public $property = "value";
```

**Note:** All `var` declarations become `public` as this maintains backward compatibility.

---

## Phase 4: Convert Old-Style Constructors to `__construct()`

**Classes requiring conversion:**

### 4.1 Core Classes

| Class | File | Current Constructor |
|-------|------|---------------------|
| `BasicAuthenticator` | auth.php:43 | `function BasicAuthenticator()` |
| `SQLAuthenticator` | auth.php:124 | (inherits, no constructor) |
| `IPplanDbf` | class.dbflib.php:44 | `function IPplanDbf()` |
| `mySearch` | ipplanlib.php:661 | `function mySearch()` |
| `xml` | xmllib.php | `function xml()` |
| `xmlnmap` | xmllib.php | `function xmlnmap()` |
| `IPplanIPTemplate` | class.templib.php | `function IPplanIPTemplate()` |
| `DNSZone` | class.dnslib.php | `function DNSZone()` |

### 4.2 Menu/Layout Classes

| Class | File |
|-------|------|
| `Template_PHPLIB` | layout/class.layout |
| `LayersMenu` | menus/lib/layersmenu.inc.php |
| Various | menus/lib/PHPLIB.php |

### 4.3 Conversion Pattern

```php
// Before
class Foo {
    function Foo($param) {
        // constructor code
    }
}

// After
class Foo {
    public function __construct($param) {
        // constructor code
    }
}
```

---

## Phase 5: Replace Deprecated Regex Functions

### 5.1 `ereg()` and `eregi()` → `preg_match()`

**Files:** 47+ files

```php
// Before
if (ereg("^Basic ", $var))
if (eregi("/user$", $path))

// After
if (preg_match("/^Basic /", $var))
if (preg_match("/\\/user$/i", $path))
```

### 5.2 `eregi_replace()` → `preg_replace()` with `i` flag

**Locations:**
- `ipplanlib.php:469-472` (base_url function)
- `ipplanlib.php:491-492` (base_dir function)

```php
// Before
$tmp = eregi_replace("/user$","",$tmp);

// After
$tmp = preg_replace("/\\/user$/i", "", $tmp);
```

### 5.3 `split()` → `explode()` or `preg_split()`

**Location:** `ipplanlib.php:860,863` (myRegister function)

```php
// Before
$tokens = split(" ", $vars);
list($code, $variable) = split(":", $value);

// After
$tokens = explode(" ", $vars);
list($code, $variable) = explode(":", $value);
```

### 5.4 Known Files Requiring Regex Updates

- `ipplanlib.php`
- `class.dnslib.php`
- `user/displaysubnet.php`
- `user/modifyipform.php`
- `user/modifyzone.php`
- `contrib/ipplan-poller.php`
- `menus/lib/layersmenu*.php`
- And ~40 more files

---

## Phase 6: Remove Magic Quotes Handling

### 6.1 ipplanlib.php (lines 34-49)

Remove the register_globals workaround and magic_quotes handling:

```php
// REMOVE THIS ENTIRE BLOCK:
$types_to_register = array('_GET','_POST','_COOKIE','_SESSION','_SERVER');
foreach ($types_to_register as $type) {
    $arr = @${ $type };
    if (($type=="_GET" or $type=="_POST") and get_magic_quotes_gpc())  {
        $arr=stripslashes_deep($arr);
    }
    if (@count($arr) > 0) {
        extract($arr, EXTR_OVERWRITE);
    }
}
```

**Note:** This is a critical change - the `extract()` call was simulating register_globals. We need to verify all files use `$_GET`, `$_POST`, etc. directly rather than extracted variables.

### 6.2 class.phpmailer.php

Remove magic_quotes_runtime handling.

---

## Phase 7: Replace `each()` with `foreach()`

### 7.1 layout/class.layout

```php
// Before
while( list($key,$val) = each($a) ) {
    $name = "def_" . $key ;
    $GLOBALS["$name"] = $val;
}

// After
foreach($a as $key => $val) {
    $name = "def_" . $key;
    $GLOBALS[$name] = $val;
}
```

---

## Phase 8: Fix Remaining PHP 8.2 Deprecations

### 8.1 Dynamic Properties

PHP 8.2 deprecates dynamic properties on classes. Audit for undeclared property usage.

### 8.2 `${var}` String Interpolation

PHP 8.2 deprecates `${var}` in strings. Use `{$var}` instead.

### 8.3 Passing `null` to Non-Nullable Parameters

Many PHP internal functions no longer accept `null`. Add null checks or use null coalescing.

### 8.4 Reference Assignments

Review `&$var` reference assignments for necessity:
- `$ds = &ADONewConnection(DBF_TYPE);` → `$ds = ADONewConnection(DBF_TYPE);`
- `$result = &$this->ds->Execute(...)` → `$result = $this->ds->Execute(...)`

---

## Phase 9: Testing and Validation

### 9.1 Core Functionality Tests

- [ ] Database connection (MySQL, PostgreSQL)
- [ ] User authentication (internal and external)
- [ ] Session management
- [ ] IP address CRUD operations
- [ ] Subnet management
- [ ] DNS zone management
- [ ] DHCP configuration
- [ ] Import/Export functionality
- [ ] Audit logging

### 9.2 UI/Menu Tests

- [ ] Menu rendering
- [ ] Theme switching
- [ ] Language switching
- [ ] Form submissions
- [ ] Search functionality

### 9.3 Admin Functions

- [ ] User management
- [ ] Password changes
- [ ] Schema upgrades

---

## Implementation Order

1. **Phase 2 first** - Get ADODB updated (foundation for everything)
2. **Phase 1** - Fix version gates so app can load
3. **Phases 3-4** - Syntax fixes (var, constructors)
4. **Phases 5-7** - Function replacements (ereg, split, each)
5. **Phase 6** - Magic quotes removal (careful testing needed)
6. **Phase 8** - PHP 8.2 specific deprecations
7. **Phase 9** - Full testing

---

## Risk Assessment

| Risk | Impact | Mitigation |
|------|--------|------------|
| ADODB compatibility | High | Use official ADODB 5.x which maintains API compatibility |
| Register globals removal | High | Verify all files use superglobals directly |
| Database schema changes | None | No schema changes - only PHP code updates |
| Session handling | Medium | Test authentication thoroughly |
| Third-party libraries | Medium | Update PHPMailer, menu libraries |

---

## Estimated File Changes

| Category | Files | Effort |
|----------|-------|--------|
| Core libraries | 10 | High |
| Admin modules | 16 | Medium |
| User modules | 30+ | Medium |
| Menu/Layout | 15 | Medium |
| ADODB (replacement) | 100+ | Low (drop-in) |
| Contrib scripts | 10+ | Low |

---

## Success Criteria

1. Application loads without errors on PHP 8.2
2. All database operations work unchanged
3. Authentication works (internal and external)
4. All CRUD operations function correctly
5. No PHP deprecation warnings in error log
6. Existing databases work without modification
