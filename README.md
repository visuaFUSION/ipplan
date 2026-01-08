# IPplan

**A modernized continuation of IPplan for PHP 8.x**

IPplan is a web-based, multilingual IP address management (IPAM) and tracking tool, updated to run on modern PHP environments.  This project revives and maintains the classic IPplan codebase for organizations that depend on this proven IPAM solution.

---

## About IPplan

IPplan is a web-based, multilingual IP address management (IPAM) and tracking tool.  Originally written for PHP 4, IPplan has served network administrators worldwide for over two decades.  It goes beyond simple IP address management to include DNS administration, configuration file management, circuit management, and hardware asset tracking -- all customizable via templates.

### Key Features

- Multi-customer/multi-network IP address management with overlapping address space support
- DNS administration (forward and reverse zones)
- Device configuration file management
- Circuit and asset tracking via customizable templates
- Multiple authentication methods (internal or external Apache modules including LDAP)
- Audit logging with before/after change tracking
- SWIP/registrar integration for ISPs
- External poller for subnet scanning via NMAP
- IP address request system for end-user self-service
- Multilingual support

---

## Project History

### Origins (2001)

IPplan was created by **Richard Ellerbrock** (SourceForge username: richarde) and first released in 2001.  The project was originally named "IPtrack" before being renamed to "IPplan" on October 31, 2001.  Richard developed and maintained the project through its formative years, responding to user feedback and building out the comprehensive feature set that made IPplan a go-to solution for IP address management.

The earliest changelog entries date back to August 2001, with features being added rapidly through 2001-2002 including SWIP support, bulk subnet creation, import/export capabilities, and the foundation of the multi-customer architecture.

Richard continued active development through the 2000s, adding major features like:

- Multilingual/internationalization support (2002)
- XML-RPC API (2003)
- DNS administration capabilities
- Template system for customization
- IPv6 beta support (2010)

### Final Upstream Releases (2009-2011)

The last official releases from the original project were:

- **v4.92a** (August 2009) - Added PHP 5.3 compatibility
- **v4.92b** (July 2011) - Fixed MySQL 5.5 compatibility issues (ENGINE= vs TYPE=)

After v4.92b, the project became dormant on SourceForge.  The original project remains available at:

- SourceForge: https://sourceforge.net/projects/iptrack/
- Project Homepage: http://iptrack.sourceforge.net/

### GitHub Archive (2016)

In January 2016, **Shaun Bugler** of Hetzner (Pty) Ltd (now **xneelo**) imported IPplan v4.92b to GitHub for archival purposes.  xneelo is a South African web hosting company (formerly known as Hetzner South Africa, rebranded in 2019) that has hosted hundreds of thousands of websites since 1999.  The GitHub repository preserved the codebase but saw no active development.

- GitHub Archive: https://github.com/xneelo/ipplan

### This Fork (2025)

After more than 13 years without updates, the original IPplan codebase would no longer run on modern PHP versions.  **visuaFUSION LLC**, a healthcare IT managed services provider based in Nebraska, needed a working IPAM solution and chose to modernize IPplan rather than abandon it.

This fork updates IPplan to support PHP 8.2 and modern MySQL/MariaDB while preserving the functionality that made the original so valuable.

---

## What's New in This Fork

### Versioning

IPplan-NG uses a date-based versioning system: **YYYY.M.D.R** (Year.Month.Day.Revision)

- Current Release Candidate: **2026.1.8.2**

This versioning approach provides clear indication of when a release was made and allows for multiple releases on the same day if needed.

### PHP 8.x Compatibility

- Updated deprecated function calls and syntax
- Converted `var` declarations to `public` visibility
- Converted old-style constructors to `__construct()`
- Removed deprecated reference parameters
- Fixed array/string access patterns
- Resolved nullable parameter issues
- Updated session handling
- Fixed IIS/Windows compatibility issues

### Database Compatibility

- Full MySQL 8.x / MariaDB 10.x support
- Updated from deprecated `mysql` driver to `mysqli`
- Updated SQL syntax for modern database engines

### Dependency Updates

- Updated ADOdb to v5.22.8
- Updated PHPLayersMenu for modern browsers
- Updated Net/DNS library for PHP 8
- Modern SNMP handling

---

## Requirements

- PHP 8.0 or higher (8.2+ recommended)
- MySQL 8.x / MariaDB 10.x (recommended) or PostgreSQL
- Apache web server with mod_php or IIS with PHP
- SNMP extension (optional, for router table imports)

---

## Installation

Please refer to the INSTALL file for detailed installation instructions.

Quick start:

1. Extract files to your web server document root
2. Copy `config.php` and edit database connection settings
3. Set `DBF_TYPE` to `'mysqli'` (required for PHP 7+)
4. Navigate to `/admin/install.php` to initialize the database
5. Log in with the admin credentials configured in `config.php`

---

## Upgrading from v4.92b

1. Backup your existing database and `config.php`
2. Replace all files with the new version
3. Update your `config.php`:
   - Change `DBF_TYPE` from `'mysql'` or `'maxsql'` to `'mysqli'`
   - Keep your existing database credentials and custom settings
4. Access the application - schema upgrades are automatic if needed

---

## Documentation

The original IPplan documentation remains largely applicable.  Key resources:

- User Manual: Included with distribution (README.html)
- Original SourceForge Forums: https://sourceforge.net/p/iptrack/discussion/

---

## Contributing

Contributions are welcome!  This project exists to keep IPplan alive for the community.

- Report issues via GitHub Issues
- Submit pull requests for bug fixes or enhancements
- Help with testing on various PHP/database configurations

---

## License

IPplan is released under the **GNU General Public License (GPL)**.  See the LICENSE file for details.

The original documentation is copyrighted (c) 2002 Richard E and distributed under the Linux Documentation Project (LDP) license.

---

## Credits

### Original IPplan

- **Richard Ellerbrock** - Original author and maintainer (2001-2011)
- ValueHunt Inc. - Layout class
- ADOdb - Database abstraction
- PHP Layers Menu System - Menu rendering
- All translators and contributors listed in the original documentation

### This Fork

- **visuaFUSION LLC** - PHP 8.x modernization and ongoing maintenance
- xneelo (formerly Hetzner SA) - GitHub archival of v4.92b

---

## About visuaFUSION

visuaFUSION Systems Solutions is a healthcare IT company based in Sutherland, Nebraska, specializing in managed services for rural healthcare organizations.  Our mission is "Leveling the IT Playing Field for Rural Health Care Organizations."

- Website: https://visuafusion.com

---

## See Also

- Original SourceForge Project: https://sourceforge.net/projects/iptrack/
- xneelo GitHub Archive: https://github.com/xneelo/ipplan
