<?php

// IPplan Help Documentation Viewer
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

// Authenticate user (same as user pages)
$auth = new SQLAuthenticator(REALM, REALMERROR);
$grps = $auth->authenticate();

// set language
isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);

// Initialize database connection for permission checking
$ds = new IPplanDbf();

// Get authenticated username
$username = getAuthUsername();

// Check if user has admin documentation access
$canViewAdminDocs = false;

// Static admin always has access
if (defined('ADMINUSER') && $username === ADMINUSER) {
    $canViewAdminDocs = true;
} elseif (!defined('RESTRICT_ADMIN_DOCS') || !RESTRICT_ADMIN_DOCS) {
    // If restriction is disabled, everyone can see admin docs
    $canViewAdminDocs = true;
} else {
    // Check if user has createcust permission (can create/modify/delete customers)
    if ($ds->TestCustomerCreate($username)) {
        $canViewAdminDocs = true;
    }
}

// Get the requested documentation section and page
$section = isset($_GET['section']) ? $_GET['section'] : '';
$page = isset($_GET['page']) ? $_GET['page'] : 'index';

// Handle legacy URLs (no section specified)
if (empty($section)) {
    // Default to user section
    $section = 'user';
}

// Sanitize inputs
$section = preg_replace('/[^a-zA-Z0-9_-]/', '', $section);
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Validate section
$validSections = array('admin', 'user');
if (!in_array($section, $validSections)) {
    $section = 'user';
}

// Check permission for admin section
if ($section === 'admin' && !$canViewAdminDocs) {
    // Redirect to user section if not permitted
    $section = 'user';
    $page = 'index';
}

// Build the file path
$docPath = __DIR__ . '/docs/' . $section . '/' . $page . '.md';

// Check if file exists, fall back to section index
if (!file_exists($docPath)) {
    $docPath = __DIR__ . '/docs/' . $section . '/index.md';
    $page = 'index';
}

// Final fallback to user index
if (!file_exists($docPath)) {
    $section = 'user';
    $page = 'index';
    $docPath = __DIR__ . '/docs/user/index.md';
}

// Read the markdown content
$markdownContent = file_get_contents($docPath);

// Escape for JavaScript
$markdownContent = json_encode($markdownContent);

// Define navigation structure
$adminNavItems = array(
    'index' => 'Overview',
    'installation' => 'Installation',
    'configuration' => 'Configuration',
    'user-management' => 'User Management',
    'import-export' => 'Import/Export',
    'maintenance' => 'Maintenance',
    'upgrading' => 'Upgrading',
    'custom-branding' => 'Custom Branding',
    'planned-enhancements' => 'Planned Enhancements',
    'troubleshooting' => 'Troubleshooting',
    'faq' => 'Admin FAQ'
);

$userNavItems = array(
    'index' => 'Overview',
    'getting-started' => 'Quick Start',
    'subnets' => 'Managing Subnets',
    'ip-addresses' => 'IP Addresses',
    'dns' => 'DNS Records',
    'faq' => 'User FAQ'
);

// Build navigation HTML
$navHtml = '<div class="help-nav">';

// User documentation section (always visible)
$navHtml .= '<h3><a href="help.php?section=user&page=index" class="nav-section-link">Using IPplan</a></h3>';
$navHtml .= '<ul>';
foreach ($userNavItems as $navPage => $navTitle) {
    $isActive = ($section === 'user' && $navPage === $page);
    $activeClass = $isActive ? ' class="active"' : '';
    $navHtml .= '<li' . $activeClass . '><a href="help.php?section=user&page=' . htmlspecialchars($navPage) . '">' . htmlspecialchars($navTitle) . '</a></li>';
}
$navHtml .= '</ul>';

// Admin documentation section (only if permitted)
if ($canViewAdminDocs) {
    $navHtml .= '<h3 style="margin-top: 20px;"><a href="help.php?section=admin&page=index" class="nav-section-link">System Administration</a></h3>';
    $navHtml .= '<ul>';
    foreach ($adminNavItems as $navPage => $navTitle) {
        $isActive = ($section === 'admin' && $navPage === $page);
        $activeClass = $isActive ? ' class="active"' : '';
        $navHtml .= '<li' . $activeClass . '><a href="help.php?section=admin&page=' . htmlspecialchars($navPage) . '">' . htmlspecialchars($navTitle) . '</a></li>';
    }
    $navHtml .= '</ul>';
}

$navHtml .= '</div>';

// Create the page
newhtml($p);
$w = myheading($p, my_("Help Documentation"));

