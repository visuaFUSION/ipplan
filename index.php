<?php

// IPplan v4.92b
// Aug 24, 2001
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
//

require_once("config.php");
require_once("schema.php");
require_once("ipplanlib.php");
require_once("layout/class.layout");

// check for latest variable added to config.php file, if not there
// user did not upgrade properly
if (!defined("CONFIG_DIR")) die("Your config.php file is inconsistent - you cannot use your old config.php file during upgrade");

// set language
isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);

CheckSchema();

newhtml($p);
insert($p,block("<script type=\"text/javascript\">
</script>
<noscript>
<p><b>
<font size=4 color=\"#FF0000\">
Your browser must be JavaScript capable to use this application. Please turn JavaScript on.
</font>
</b>
</noscript>
"));

$w=myheading($p,my_("Main Menu"));

// Load main menu content from markdown file
$mainMenuMdPath = __DIR__ . '/docs/main-menu.md';
if (file_exists($mainMenuMdPath)) {
    $markdownContent = file_get_contents($mainMenuMdPath);
    $markdownJson = json_encode($markdownContent);

    // Add marked.js and render the markdown content
    $mainMenuHtml = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<div id="main-menu-content" class="main-menu-content"></div>
<script>
(function() {
    var markdown = {$markdownJson};
    if (typeof marked !== "undefined") {
        marked.setOptions({
            breaks: true,
            gfm: true
        });
        document.getElementById("main-menu-content").innerHTML = marked.parse(markdown);
    } else {
        document.getElementById("main-menu-content").innerHTML = "<pre>" + markdown + "</pre>";
    }
})();
</script>
HTML;
    insert($w, block($mainMenuHtml));
} else {
    // Fallback if markdown file doesn't exist
    insert($w, block("<p>".my_("Welcome to IPplan - IP Address Management")."</p>"));
}

printhtml($p);
?> 
