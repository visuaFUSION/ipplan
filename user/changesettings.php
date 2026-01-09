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

require_once("../config.php");
require_once("../ipplanlib.php");
require_once("../adodb/adodb.inc.php");
require_once("../layout/class.layout");
require_once("../auth.php");

$auth = new SQLAuthenticator(REALM, REALMERROR);

// And now perform the authentication
$grps=$auth->authenticate();

// explicitly cast variables as security measure against SQL injection
list($paranoid, $ipplanParanoid, $poll, $ipplanPoll, $lang, $theme, $rowsperpage) = myRegister("I:paranoid I:ipplanParanoid I:poll I:ipplanPoll S:lang S:theme I:rowsperpage");

// Dashboard card settings
$dashboardCardNames = array(
    'quick_stats' => my_("Quick Stats"),
    'recent_activity' => my_("Recent Activity"),
    'subnet_usage' => my_("Subnet Usage"),
    'quick_actions' => my_("Quick Actions"),
    'system_info' => my_("System Information")
);

// Get current dashboard card settings
// Default: all cards enabled
$dashboardCards = array();
foreach (array_keys($dashboardCardNames) as $cardKey) {
    $dashboardCards[$cardKey] = true;  // Default all to enabled
}
// Override with saved preferences if cookie exists
if (isset($_COOKIE['ipplanDashboardCards'])) {
    $savedCards = json_decode($_COOKIE['ipplanDashboardCards'], true);
    if (is_array($savedCards)) {
        foreach ($savedCards as $cardKey => $enabled) {
            if (array_key_exists($cardKey, $dashboardCards)) {
                $dashboardCards[$cardKey] = (bool)$enabled;
            }
        }
    }
}

// set language
if ($lang) {
    myLanguage($lang.":".dirname(dirname(__FILE__)));
}
else {
    isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);
}

//setdefault("window",array("bgcolor"=>"white"));
//setdefault("table",array("cellpadding"=>"0"));
//setdefault("text",array("size"=>"2"));

$title=my_("Change display settings");
newhtml($p);

$results="";

// Handle reset dashboard cards to defaults
if (isset($_GET['reset_dashboard']) && $_GET['reset_dashboard'] == '1') {
    // Delete the cookie by setting expiry in the past
    setcookie("ipplanDashboardCards", "", time() - 3600, "/");
    unset($_COOKIE['ipplanDashboardCards']);
    // Reset to defaults
    foreach (array_keys($dashboardCardNames) as $cardKey) {
        $dashboardCards[$cardKey] = true;
    }
    $results = my_("Dashboard cards reset to defaults");
}

if ($_POST) {
    setcookie("ipplanTheme",$theme, time() + 10000000, "/");
    // Make change immediate.
    $_COOKIE["ipplanTheme"]=$theme;
    setcookie("ipplanParanoid","$paranoid",time() + 10000000, "/");
    $ipplanParanoid=$paranoid;  // to update display once page submitted
    setcookie("ipplanPoll","$poll",time() + 10000000, "/");
    $ipplanPoll=$poll;  // to update display once page submitted

    // set rows per page cookie if changed by user
    if ($rowsperpage && in_array($rowsperpage, array(64, 128, 256, 512))) {
        setcookie("ipplanRowsPerPage", "$rowsperpage", time() + 10000000, "/");
        $_COOKIE['ipplanRowsPerPage'] = $rowsperpage;
    }

    // set language cookie if language changed by user
    // language includes path of ipplan root seperated by :
    if ($lang) {
        setcookie("ipplanLanguage",$lang.":".dirname(dirname(__FILE__)),time() + 10000000, "/");
        $_COOKIE['ipplanLanguage']=$lang.":".dirname(dirname(__FILE__));
        //isset($_COOKIE["ipplanLanguage"]) && myLanguage($_COOKIE['ipplanLanguage']);
        //isset($_COOKIE["ipplanLanguage"]) && myLanguage($lang.":".dirname(dirname(__FILE__)));
    }

    // Save dashboard card settings
    $newDashboardCards = array();
    foreach (array_keys($dashboardCardNames) as $cardKey) {
        $postKey = 'dashboard_' . $cardKey;
        $newDashboardCards[$cardKey] = isset($_POST[$postKey]) && $_POST[$postKey] == '1';
    }
    setcookie("ipplanDashboardCards", json_encode($newDashboardCards), time() + 10000000, "/");
    $_COOKIE['ipplanDashboardCards'] = json_encode($newDashboardCards);
    $dashboardCards = $newDashboardCards;

    $results=my_("Settings changed");
}
// Call myheading after setting
// the theme variable any change shows up
// immediately.
$w=myheading($p, $title);

insert($w,text($results));

//if (!$_POST) {
// display opening text
insert($w,heading(3, "$title."));

// start form
insert($w, $f = form(array("method"=>"post",
                "action"=>$_SERVER["PHP_SELF"])));

insert($f, $con=container("fieldset",array("class"=>"fieldset")));
insert($con, $legend=container("legend",array("class"=>"legend")));
insert($legend,text(my_("Change display settings for this workstation")));

insert($con,textbr(my_("Setting paranoid prompts 'Are you sure?' for all deletes")));
insert($con,selectbox(array("0"=>my_("No"),
                "1"=>my_("Yes")),
            array("name"=>"paranoid"),
            (int)$ipplanParanoid));

