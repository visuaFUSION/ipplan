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

// Build the about content
$content = <<<HTML
<style>
.about-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
}
.about-header {
    text-align: center;
    border-bottom: 2px solid #0066cc;
    padding-bottom: 20px;
    margin-bottom: 20px;
}
.about-header h1 {
    color: #333;
    margin: 0 0 10px 0;
}
.about-version {
    font-size: 18px;
    color: #666;
}
.about-section {
    margin: 20px 0;
}
.about-section h2 {
    color: #444;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
    margin-bottom: 10px;
}
.about-section p {
    line-height: 1.6;
    color: #555;
}
.about-links {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}
.about-links h3 {
    margin-top: 0;
    color: #333;
}
.about-links ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.about-links li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}
.about-links li:last-child {
    border-bottom: none;
}
.about-links a {
    color: #0066cc;
    text-decoration: none;
}
.about-links a:hover {
    text-decoration: underline;
}
.about-credits {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #ddd;
    font-size: 13px;
    color: #777;
}
</style>

<div class="about-container">
    <div class="about-header">
        <h1>IPplan</h1>
        <div class="about-version">Version {$version}</div>
    </div>

    <div class="about-section">
        <h2>About IPplan</h2>
        <p>
            IPplan is a web-based, multilingual IP address management (IPAM) and tracking tool
            originally created by Richard Ellerbrock in 2001. It provides comprehensive features
            for managing IP addresses, subnets, DNS zones, and network documentation across
            multiple customers and organizations. For a more detailed project history, see
            <a href="{$historyUrl}" target="_blank">Project History</a>.
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
            Originally created by Richard Ellerbrock (2001). This version maintained by visuaFUSION LLC
            with PHP 8.x compatibility updates.
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