// Add CSS for help layout - uses CSS variables for theme compatibility
$css = <<<'CSS'
<style>
.help-container {
    display: flex;
    gap: 20px;
    margin: 10px;
}
.help-nav {
    min-width: 220px;
    background: var(--bg-card, #f5f5f5);
    padding: 15px;
    border-radius: var(--radius-lg, 5px);
    border: 1px solid var(--border-color, #ddd);
}
.help-nav h3 {
    margin-top: 0;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color, #ccc);
    color: var(--text-primary, #333);
    font-size: 14px;
}
.help-nav h3 a.nav-section-link {
    color: var(--text-primary, #333);
    text-decoration: none;
}
.help-nav h3 a.nav-section-link:hover {
    color: var(--accent-primary, #0066cc);
    text-decoration: underline;
}
.help-nav ul {
    list-style: none;
    padding: 0;
    margin: 0 0 15px 0;
}
.help-nav li {
    padding: 4px 0;
}
.help-nav li a {
    color: var(--accent-light, #0066cc);
    text-decoration: none;
    font-size: 13px;
}
.help-nav li a:hover {
    text-decoration: underline;
}
.help-nav li.active a {
    font-weight: bold;
    color: var(--text-primary, #333);
}
.help-content {
    flex: 1;
    background: var(--bg-card, #fff);
    padding: 20px;
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius-lg, 5px);
    overflow-x: auto;
    color: var(--text-primary, #333);
}
.help-content h1 {
    margin-top: 0;
    color: var(--text-primary, #333);
    border-bottom: 2px solid var(--accent-primary, #0066cc);
    padding-bottom: 10px;
}
.help-content h2 {
    color: var(--text-primary, #444);
    margin-top: 25px;
    border-bottom: 1px solid var(--border-color, #ddd);
    padding-bottom: 5px;
}
.help-content h3 {
    color: var(--text-secondary, #555);
    margin-top: 20px;
}
.help-content p {
    color: var(--text-secondary, #333);
}
.help-content pre {
    background: var(--bg-card-alt, #f8f8f8);
    border: 1px solid var(--border-color, #ddd);
    border-radius: var(--radius-md, 4px);
    padding: 10px;
    overflow-x: auto;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: var(--text-primary, #333);
}
.help-content code {
    background: var(--bg-card-alt, #f0f0f0);
    padding: 2px 5px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    color: var(--text-primary, #333);
}
.help-content pre code {
    background: transparent;
    padding: 0;
}
.help-content table {
    border-collapse: collapse;
    width: 100%;
    margin: 15px 0;
}
.help-content th, .help-content td {
    border: 1px solid var(--border-color, #ddd);
    padding: 8px 12px;
    text-align: left;
    color: var(--text-secondary, #333);
}
.help-content th {
    background: var(--bg-card-alt, #f5f5f5);
    color: var(--text-primary, #333);
}
.help-content tr:nth-child(even) {
    background: var(--bg-hover, #fafafa);
}
.help-content a {
    color: var(--accent-light, #0066cc);
}
.help-content ul, .help-content ol {
    padding-left: 25px;
    color: var(--text-secondary, #333);
}
.help-content li {
    margin: 5px 0;
}
.help-content blockquote {
    border-left: 4px solid var(--accent-primary, #0066cc);
    margin: 15px 0;
    padding: 10px 20px;
    background: var(--bg-card-alt, #f9f9f9);
    color: var(--text-secondary, #333);
}
.help-content hr {
    border: none;
    border-top: 1px solid var(--border-color, #ddd);
    margin: 20px 0;
}
</style>
CSS;

// Add marked.js library for markdown parsing
$markedJs = <<<'SCRIPT'
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
SCRIPT;

// Current section for JavaScript link processing
$currentSection = htmlspecialchars($section);

// Build the content
$content = $css . $markedJs;
$content .= '<div class="help-container">';
$content .= $navHtml;
$content .= '<div class="help-content" id="help-content"></div>';
$content .= '</div>';

// Add script to render markdown
$content .= '<script>
document.addEventListener("DOMContentLoaded", function() {
    var markdown = ' . $markdownContent . ';
    var currentSection = "' . $currentSection . '";

    // Configure marked
    if (typeof marked !== "undefined") {
        marked.setOptions({
            gfm: true,
            breaks: false,
            tables: true
        });

        // Process internal links to use help.php
        var renderer = new marked.Renderer();
        renderer.link = function(href, title, text) {
            // Check if href is a parameter object (marked v5+)
            var linkHref = href;
            var linkTitle = title;
            var linkText = text;

            if (typeof href === "object") {
                linkHref = href.href || "";
                linkTitle = href.title || "";
                linkText = href.text || "";
            }

            // Convert relative .md links to help.php links
            if (linkHref) {
                // Handle ../section/page.md format
                var crossSectionMatch = linkHref.match(/^\.\.\/(admin|user)\/([a-zA-Z0-9_-]+)\.md$/);
                if (crossSectionMatch) {
                    linkHref = "help.php?section=" + crossSectionMatch[1] + "&page=" + crossSectionMatch[2];
                }
                // Handle simple page.md format (same section)
                else if (linkHref.match(/^[a-zA-Z0-9_-]+\.md$/)) {
                    var pageName = linkHref.replace(".md", "");
                    linkHref = "help.php?section=" + currentSection + "&page=" + pageName;
                }
                // Handle page.md#anchor format
                else if (linkHref.match(/^[a-zA-Z0-9_-]+\.md#/)) {
                    var parts = linkHref.split("#");
                    var pageName = parts[0].replace(".md", "");
                    var anchor = parts[1];
                    linkHref = "help.php?section=" + currentSection + "&page=" + pageName + "#" + anchor;
                }
            }

            // Handle external links
            var isExternal = linkHref && (linkHref.indexOf("http://") === 0 || linkHref.indexOf("https://") === 0);
            var target = isExternal ? " target=\"_blank\" rel=\"noopener\"" : "";

            return "<a href=\"" + linkHref + "\"" + (linkTitle ? " title=\"" + linkTitle + "\"" : "") + target + ">" + linkText + "</a>";
        };

        marked.setOptions({ renderer: renderer });

        document.getElementById("help-content").innerHTML = marked.parse(markdown);
    } else {
        // Fallback if marked.js fails to load
        document.getElementById("help-content").innerHTML = "<pre>" + markdown + "</pre>";
    }
});
</script>';

insert($w, block($content));
printhtml($p);

?>