insert($con,generic("br"));
insert($con,generic("br"));
insert($con,textbr(my_("Setting poll forces a scan of the IP address before assigning it, and warns the user if the address is active. This slows down address assignment.")));
insert($con,selectbox(array("0"=>my_("No"),
                "1"=>my_("Yes")),
            array("name"=>"poll"),
            (int)$ipplanPoll));

insert($con,textbr());

// Rows per page setting
insert($con,generic("br"));
insert($con,textbr(my_("Records per page in list views (subnets, hosts, DNS records, etc.)")));
$currentRowsPerPage = isset($_COOKIE['ipplanRowsPerPage']) ? (int)$_COOKIE['ipplanRowsPerPage'] : MAXTABLESIZE;
insert($con,selectbox(array(
            "64"=>"64",
            "128"=>"128 " . my_("(default)"),
            "256"=>"256",
            "512"=>"512"),
        array("name"=>"rowsperpage"),
        (string)$currentRowsPerPage));

insert($con,textbr());

if(extension_loaded("gettext") and LANGCHOICE) {

    insert($con,block("<br>Language:<br>"));
    //      insert($f,block('<select NAME="lang" ONCHANGE="submit()">'));
    insert($con,block('<select NAME="lang">'));

    foreach($iso_codes as $key => $value)
        // look only at language part of cookie
        if (isset($_COOKIE["ipplanLanguage"]) and substr($_COOKIE['ipplanLanguage'],0,5)==$key)
            insert($con,block('<option VALUE="'.$key.'" SELECTED>'.$value."\n"));
        else
            insert($con, block('<option VALUE="'.$key.'">'.$value."\n"));

    insert($con,block("</select>"));
    insert($con,textbr());

}

// Theme selection
insert($f,textbr());
insert($con,generic("br"));
insert($con,block(my_("Theme:")));
insert($con,generic("br"));
$currentTheme = isset($_COOKIE["ipplanTheme"]) ? $_COOKIE["ipplanTheme"] : "";
$themelist = array();
// Only show themes that have display names (excludes legacy aliases)
global $config_themes, $config_theme_names;
$isIE = isInternetExplorer();
foreach ($config_theme_names as $th => $displayName) {
    // Only include if theme exists in config_themes
    if (isset($config_themes[$th])) {
        // If IE detected, only show classic theme
        if ($isIE && $th !== 'classic') {
            continue;
        }
        $themelist[$th] = $displayName;
    }
}
insert($con,selectbox($themelist, array("name"=>"theme"), $currentTheme));

// Show IE warning if detected
if ($isIE) {
    insert($con,generic("br"));
    insert($con,block("<small style=\"color: #856404;\">" . my_("Internet Explorer detected - only Classic theme is available.") . "</small>"));
}

// Dashboard card settings (only show for Current Branch themes)
if (isCurrentBranchTheme()) {
    insert($con,generic("br"));
    insert($con,generic("br"));
    insert($con,block("<strong>" . my_("Dashboard Cards") . "</strong>"));
    insert($con,generic("br"));
    insert($con,block("<small>" . my_("Select which cards to display on the Dashboard page") . "</small>"));
    insert($con,generic("br"));
    insert($con,generic("br"));

    // Add CSS for the card table
    $cardTableCss = '
    <style>
    .dashboard-card-table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
    }
    .dashboard-card-table th,
    .dashboard-card-table td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid var(--border-color, #ddd);
    }
    .dashboard-card-table th {
        background: var(--bg-card-alt, #f5f5f5);
        font-weight: 600;
        color: var(--text-primary, #333);
    }
    .dashboard-card-table td:last-child {
        text-align: center;
        width: 80px;
    }
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: var(--accent-primary, #0097a7);
    }
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
    </style>';
    insert($con, block($cardTableCss));

    // Build the dashboard cards table
    $cardTableHtml = '<table class="dashboard-card-table">
        <thead>
            <tr>
                <th>' . my_("Card") . '</th>
                <th>' . my_("Enabled") . '</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($dashboardCardNames as $cardKey => $cardLabel) {
        $checked = $dashboardCards[$cardKey] ? 'checked' : '';
        $cardTableHtml .= '
            <tr>
                <td>' . htmlspecialchars($cardLabel) . '</td>
                <td>
                    <label class="toggle-switch">
                        <input type="checkbox" name="dashboard_' . $cardKey . '" value="1" ' . $checked . '>
                        <span class="toggle-slider"></span>
                    </label>
                </td>
            </tr>';
    }

    $cardTableHtml .= '</tbody></table>';
    insert($con, block($cardTableHtml));

    // Reset to defaults button
    $resetBtnHtml = '<div style="margin-top: 10px;">
        <a href="' . $_SERVER['PHP_SELF'] . '?reset_dashboard=1"
           class="btn btn-small"
           style="display: inline-block; padding: 6px 12px; font-size: 12px;
                  color: var(--text-muted, #666); border: 1px solid var(--border-color, #ccc);
                  border-radius: 4px; text-decoration: none; background: transparent;"
           onclick="return confirm(\'' . my_("Reset all dashboard cards to enabled?") . '\');">
            ' . my_("Reset to Defaults") . '
        </a>
    </div>';
    insert($con, block($resetBtnHtml));
}

insert($f,submit(array("value"=>my_("Submit"))));
insert($f,freset(array("value"=>my_("Clear"))));

printhtml($p);

?>
