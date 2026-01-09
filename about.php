<?php

// IPplan About Page
// PHP 8.x compatible
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.

require_once("ipplanlib.php");
require_once("adodb/adodb.inc.php");
require_once("class.dbflib.php");
require_once("layout/class.layout");
require_once("auth.php");

// Authenticate user
$auth = new SQLAuthenticator(REALM, REALMERROR);
$grps = $auth->authenticate();

// set language
isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);

// Get version
$version = defined('IPPLAN_VERSION') ? IPPLAN_VERSION : 'Unknown';

// Get configured URLs with fallbacks
$issueTrackerName = defined('ISSUE_TRACKER_NAME') ? ISSUE_TRACKER_NAME : 'GitHub';
$issueTrackerUrl = defined('ISSUE_TRACKER_URL') ? ISSUE_TRACKER_URL : 'https://github.com/visuafusion/ipplan/issues';
$discussionsUrl = defined('DISCUSSIONS_URL') ? DISCUSSIONS_URL : 'https://github.com/visuafusion/ipplan/discussions';
$historyUrl = defined('PROJECT_INFO_HISTORY') ? PROJECT_INFO_HISTORY : '#';

// Create the page
newhtml($p);
$w = myheading($p, my_("About IPplan"));

// Build the about content - uses CSS variables for theme compatibility
$content = <<<HTML
<style>
.about-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius-lg, 5px);
    color: var(--text-primary, #333);
}
.about-header {
    text-align: center;
    border-bottom: 2px solid var(--accent-primary, #0066cc);
    padding-bottom: 20px;
    margin-bottom: 20px;
}
.about-header h1 {
    color: var(--text-primary, #333);
    margin: 0 0 10px 0;
}
.about-version {
    font-size: 18px;
    color: var(--text-secondary, #666);
}
.about-section {
    margin: 20px 0;
}
.about-section h2 {
    color: var(--text-primary, #444);
    border-bottom: 1px solid var(--border-color, #ddd);
    padding-bottom: 5px;
    margin-bottom: 10px;
}
.about-section p {
    line-height: 1.6;
    color: var(--text-secondary, #555);
}
.about-section ul {
    color: var(--text-secondary, #555);
    padding-left: 25px;
}
.about-section li {
    margin: 5px 0;
}
.about-links {
    background: var(--bg-card-alt, #f9f9f9);
    padding: 15px;
    border-radius: var(--radius-lg, 5px);
    margin-top: 20px;
    border: 1px solid var(--border-color, #eee);
}
.about-links h3 {
    margin-top: 0;
    color: var(--text-primary, #333);
}
.about-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.about-links li {
    padding: 8px 0;
    border-bottom: 1px solid var(--border-color, #eee);
    color: var(--text-secondary, #555);
}
.about-links li:last-child {
    border-bottom: none;
}
.about-links a {
    color: var(--accent-light, #0066cc);
    text-decoration: none;
}
.about-links a:hover {
    text-decoration: underline;
}
.about-section a {
    color: var(--accent-light, #0066cc);
    text-decoration: none;
}
.about-section a:hover {
    text-decoration: underline;
}
.about-credits {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid var(--border-color, #ddd);
    font-size: 13px;
    color: var(--text-muted, #777);
}
</style>

<div class="about-container">
    <div class="about-header">
        <h1>IPplan Current Branch</h1>
        <div class="about-version">Version {$version}</div>
    </div>

    <div class="about-section">
        <h2>About IPplan Current Branch</h2>
        <p>
            IPplan Current Branch is a web-based, multilingual IP address management (IPAM) and
            tracking tool currently maintained by visuaFUSION Systems Solutions, a health care
            IT company dedicated to helping rural hospitals, clinics, and long term care facilities
            achieve HIPAA compliant, enterprise-grade IT operations with rural health care scale and budgets.
        </p>
        <p>
            Originally created by Richard Ellerbrock in 2001, IPplan provides comprehensive
            features for managing IP addresses, subnets, DNS zones, and network documentation
            across multiple customers and organizations.
        </p>
        <p>
            IPplan Current Branch was started to breathe new life into this proven IPAM platform
            after more than a decade of stagnation with the original project. Thanks to the power
            of open-source software, we were able to revive IPplan, modernize its codebase, and
            bring it up to current security standards. This gives rural health care organizations
            and other budget-conscious IT teams a reliable, feature-rich IPAM solution without
            the high licensing costs typically associated with commercial IP address management software.
        </p>
        <p>
            For a more detailed project history, see <a href="{$historyUrl}" target="_blank">Project History</a>.
        </p>
    </div>

    <div class="about-section">
        <h2>Key Features</h2>
        <ul>
            <li>Multi-customer/multi-tenant IP address management</li>
            <li>Overlapping address space support</li>
            <li>DNS zone administration (forward and reverse)</li>
            <li>Audit logging with change tracking</li>
            <li>Customizable templates</li>
            <li>Import/export capabilities</li>
            <li>Multiple authentication methods</li>
        </ul>
    </div>

    <div class="about-links">
        <h3>Resources</h3>
        <ul>
            <li><a href="{$historyUrl}" target="_blank">Project History</a> - Learn about IPplan's origins and development</li>
            <li><a href="{$discussionsUrl}" target="_blank">Community Discussions</a> - Get help and connect with other users</li>
            <li><a href="{$issueTrackerUrl}" target="_blank">Report an Issue</a> - Submit bug reports on {$issueTrackerName}</li>
        </ul>
    </div>

    <div class="about-credits">
        <p>
            Originally created by Richard Ellerbrock (2001). Current Branch maintained by
            visuaFUSION Systems Solutions with modern PHP compatibility, security hardening,
            modern UI, and enhanced features.
        </p>
        <p>
            IPplan is free software released under the GNU General Public License (GPL).
        </p>
    </div>
</div>
HTML;

insert($w, block($content));
printhtml($p);

?>
