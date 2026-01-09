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

// IPplan version - defined here in core lib, not in config.php
// Format: YYYY.M.D.revision (e.g., 2026.1.9.4 = 4th release on Jan 9, 2026)
define("IPPLAN_VERSION", "2026.1.9.15");

define("DEFAULTROUTE", "0.0.0.0");
define("ALLNETS", "255.255.255.255");


/*********** Browser Detection *********/

/**
 * Check if the browser is Internet Explorer or IE Mode (Edge IE Mode)
 * Current Branch themes are not supported in IE, so we force classic theme
 */
function isInternetExplorer() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    $ua = $_SERVER['HTTP_USER_AGENT'];
    // Check for IE 10 and below
    if (preg_match('/MSIE\s/', $ua)) {
        return true;
    }
    // Check for IE 11
    if (preg_match('/Trident\/.*rv:/', $ua)) {
        return true;
    }
    // Check for Edge IE Mode (sends IE11 user agent)
    if (strpos($ua, 'Trident') !== false) {
        return true;
    }
    return false;
}

/*********** Theme helper functions *********/

/**
 * Get the current theme identifier
 * Returns theme key like 'current-branch-dark', 'classic', etc.
 */
function getCurrentTheme() {
    // Force classic theme for Internet Explorer (Current Branch themes not supported)
    if (isInternetExplorer()) {
        return 'classic';
    }

    // Check if forced to classic
    if (defined('FORCE_CLASSIC_THEME') && FORCE_CLASSIC_THEME) {
        return 'classic';
    }

    // Check cookie for user preference
    if (isset($_COOKIE['ipplanTheme']) && !empty($_COOKIE['ipplanTheme'])) {
        $theme = $_COOKIE['ipplanTheme'];
        // Handle legacy cookie values - convert old 2026- prefix to current-branch-
        if (strpos($theme, '2026-') === 0) {
            $theme = str_replace('2026-', 'current-branch-', $theme);
        }
        return $theme;
    }

    // Fall back to default theme
    if (defined('DEFAULT_THEME')) {
        return DEFAULT_THEME;
    }

    // Ultimate fallback
    return 'current-branch-dark';
}

/**
 * Check if current theme uses the Current Branch sidebar layout
 */
function isCurrentBranchTheme($theme = null) {
    if ($theme === null) {
        $theme = getCurrentTheme();
    }
    // Check for current-branch- prefix or legacy 2026- prefix
    return (strpos($theme, 'current-branch-') === 0 || strpos($theme, '2026-') === 0);
}

/**
 * Backward compatibility alias for isCurrentBranchTheme
 * @deprecated Use isCurrentBranchTheme() instead
 */
function is2026Theme($theme = null) {
    return isCurrentBranchTheme($theme);
}

/**
 * Get CSS file for a theme
 */
function getThemeCssFile($theme = null) {
    global $config_themes;

    if ($theme === null) {
        $theme = getCurrentTheme();
    }

    if (isset($config_themes[$theme])) {
        return $config_themes[$theme];
    }

    // Fallback to default.css
    return 'default.css';
}

/**
 * Generate IE warning banner HTML
 * Displayed on all pages when Internet Explorer is detected
 */
function getIEWarningBanner() {
    if (!isInternetExplorer()) {
        return '';
    }
    return '<div style="background: #fff3cd; color: #856404; padding: 8px 16px; text-align: center; font-size: 13px; border-bottom: 1px solid #ffc107;">
        ' . my_("Internet Explorer detected. Only the Classic theme is supported in this browser.") . '
    </div>';
}


/*********** start of global code which runs for each script *********/

// compress output of all pages - could break things!
// breaks if there is space after last php close tag in script!
// must flush with ob_flush if sending from system() call
// Note: ob_gzhandler can cause issues on IIS/Windows - use NOCOMPRESS to disable
if (!defined("NOCOMPRESS")) {
    // Only use gzip if zlib is available and we're not on IIS (which can have issues)
    if (function_exists('ob_gzhandler') && extension_loaded('zlib')) {
        // Check if running on IIS - may need to disable compression
        $isIIS = (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false);
        if (!$isIIS) {
            ob_start("ob_gzhandler");
        } else {
            // On IIS, use regular output buffering without gzip
            ob_start();
        }
    } else {
        ob_start();
    }
}

// set the error reporting level for IPplan (PHP 8.2+)
error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
// set to the user defined error handler
set_error_handler("myErrorHandler");

/*********** end of global code which runs for each script *********/


// baseaddr is an int, not an ip address
// Test base address to see if it is on valid subnet boundary
function TestBaseAddr($baseaddr, $subnetsize) {

    $newsize = $subnetsize-1;
    return ($baseaddr & $newsize);

}

// scan a single host to see if it is up
function ScanHost($host, $timeout=2) {

    // try port 80, if we connect OK, else if error 111, also OK
    $fp = @fsockopen($host, 80, $errno, $errstr, $timeout);
    if (!$fp) {
        // linux likes code 111, solaris likes 146
        if ($errno == 111 or $errno == 146) // connection refused
            return 1;
        else
            return 0;
    } else {
        fclose ($fp);
        return 1;
    }
}

// scan ip-range
// expects a range of addresses to scan in nmap range notation
function NmapScan ($range) {

    $NMAP = NMAP;
    $command = "$NMAP -sP -q -n -oG - ".escapeshellarg($range);
    exec($command, $resarr, $retval);

#echo $command;
    // error due to safe mode?
    if ($retval) {
        return FALSE;
    }
    else {
        $ret=array();
        foreach ($resarr as $line) {
            if(preg_match ("/^Host: ([\d\.]*) \(\).*Status: Up$/", $line, $m)) {
                $ret[$m[1]] = 1;
            }
        }
        return $ret;
    }
}

// creates a range of addresses to scan in nmap format given start and end
// ip addresses - something like 10.10.1.64-127
function NmapRange($nmapstart, $nmapend) {

    #echo $nmapstart." ".$nmapend;
    list($so1, $so2, $so3, $so4) = explode(".", $nmapstart);
    list($eo1, $eo2, $eo3, $eo4) = explode(".", $nmapend);

    $res="";
    $res=sprintf("%s.%s.%s.%s",
        $so1==$eo1 ? $so1 : $so1."-".$eo1,
        $so2==$eo2 ? $so2 : $so2."-".$eo2,
        $so3==$eo3 ? $so3 : $so3."-".$eo3,
        $so4==$eo4 ? $so4 : $so4."-".$eo4);

    return $res;

}

// test for ip addresses between 1.0.0.0 and 255.255.255.255
function testIP($a, $allowzero=FALSE) {
    $t = explode(".", $a);

    if (sizeof($t) != 4)
       return 1;

    for ($i = 0; $i < 4; $i++) {
        // first octet may not be 0
        if ($t[0] == 0 && $allowzero == FALSE)
           return 1;
        if ($t[$i] < 0 or $t[$i] > 255)
           return 1;
        if (!is_numeric($t[$i]))
           return 1;
    };
    return 0;
}

// test if string is a valid regex expression
function preg_ispreg($str) {
    $prefix = "";
    $sufix = "";
    if ($str[0] != '^')
        $prefix = '^';
    if ($str[strlen($str) - 1] != '$')
        $sufix = '$';
    $estr = preg_replace("'^/'", "\\/", preg_replace("'([^/])/'", "\\1\\/", $str));
    if (@preg_match("/".$prefix.$estr.$sufix."/", $str, $matches))
        return strcmp($str, $matches[0]) != 0;
    return true;
}

// fill subnet - add 0 or 255 as required
// options - 1 for 0, 2 for 255
function completeIP($a, $opt) {

    $t = explode(".", $a);
    for ($i = 4; $i > sizeof($t); $i--) {
        if ($opt == 1)
           $a = $a.".0";
        else
           $a = $a.".255";
    }

    return $a;
}

// php function ip2long is broken!!! (mod_php4.0.4p1)
function inet_aton($a) {
    $inet = 0.0;
    if (count($t = explode(".", $a)) != 4) return 0;
    //$t = explode(".", $a);
    for ($i = 0; $i < 4; $i++) {
        $inet *= 256.0;
        $inet += $t[$i];
    };
    return $inet;
}

// php function ip2long is broken!!! (mod_php4.0.4p1)
function inet_aton3($a) {
    $inet = 0.0;
    if (count($t = explode(".", $a)) != 4) return 0;
    //$t = explode(".", $a);
    for ($i = 1; $i < 4; $i++) {
        $inet *= 256.0;
        $inet += $t[$i];
    };
    return $inet;
}

// php function long2ip is broken!!! (mod_php4.0.4p1)
function inet_ntoa($n) {
    $t=array(0,0,0,0);
    $msk = 16777216.0;
    $n += 0.0;
    if ($n < 1)
        return('0.0.0.0');
    for ($i = 0; $i < 4; $i++) {
        $k = (int) ($n / $msk);
        $n -= $msk * $k;
        $t[$i]= $k;
        $msk /=256.0;
    };
    $a=join('.', $t);
    return($a);
}

// returns the number of bits in the mask cisco style
function inet_bits($n) {

    if ($n == 1)
       return 32;
    else
       return 32-strlen(decbin($n-1));
}

/*********** Pagination / Rows Per Page *********/

/**
 * Get the effective rows per page value
 * Checks user preference cookie first, falls back to MAXTABLESIZE config default
 * Also checks for page-specific override via 'rpp' GET parameter
 * When 'rpp' is passed via URL, it also updates the user's cookie preference
 *
 * @return int The number of rows to display per page
 */
function getRowsPerPage() {
    // Valid options - must be powers of 2
    $validOptions = array(64, 128, 256, 512);

    // Check for page-specific override via GET parameter
    if (isset($_GET['rpp']) && in_array((int)$_GET['rpp'], $validOptions)) {
        $rpp = (int)$_GET['rpp'];
        // Also update the cookie so this becomes the user's default
        setcookie("ipplanRowsPerPage", "$rpp", time() + 10000000, "/");
        $_COOKIE['ipplanRowsPerPage'] = $rpp;
        return $rpp;
    }

    // Check user preference cookie
    if (isset($_COOKIE['ipplanRowsPerPage'])) {
        $userPref = (int)$_COOKIE['ipplanRowsPerPage'];
        if (in_array($userPref, $validOptions)) {
            return $userPref;
        }
    }

    // Fall back to config default
    return MAXTABLESIZE;
}

/**
 * Display a "Records per page" dropdown selector
 * Preserves existing URL parameters when changing rows per page
 *
 * @param object $container The HTML container to insert into
 * @param string $position Position: 'top' (float right), 'bottom' (float right), 'inline' (default, no float)
 * @return void
 */
function displayRowsPerPageSelector($container, $position = 'inline') {
    $options = array(64, 128, 256, 512);
    $current = getRowsPerPage();

    // Build the current URL with existing parameters, minus 'rpp' and 'block'
    $params = $_GET;
    unset($params['rpp']);
    unset($params['block']); // Reset to first page when changing rows per page
    $baseUrl = $_SERVER['PHP_SELF'];
    $queryString = http_build_query($params);
    $separator = $queryString ? '&' : '';

    // Style based on position
    if ($position === 'inline') {
        // For use inside table-cell layout (display: table-cell wrapper)
        $containerStyle = 'display: table-cell; vertical-align: middle; text-align: right; padding-left: 30px;';
    } else if ($position === 'top' || $position === 'bottom') {
        $containerStyle = 'float: right; margin: 0;';
    } else {
        $containerStyle = 'margin: 10px 0; display: inline-block;';
    }

    $html = '<div class="rows-per-page-selector" style="' . $containerStyle . '">';
    $html .= '<label style="margin-right: 8px; font-size: 12px;">' . my_("Records per page:") . '</label>';
    $html .= '<select onchange="window.location.href=\'' . htmlspecialchars($baseUrl) . '?' . htmlspecialchars($queryString) . $separator . 'rpp=\'+this.value" style="padding: 3px 6px; font-size: 12px;">';

    foreach ($options as $opt) {
        $selected = ($opt == $current) ? ' selected' : '';
        $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '</option>';
    }

    $html .= '</select>';
    $html .= '</div>';

    insert($container, block($html));
}

/**
 * Display a list header with title on left and rows per page selector on right
 * Uses display:table to match the natural width of the content below (like a table)
 *
 * @param object $w The HTML container to insert into
 * @param string $title The title/heading to display (e.g., domain name)
 * @param bool $showRowsSelector Whether to show the rows per page dropdown
 * @return void
 */
function displayListHeader($w, $title, $showRowsSelector = true) {
    $html = '<div class="list-header-wrapper" style="display: table; width: auto; min-width: 100%; margin: 10px 0;">';
    $html .= '<div style="display: table-row;">';
    $html .= '<div style="display: table-cell; vertical-align: middle;"><strong>' . $title . '</strong></div>';

    if ($showRowsSelector) {
        // Build selector inline
        $options = array(64, 128, 256, 512);
        $current = getRowsPerPage();
        $params = $_GET;
        unset($params['rpp']);
        unset($params['block']);
        $baseUrl = $_SERVER['PHP_SELF'];
        $queryString = http_build_query($params);
        $separator = $queryString ? '&' : '';

        $html .= '<div style="display: table-cell; vertical-align: middle; text-align: right; padding-left: 30px;">';
        $html .= '<label style="margin-right: 8px; font-size: 12px;">' . my_("Records per page:") . '</label>';
        $html .= '<select onchange="window.location.href=\'' . htmlspecialchars($baseUrl) . '?' . htmlspecialchars($queryString) . $separator . 'rpp=\'+this.value" style="padding: 3px 6px; font-size: 12px;">';

        foreach ($options as $opt) {
            $selected = ($opt == $current) ? ' selected' : '';
            $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '</option>';
        }

        $html .= '</select>';
        $html .= '</div>';
    }

    $html .= '</div></div>';
    insert($w, block($html));
}

/**
 * Display a list footer with optional content on left and rows per page selector on right
 * Uses display:table to match the natural width of the content above (like a table)
 *
 * @param object $w The HTML container to insert into
 * @param string $leftContent Optional HTML content for the left side (e.g., action buttons)
 * @param bool $showRowsSelector Whether to show the rows per page dropdown
 * @return void
 */
function displayListFooter($w, $leftContent = '', $showRowsSelector = true) {
    $html = '<div class="list-footer-wrapper" style="display: table; width: auto; min-width: 100%; margin: 10px 0;">';
    $html .= '<div style="display: table-row;">';
    $html .= '<div style="display: table-cell; vertical-align: middle;">' . $leftContent . '</div>';

    if ($showRowsSelector) {
        // Build selector inline
        $options = array(64, 128, 256, 512);
        $current = getRowsPerPage();
        $params = $_GET;
        unset($params['rpp']);
        unset($params['block']);
        $baseUrl = $_SERVER['PHP_SELF'];
        $queryString = http_build_query($params);
        $separator = $queryString ? '&' : '';

        $html .= '<div style="display: table-cell; vertical-align: middle; text-align: right; padding-left: 30px;">';
        $html .= '<label style="margin-right: 8px; font-size: 12px;">' . my_("Records per page:") . '</label>';
        $html .= '<select onchange="window.location.href=\'' . htmlspecialchars($baseUrl) . '?' . htmlspecialchars($queryString) . $separator . 'rpp=\'+this.value" style="padding: 3px 6px; font-size: 12px;">';

        foreach ($options as $opt) {
            $selected = ($opt == $current) ? ' selected' : '';
            $html .= '<option value="' . $opt . '"' . $selected . '>' . $opt . '</option>';
        }

        $html .= '</select>';
        $html .= '</div>';
    }

    $html .= '</div></div>';
    insert($w, block($html));
}

/**
 * Display page navigation showing record counts (e.g., "1-128", "129-256")
 * This is a cleaner alternative to DisplayBlock that shows actual record numbers
 *
 * @param object $w The HTML container to insert into
 * @param int $totalRecords Total number of records
 * @param int $currentBlock Current block number (0-based)
 * @param string $baseParams URL parameters to preserve (e.g., "&cust=1&domain=example.com")
 * @return void
 */
function displayPaginationNav($w, $totalRecords, $currentBlock, $baseParams) {
    $rowsPerPage = getRowsPerPage();
    $totalBlocks = ceil($totalRecords / $rowsPerPage);

    if ($totalBlocks <= 1) {
        return; // No pagination needed
    }

    $html = '<div class="pagination-nav" style="margin: 10px 0; font-size: 12px;">';
    $html .= my_("Pages:") . ' ';

    for ($i = 0; $i < $totalBlocks; $i++) {
        $startRec = ($i * $rowsPerPage) + 1;
        $endRec = min(($i + 1) * $rowsPerPage, $totalRecords);
        $label = $startRec . '-' . $endRec;

        $url = $_SERVER["PHP_SELF"] . "?block=" . $i . $baseParams;

        if ($i == $currentBlock) {
            $html .= '<strong>[' . $label . ']</strong> ';
        } else {
            $html .= '<a href="' . htmlspecialchars($url) . '">' . $label . '</a> ';
        }

        // Add separator between links
        if ($i < $totalBlocks - 1) {
            $html .= '| ';
        }
    }

    $html .= '</div>';

    insert($w, block($html));
}

// display various blocks of subnet
// if $fldindex is set use this as column in dfb to skip
// Note: For cleaner record-count based pagination, use displayPaginationNav() instead
function DisplayBlock($w, $row, $totcnt, $anchor, $fldindex="") {

       $rowsPerPage = getRowsPerPage();
       $cnt=intval($totcnt/$rowsPerPage);
       $vars=$_SERVER["PHP_SELF"]."?block=".$cnt.$anchor;
       if ($totcnt % $rowsPerPage == 0) {
          insert($w,anchor($vars, $fldindex ? $row[$fldindex] : inet_ntoa($row["baseaddr"])));
       }
       if ($totcnt % $rowsPerPage == $rowsPerPage-1) {
          insert($w,text(" - "));
          insert($w,anchor($vars, $fldindex ? $row[$fldindex] : inet_ntoa($row["baseaddr"])));
          insert($w,textbr());
       }

       return $vars;
}

/*********** Error Handling for Resource-Intensive Operations *********/

/**
 * Custom shutdown handler to catch fatal errors like memory exhaustion or timeout
 * Call registerResourceErrorHandler() at the start of resource-intensive operations
 */
function resourceErrorShutdownHandler() {
    $error = error_get_last();
    if ($error !== null) {
        $errorTypes = array(E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR);
        if (in_array($error['type'], $errorTypes)) {
            // Check for memory or timeout related errors
            $isResourceError = (
                stripos($error['message'], 'memory') !== false ||
                stripos($error['message'], 'timeout') !== false ||
                stripos($error['message'], 'execution time') !== false ||
                stripos($error['message'], 'exhausted') !== false
            );

            if ($isResourceError) {
                // Clear any output buffers
                while (ob_get_level()) {
                    ob_end_clean();
                }

                $memLimit = ini_get('memory_limit');
                $maxExecTime = ini_get('max_execution_time');

                // Output a helpful error page
                echo '<!DOCTYPE html><html><head><title>Resource Limit Exceeded - IPplan</title>';
                echo '<style>body{font-family:Arial,sans-serif;margin:40px;background:#f5f5f5;}';
                echo '.error-box{background:#fff;border:1px solid #dc3545;border-radius:8px;padding:30px;max-width:700px;margin:0 auto;box-shadow:0 2px 10px rgba(0,0,0,0.1);}';
                echo '.error-title{color:#dc3545;margin:0 0 20px 0;font-size:24px;}';
                echo '.error-message{color:#333;line-height:1.6;}';
                echo '.settings-box{background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;padding:15px;margin:20px 0;font-family:monospace;font-size:13px;}';
                echo '.solution-list{margin:15px 0;padding-left:20px;}';
                echo '.solution-list li{margin:8px 0;}';
                echo '.back-link{display:inline-block;margin-top:20px;padding:10px 20px;background:#0097a7;color:#fff;text-decoration:none;border-radius:4px;}';
                echo '.back-link:hover{background:#00838f;}';
                echo '</style></head><body>';
                echo '<div class="error-box">';
                echo '<h1 class="error-title">⚠️ Resource Limit Exceeded</h1>';
                echo '<div class="error-message">';
                echo '<p>The operation exceeded PHP resource limits. This typically happens when:</p>';
                echo '<ul class="solution-list">';
                echo '<li>Importing a large DNS zone with many records</li>';
                echo '<li>Processing a very large dataset</li>';
                echo '<li>PHP memory or execution time limits are too low</li>';
                echo '</ul>';
                echo '<div class="settings-box">';
                echo '<strong>Current PHP Settings:</strong><br>';
                echo 'memory_limit = ' . htmlspecialchars($memLimit) . '<br>';
                echo 'max_execution_time = ' . htmlspecialchars($maxExecTime) . ' seconds';
                echo '</div>';
                echo '<p><strong>Solutions:</strong></p>';
                echo '<ul class="solution-list">';
                echo '<li>Ask your system administrator to increase <code>memory_limit</code> in php.ini (e.g., to 256M or 512M)</li>';
                echo '<li>Ask your system administrator to increase <code>max_execution_time</code> in php.ini (e.g., to 300 or 600)</li>';
                echo '<li>For IIS: Modify these settings in the PHP configuration for your site</li>';
                echo '<li>Try importing smaller portions of data if possible</li>';
                echo '</ul>';
                echo '<a href="javascript:history.back()" class="back-link">← Go Back</a>';
                echo '</div></div></body></html>';
                exit;
            }
        }
    }
}

/**
 * Register the resource error handler for operations that may hit memory/timeout limits
 * Call this at the start of resource-intensive operations like DNS zone transfers
 */
function registerResourceErrorHandler() {
    register_shutdown_function('resourceErrorShutdownHandler');
}

// displays customer drop down box - requires a working form
// $submit parameter allows drop down to just be displayed, normal
// behaviour will be to submit to self
function myCustomerDropDown($ds, $f1, $cust, $grps, $submit=TRUE) {

   // need to see cookie!
   global $ipplanCustomer, $displayall;

   $custset=0;

   $cust=floor($cust);   // dont trust $cust as it could 
                         // come from form post
   $ipplanCustomer=floor($ipplanCustomer);

   // display customer drop down list, nothing to display, just exit
   if (!$result=$ds->GetCustomerGrp(0))
       return 0;

   // do this here else will do extra queries for every customer
   $adminuser=$ds->TestGrpsAdmin($grps);

   insert($f1,textbrbr(my_("Customer/autonomous system")));
   $lst=array();
   while($row=$result->FetchRow()) {
      // ugly kludge with global variable!
      // remove all from list if global searching is not available
      if (!$displayall and strtolower($row["custdescrip"])=="all")
         continue;

      // strip out customers user may not see due to not being member
      // of customers admin group. $grps array could be empty if anonymous
      // access is allowed!
      if(!$adminuser) {
         if(!empty($grps)) {
            if(!in_array($row["admingrp"], $grps))
               continue;
         }
      }

      $col=$row["customer"];
      // make customer first customer in database
      if (!$cust) {
         $cust=$col;
         $custset=1;    // remember that customer was blank
      }
      // only make customer same as cookie if customer actually
      // still exists in database, else will cause loop!
      if ($custset) {
         if ($col == $ipplanCustomer)
            $cust=$ipplanCustomer;
      }
      $lst["$col"]=$row["custdescrip"];
   }

   if ($submit)
      insert($f1,selectbox($lst,
                        array("name"=>"cust", "onChange"=>"submit()"),
                        $cust));
   else
      insert($f1,selectbox($lst,
                        array("name"=>"cust"),
                        $cust));

   return $cust;

}

// displays area drop down box - requires a working form
// does not matter if areaindex is out of range, will pick "No range"
function myAreaDropDown($ds, $f1, $cust, $areaindex, $displayall=FALSE) {

   global $notinrange;   // ugly global var 

   $cust=floor($cust);   // dont trust $cust as it could 
                         // come from form post
   $areaindex=floor($areaindex);

   if ($displayall) {
        // display all - only used on createrange
        $result=$ds->GetArea($cust, 0);
   }
   else {
        // display only those areas that have ranges - all other cases
        $result=$ds->GetArea($cust, -1);
   }


   // don't bother if there are no records, will always display "No area"
   insert($f1,textbrbr(my_("Area (optional)")));
   $lst=array();
   $lst["0"]=my_("No area selected");
   if ($notinrange) {
        $lst["-1"]=my_("All subnets not part of range");
   }
   while($result and $row = $result->FetchRow()) {
      $col=$row["areaindex"];
      $lst["$col"]=inet_ntoa($row["areaaddr"])." - ".$row["descrip"];
   }

   insert($f1,selectbox($lst,
                     array("name"=>"areaindex","onChange"=>"submit()"),
                     $areaindex));

   return $areaindex;

}

// displays range drop down box - requires a working form
function myRangeDropDown($ds, $f2, $cust, $areaindex) {

   $cust=floor($cust);   // dont trust $cust as it could 
                         // come from form post
   $areaindex=floor($areaindex);

   // display range drop down list
   if ($areaindex)
      $result=$ds->GetRangeInArea($cust, $areaindex);
   else
      $result=$ds->GetRange($cust, 0);

   // don't bother if there are no records, will always display "No range"
   insert($f2,textbrbr(my_("Range (optional)")));
   $lst=array();
   $lst["0"]=my_("No range selected");
   while($row = $result->FetchRow()) {
      $col=$row["rangeindex"];
      $lst["$col"]=inet_ntoa($row["rangeaddr"])."/".inet_ntoa(inet_aton(ALLNETS)-$row["rangesize"]+1).
                   "/".inet_bits($row["rangesize"])." - ".$row["descrip"];
   }

   insert($f2,selectbox($lst,
                     array("name"=>"rangeindex")));

}

// displays error messages and terminates the programs execution
// should only be used for terminal errors that cannot recover (database etc)
// will also ignore previous output generated for the HTML page
// takes optional terminate parameter - if FALSE, script does not terminate
// used for displaying non-fatal form errors
// $message is can be a number of errors seperated by \n
function myError($w, $p, $message, $terminate=TRUE) {

    // Changed by Stephen, 12/24/2004
    // $w now equals the DIV container for the class.
    // $p now equals the pointer to the HTML container.  

    // display error message
    if (!empty($message)) {
        $message=nl2br(htmlspecialchars($message));
        insert($w,span($message, array("class"=>"textError")));
    }

    if ($terminate) {
        printhtml($p);
        exit;
    }
}



// wrapper around gettext function to check if gettext is available first
// gettext is used to internationalize a program
// see http://www.gnu.org/software/gettext
//     http://zez.org/article/articleview/42/
//     http://www.php-er.com/chapters/Gettext_Functions.html
function my_($message) {

   return extension_loaded("gettext") ? gettext($message) : $message;

}

// set the language to use
// cookie is in form <2 letter country code>;<path to ipplan root>
function myLanguage($langcookie) {

    if(extension_loaded("gettext")) {
        // split language and path from cookie
        list($lang,$path) = explode(":", $langcookie, 2);

        // initialize gettext for Windows
        if (strpos(strtoupper(PHP_OS),'WIN') !== false) {
            putenv("LANG=$lang");
            $locale=setlocale(LC_ALL, $lang);
            // Specify location of translation tables 
            bindtextdomain ("messages", $path."\locale"); 
        }
        // and the rest
        else {
            // Set language environment variable
            // not required anymore
            //putenv("LANG=$lang");
            $locale=setlocale(LC_ALL, $lang);
            if (!$locale and $lang!= "en_EN" and DEBUG) {
                echo "Setting locale failed - the language choosen is probably not installed correctly\n";
            }
            // Specify location of translation tables 
            bindtextdomain ("messages", $path."/locale"); 
        }

        // Choose domain 
        textdomain ("messages");
    }
}

// returns the directory tree base URL for menu construction
// if constant BASE_URL is defined, use that instead
function base_url() {

    $BASE_URL = BASE_URL;

    if (empty($BASE_URL)) {
        // dirname strips trailing slash!
        $tmp = dirname($_SERVER["PHP_SELF"]);
        //$tmp = dirname($_SERVER["SCRIPT_NAME"]);
        $tmp = preg_replace("/\\/user$/i", "", $tmp);
        $tmp = preg_replace("/\\/admin$/i", "", $tmp);

        // Normalize backslashes to forward slashes (Windows compatibility)
        $tmp = str_replace('\\', '/', $tmp);

        // installed in root of a virtual server? then return empty path
        // Handle various edge cases for root installations
        if ($tmp == "/" || $tmp == "" || $tmp == "." || $tmp == '//') return "";

        return $tmp;
    }
    else {
        return $BASE_URL;
    }

}

// returns the base path under which IPplan is installed
function base_dir() {

    // dirname strips trailing slash!
    $tmp = dirname(__FILE__);
    //$tmp = dirname($_SERVER["SCRIPT_FILENAME"]);
    $tmp = preg_replace("/\\/user$/i", "", $tmp);
    $tmp = preg_replace("/\\/admin$/i", "", $tmp);

    return $tmp;

}

// returns a complete URI for use with Location: header. Returns relative URI
// if complete URI cannot be worked out
function location_uri($relative_url) {

    // running Apache or something or server that has HTTP_HOST set?
    if (isset($_SERVER['HTTP_HOST'])) {
        return "http" . (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=='on'?"s":"")
            ."://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/".$relative_url;
    }
    // no, we will hope for best with a relative URI - this is against HTTP specs, but too bad
    else {
        return $relative_url;
    }
}

// finds URL's in a string and converts them to links
function linkURL($txt) {
    return preg_replace( 
    '/(http|ftp|telnet)+(s)?:(\/\/)((\w|\.)+)(\/)?(\S+)?/i', 
                  '<a href="\0">\4</a>', $txt);
}

// This function returns the username of the current user
// as reported by PHP, with special handling for IIS.
 
// First, copy PHP server vars into a new var called MY_SERVER_VARS
// (vars might get updated if running on IIS)
function getAuthUsername() {
    // If user has logged out, don't report username even if browser sends cached credentials
    if (isset($_COOKIE["ipplanNoAuth"]) && $_COOKIE["ipplanNoAuth"] == "yes") {
        return "";
    }

    $MY_SERVER_VARS = $_SERVER;

    // Special handling for IIS - use HTTP_AUTHORIZATION instead of PHP_AUTH_*
    // (see http://www.php.net/features.http-auth - example 34.3)
    if ( (!(isset($MY_SERVER_VARS[AUTH_VAR])))      &&
            (isset($MY_SERVER_VARS["HTTP_AUTHORIZATION"]))) {
        if (preg_match("/^Basic /", $MY_SERVER_VARS["HTTP_AUTHORIZATION"]) ) {
            list($MY_SERVER_VARS[AUTH_VAR],$MY_SERVER_VARS["PHP_AUTH_PW"]) =
                explode(':',base64_decode(substr($MY_SERVER_VARS["HTTP_AUTHORIZATION"], 6)));
        }
    }
    // If it's not IIS, then the username should already be in AUTH_VAR
    return (isset($MY_SERVER_VARS[AUTH_VAR]) ? $MY_SERVER_VARS[AUTH_VAR] : "");
}

// draw title block
function myheading($q, $title, $displaymenu=true) {

    // Generate the correct prefix for URLs in menu.
    $BASE_URL = base_url();
    $BASE_DIR = base_dir();

    $myDirPath = $BASE_DIR . '/menus/';
    $myWwwPath = $BASE_URL . '/menus/';

    // Get current theme
    $currentTheme = getCurrentTheme();
    $isCurrentBranch = isCurrentBranchTheme($currentTheme);
    $themeCssFile = getThemeCssFile($currentTheme);

    // create the html page HEAD section
    insert($q, $header=wheader("IPPlan - $title"));
    insert($header, generic("meta",array("http-equiv"=>"Content-Type","content"=>"text/html; charset=UTF-8")));
    insert($header, generic("meta",array("name"=>"viewport","content"=>"width=device-width, initial-scale=1")));

    // Load theme CSS
    insert($header, generic("link",array("rel"=>"stylesheet","href"=>"$BASE_URL/themes/$themeCssFile")));

    // For Current Branch themes, use sidebar layout
    if ($isCurrentBranch) {
        return myheading_current_branch($q, $header, $title, $displaymenu, $BASE_URL, $BASE_DIR);
    }

    // Classic theme layout (original code path)
    insert($q, $w=container("div",array("class"=>"matte")));

    // Add IE warning banner if needed
    $ieBanner = getIEWarningBanner();
    if ($ieBanner) {
        insert($w, block($ieBanner));
    }

    // Load PHPLayersMenu for classic themes
    if ($displaymenu) {
        require_once $myDirPath . 'lib/PHPLIB.php';
        require_once $myDirPath . 'lib/layersmenu-common.inc.php';
        require_once $myDirPath . 'lib/layersmenu.inc.php';
        require_once $BASE_DIR  . '/menudefs.php';
        eval("\$ADMIN_MENU = \"$ADMIN_MENU\";");

        insert($header, generic("link",array("rel"=>"stylesheet","href"=>"$myWwwPath"."layersmenu-gtk2.css")));

        insert($w, script("",array("language"=>"JavaScript","type"=>"text/javascript","src"=> $myWwwPath."libjs/layersmenu-browser_detection.js")));
        insert($w, script("",array("language"=>"JavaScript","type"=>"text/javascript","src"=> $myWwwPath . 'libjs/layersmenu-library.js')));
        insert($w, script("",array("language"=>"JavaScript","type"=>"text/javascript","src"=> $myWwwPath . 'libjs/layersmenu.js')));

        $mid= new LayersMenu(6, 7, 2, 1);
        $mid->setDirroot ($BASE_DIR.'/menus/');
        $mid->setLibjsdir($BASE_DIR.'/menus/libjs/');
        $mid->setImgdir  ($BASE_DIR.'/menus/menuimages/');
        $mid->setImgwww  ($BASE_URL.'/menus/menuimages/');
        $mid->setIcondir ($BASE_DIR.'/menus/menuicons/');
        $mid->setIconwww ($BASE_URL.'/menus/menuicons/');
        $mid->setTpldir  ($BASE_DIR.'/menus/templates/');
        $mid->SetMenuStructureString($ADMIN_MENU);
        $mid->setIconsize(16, 16);
        $mid->parseStructureForMenu('hormenu1');
        $mid->newHorizontalMenu('hormenu1');
    }

    // draw header box
    insert($w,$con=container("div",array("class"=>"headerbox",
                    "align"=>"center")));
    insert($con, heading(1, my_("IPPlan - IP Address Management and Tracking")));
    insert($con, block("<br>"));
    insert($con, heading(3, $title));

    if ($displaymenu) {
        // draw menu box here
        insert($w,$con=container("div",array("class"=>"menubox")));
        insert($con,$t =table(array("cols"=>"2","width"=>"100%")));
        insert($t, $c1=cell());
        insert($t, $c2=cell(array("align"=>"right")));

        insert($c1,block($mid->getHeader()));
        insert($c1,block($mid->getMenu('hormenu1')));
        insert($c1,block($mid->getFooter()));

        // find a place to display logged in user
        insert ($c2,$uc=container("div",array("class"=>"userbox")));
        if (getAuthUsername() != "") {
            insert($uc,block(sprintf(my_("Logged in as %s"), getAuthUsername())));
        }
    }

    insert($w,$con=container("div",array("class"=>"normalbox")));
    insert($w,$con1=container("div",array("class"=>"footerbox")));
    insert($con1,block("IPPlan v" . IPPLAN_VERSION));
    return $con;
}

/**
 * Current Branch theme layout with sidebar navigation
 */
function myheading_current_branch($q, $header, $title, $displaymenu, $BASE_URL, $BASE_DIR) {

    $username = getAuthUsername();

    // Build sidebar navigation HTML
    $sidebarNav = getSidebarNavigation($BASE_URL, $displaymenu);

    // Check for logo file - theme-override/images takes priority over images directory
    $logoFile = '';
    $logoOptions = array(
        'SystemLogo_621x146.png',
        'IPPlan_255.png',
        'IPPlan_256.png',
        'logo.png',
        'ipplan-logo.png',
        'logo.svg'
    );
    foreach ($logoOptions as $logoName) {
        // Check theme-override first
        if (file_exists($BASE_DIR . '/theme-override/images/' . $logoName)) {
            $logoFile = $BASE_URL . '/theme-override/images/' . $logoName;
            break;
        }
        // Fall back to default images directory
        if (file_exists($BASE_DIR . '/images/' . $logoName)) {
            $logoFile = $BASE_URL . '/images/' . $logoName;
            break;
        }
    }

    // Build logo HTML - use image if available, fallback to text
    // Logo is clickable and links to home page
    if ($logoFile) {
        $logoInner = '<img src="' . $logoFile . '" alt="IPPlan" class="ipplan-sidebar-logo-img">';
    } else {
        $logoInner = '<div class="ipplan-sidebar-logo-icon">IP</div>
                <div>
                    <div class="ipplan-sidebar-logo-text">IPPlan</div>
                    <div class="ipplan-sidebar-logo-subtitle">' . my_("Address Management") . '</div>
                </div>';
    }
    // Wrap logo content in a link to home page
    $logoHtml = '<a href="' . $BASE_URL . '/index.php" class="ipplan-sidebar-logo-link">' . $logoInner . '</a>';

    // Build the complete Current Branch layout
    $layoutHtml = '
    <div class="ipplan-app">
        <!-- Sidebar -->
        <aside class="ipplan-sidebar">
            <div class="ipplan-sidebar-logo">
                ' . $logoHtml . '
            </div>

            <div class="ipplan-sidebar-nav-wrapper">
            ' . $sidebarNav . '
            </div>

            <div class="ipplan-sidebar-userbox">
                ' . ($username ? '
                <div class="ipplan-sidebar-userbox-label">' . my_("Logged in") . '</div>
                <div class="ipplan-sidebar-userbox-name">' . htmlspecialchars($username) . '</div>
                <div class="ipplan-sidebar-userbox-buttons">
                    <a href="' . $BASE_URL . '/user/modifyuserform.php" class="btn">' . my_("Profile") . '</a>
                    <a href="' . $BASE_URL . '/user/logout.php" class="btn btn-danger">' . my_("Logout") . '</a>
                </div>
                ' : '
                <div class="ipplan-sidebar-userbox-label">' . my_("Not Logged In") . '</div>
                <div class="ipplan-sidebar-userbox-buttons">
                    <a href="' . $BASE_URL . '/user/login.php" class="btn btn-primary" onclick="window.location.href=this.href;return false;">' . my_("Login") . '</a>
                </div>
                ') . '
            </div>

            <div class="ipplan-sidebar-version">IPPlan v' . IPPLAN_VERSION . '</div>
        </aside>

        <script>
        // Position submenus on hover to align with their parent nav item
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".ipplan-sidebar-nav-dropdown").forEach(function(dropdown) {
                dropdown.addEventListener("mouseenter", function() {
                    var submenu = this.querySelector(".ipplan-sidebar-submenu");
                    if (submenu) {
                        var rect = this.getBoundingClientRect();
                        var viewportHeight = window.innerHeight;
                        var submenuHeight = submenu.offsetHeight || 200;

                        // Start aligned with the menu item
                        var topPos = rect.top;

                        // If submenu would go off bottom of screen, move it up
                        if (topPos + submenuHeight > viewportHeight - 20) {
                            topPos = viewportHeight - submenuHeight - 20;
                        }

                        // Never go above the viewport
                        if (topPos < 10) {
                            topPos = 10;
                        }

                        submenu.style.top = topPos + "px";
                    }
                });
            });
        });
        </script>

        <!-- Main content -->
        <main class="ipplan-main">
            <!-- Header bar -->
            <header class="ipplan-header">
                <div class="ipplan-header-inner">
                    <div class="ipplan-header-title-section">
                        <div class="ipplan-header-breadcrumb">' . my_("IPPlan") . '</div>
                        <h1 class="ipplan-header-title">' . htmlspecialchars($title) . '</h1>
                    </div>
                    <div class="ipplan-header-actions">
                        <a href="' . $BASE_URL . '/user/searchallform.php" class="btn">' . my_("Search") . '</a>
                    </div>
                </div>
            </header>

            <!-- Content area -->
            <div class="ipplan-content">
    ';

    // Insert the opening layout HTML
    insert($q, block($layoutHtml));

    // Create a proper container for page content (this is what gets returned)
    insert($q, $contentContainer = container("div", array("class" => "normalbox")));

    // Set the flag so Window class knows to close the Current Branch divs
    $GLOBALS['ipplan_current_branch_layout'] = true;

    // Return the content container - pages will insert their content into this
    return $contentContainer;
}

/**
 * Sidebar navigation icons as inline SVG (18x18, fill="currentColor")
 * Inspired by visuaFUSION phishing simulator design
 */
function getSidebarIcon($iconName) {
    $icons = array(
        'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M7.7 6.67H1.46C.65 6.67 0 6.01 0 5.21V1.46C0 .65.65 0 1.46 0h6.25c.8 0 1.46.65 1.46 1.46v3.75c0 .8-.65 1.46-1.46 1.46zM1.46 1.25c-.12 0-.21.09-.21.21v3.75c0 .12.09.21.21.21h6.25c.12 0 .21-.09.21-.21V1.46c0-.12-.09-.21-.21-.21H1.46z" fill="currentColor"/><path d="M7.7 20H1.46C.65 20 0 19.35 0 18.54V9.79c0-.8.65-1.46 1.46-1.46h6.25c.8 0 1.46.65 1.46 1.46v8.75c0 .8-.65 1.46-1.46 1.46zM1.46 9.58c-.12 0-.21.09-.21.21v8.75c0 .12.09.21.21.21h6.25c.12 0 .21-.09.21-.21V9.79c0-.12-.09-.21-.21-.21H1.46z" fill="currentColor"/><path d="M18.54 20h-6.25c-.8 0-1.46-.65-1.46-1.46v-3.75c0-.8.65-1.46 1.46-1.46h6.25c.8 0 1.46.65 1.46 1.46v3.75c0 .8-.65 1.46-1.46 1.46zm-6.25-5.42c-.12 0-.21.09-.21.21v3.75c0 .12.09.21.21.21h6.25c.12 0 .21-.09.21-.21v-3.75c0-.12-.09-.21-.21-.21h-6.25z" fill="currentColor"/><path d="M18.54 11.67h-6.25c-.8 0-1.46-.65-1.46-1.46V1.46c0-.8.65-1.46 1.46-1.46h6.25c.8 0 1.46.65 1.46 1.46v8.75c0 .8-.65 1.46-1.46 1.46zm-6.25-10.42c-.12 0-.21.09-.21.21v8.75c0 .12.09.21.21.21h6.25c.12 0 .21-.09.21-.21V1.46c0-.12-.09-.21-.21-.21h-6.25z" fill="currentColor"/></svg>',
        'customers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M16.8 10.3h-1.9c-.26 0-.5.03-.75.1-.47-.92-1.43-1.56-2.54-1.56h-3.37c-1.11 0-2.07.64-2.54 1.56-.24-.07-.49-.1-.75-.1H3.06c-1.57 0-2.85 1.28-2.85 2.85v3.05c0 .94.77 1.71 1.71 1.71h16.37c.94 0 1.71-.77 1.71-1.71v-3.05c0-1.57-1.28-2.85-2.85-2.85zm-12.02 1.4v5.09H1.92c-.31 0-.57-.26-.57-.57v-3.05c0-.94.77-1.71 1.71-1.71h1.9c.15 0 .3.02.44.06-.01.06-.02.12-.02.18zm8.83 5.09H6.11v-5.09c0-.94.77-1.71 1.71-1.71h5.88c.94 0 1.71.77 1.71 1.71v5.09zm5.17-.57c0 .31-.26.57-.57.57h-4.31v-5.09c0-.08 0-.15-.01-.23.14-.04.29-.06.44-.06h1.9c.94 0 1.71.77 1.71 1.71v3.1z" fill="currentColor"/><path d="M4.04 4.94c-1.4 0-2.53 1.14-2.53 2.53s1.14 2.53 2.53 2.53 2.53-1.14 2.53-2.53-1.13-2.53-2.53-2.53zm0 3.77c-.69 0-1.24-.56-1.24-1.24s.56-1.24 1.24-1.24 1.24.56 1.24 1.24-.55 1.24-1.24 1.24z" fill="currentColor"/><path d="M9.98 1.8c-1.87 0-3.38 1.52-3.38 3.38s1.52 3.38 3.38 3.38 3.38-1.52 3.38-3.38S11.84 1.8 9.98 1.8zm0 5.47c-1.16 0-2.09-.94-2.09-2.09s.94-2.09 2.09-2.09 2.09.94 2.09 2.09-.94 2.09-2.09 2.09z" fill="currentColor"/><path d="M15.91 4.94c-1.4 0-2.53 1.14-2.53 2.53s1.14 2.53 2.53 2.53 2.53-1.14 2.53-2.53-1.13-2.53-2.53-2.53zm0 3.77c-.69 0-1.24-.56-1.24-1.24s.56-1.24 1.24-1.24 1.24.56 1.24 1.24-.55 1.24-1.24 1.24z" fill="currentColor"/></svg>',
        'subnets' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><rect x="7" y="1" width="6" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><rect x="1" y="15" width="5" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><rect x="7.5" y="15" width="5" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><rect x="14" y="15" width="5" height="4" rx="1" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M10 5v3m0 0v4m0-4h-6.5v7m6.5-7h6.5v7" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>',
        'dns' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5" fill="none"/><ellipse cx="10" cy="10" rx="3.5" ry="8.25" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M2 10h16M3 6h14M3 14h14" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>',
        'search' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="8.5" cy="8.5" r="6.75" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M13.5 13.5L18 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
        'admin' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><path d="M10 1L2 4.5v5c0 4.42 3.42 8.54 8 9.5 4.58-.96 8-5.08 8-9.5v-5L10 1z" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M7 10l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>',
        'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M16.5 10c0-.34-.03-.67-.08-1l1.74-1.36-1.75-3.03-2.03.82a6.96 6.96 0 0 0-1.73-1l-.31-2.18h-3.5l-.31 2.18c-.63.24-1.21.58-1.73 1l-2.03-.82-1.75 3.03L4.76 9c-.1.66-.1 1.34 0 2l-1.74 1.36 1.75 3.03 2.03-.82c.52.42 1.1.76 1.73 1l.31 2.18h3.5l.31-2.18c.63-.24 1.21-.58 1.73-1l2.03.82 1.75-3.03L16.42 11c.05-.33.08-.66.08-1z" stroke="currentColor" stroke-width="1.5" fill="none"/></svg>',
        'help' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="8.25" stroke="currentColor" stroke-width="1.5" fill="none"/><path d="M7.5 7.5a2.5 2.5 0 1 1 3.25 2.39c-.46.14-.75.58-.75 1.06V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/><circle cx="10" cy="14.5" r="0.75" fill="currentColor"/></svg>'
    );
    return isset($icons[$iconName]) ? $icons[$iconName] : '';
}

/**
 * Generate sidebar navigation HTML for Current Branch themes
 * Organized into sections like visuaFUSION
 */
function getSidebarNavigation($BASE_URL, $displaymenu) {
    if (!$displaymenu) {
        return '';
    }

    // Get current page to highlight active nav item
    $currentPage = basename($_SERVER['PHP_SELF']);
    $currentDir = basename(dirname($_SERVER['PHP_SELF']));

    // Define sidebar navigation structure with sections
    $sections = array(
        // Main section - no header
        array(
            'header' => '',
            'items' => array(
                array(
                    'label' => my_('Dashboard'),
                    'url' => $BASE_URL . '/dashboard.php',
                    'match' => array('dashboard.php'),
                    'icon' => 'dashboard'
                )
            )
        ),
        // Network section
        array(
            'header' => my_('Network'),
            'items' => array(
                array(
                    'label' => my_('Customers'),
                    'url' => $BASE_URL . '/user/displaycustomerform.php',
                    'match' => array('displaycustomerform.php', 'modifycustomer.php', 'createcustomer.php'),
                    'icon' => 'customers',
                    'children' => array(
                        array('label' => my_('Create New'), 'url' => $BASE_URL . '/user/modifycustomer.php'),
                        array('label' => my_('Edit Existing'), 'url' => $BASE_URL . '/user/displaycustomerform.php')
                    )
                ),
                array(
                    'label' => my_('Subnets'),
                    'url' => $BASE_URL . '/user/displaybaseform.php',
                    'match' => array('displaybaseform.php', 'displaybase.php', 'displaysubnet.php', 'modifybase.php', 'modifybaseform.php', 'createbase.php', 'createbaseform.php', 'createsubnetform.php', 'createarea.php', 'createrange.php', 'modifyarearangeform.php', 'treeview.php', 'displayoverlapform.php'),
                    'icon' => 'subnets',
                    'children' => array(
                        array('label' => my_('Display Subnets'), 'url' => $BASE_URL . '/user/displaybaseform.php'),
                        array('label' => my_('Tree View'), 'url' => $BASE_URL . '/user/treeview.php'),
                        array('label' => my_('Create Subnet'), 'url' => $BASE_URL . '/user/createsubnetform.php'),
                        array('label' => my_('Modify Subnet'), 'url' => $BASE_URL . '/user/modifybaseform.php'),
                        array('label' => my_('---')),
                        array('label' => my_('Create Area'), 'url' => $BASE_URL . '/user/createarea.php'),
                        array('label' => my_('Create Range'), 'url' => $BASE_URL . '/user/createrange.php'),
                        array('label' => my_('Modify Areas/Ranges'), 'url' => $BASE_URL . '/user/modifyarearangeform.php'),
                        array('label' => my_('---')),
                        array('label' => my_('Overlap Check'), 'url' => $BASE_URL . '/user/displayoverlapform.php')
                    )
                ),
                array(
                    'label' => my_('DNS'),
                    'url' => $BASE_URL . '/user/modifydns.php',
                    'match' => array('modifydns.php', 'modifydnsform.php', 'modifydnsrecord.php', 'modifydnsrecordform.php', 'modifyzone.php', 'modifyzoneform.php'),
                    'icon' => 'dns',
                    'children' => array(
                        array('label' => my_('Zone Domains'), 'url' => $BASE_URL . '/user/modifydns.php'),
                        array('label' => my_('DNS Records'), 'url' => $BASE_URL . '/user/modifydnsrecord.php'),
                        array('label' => my_('Reverse DNS'), 'url' => $BASE_URL . '/user/modifyzone.php')
                    )
                )
            )
        ),
        // Tools section
        array(
            'header' => my_('Tools'),
            'items' => array(
                array(
                    'label' => my_('Search'),
                    'url' => $BASE_URL . '/user/searchallform.php',
                    'match' => array('searchallform.php', 'searchall.php', 'finddns.php', 'findip.php', 'findsubnet.php', 'findfreeform.php', 'findfree.php'),
                    'icon' => 'search',
                    'children' => array(
                        array('label' => my_('Search Subnets'), 'url' => $BASE_URL . '/user/searchallform.php'),
                        array('label' => my_('Find Free Space'), 'url' => $BASE_URL . '/user/findfreeform.php')
                    )
                ),
                array(
                    'label' => my_('Admin'),
                    'url' => $BASE_URL . '/admin/usermanager.php',
                    'match' => array('usermanager.php', 'groupedit.php', 'polldns.php', 'importbaseform.php', 'exportform.php', 'exportbaseform.php', 'exportipform.php', 'importipform.php', 'displayboundsform.php', 'displayauditlog.php', 'maintenance.php'),
                    'icon' => 'admin',
                    'children' => array(
                        array('label' => my_('Users'), 'url' => $BASE_URL . '/admin/usermanager.php'),
                        array('label' => my_('Create User'), 'url' => $BASE_URL . '/admin/usermanager.php?action=newuserform'),
                        array('label' => my_('Groups'), 'url' => $BASE_URL . '/admin/usermanager.php?action=newgroupform'),
                        array('label' => my_('Boundaries'), 'url' => $BASE_URL . '/admin/displayboundsform.php'),
                        array('label' => my_('---')),
                        array('label' => my_('Import Subnets'), 'url' => $BASE_URL . '/admin/importbaseform.php'),
                        array('label' => my_('Import IPs'), 'url' => $BASE_URL . '/admin/importipform.php'),
                        array('label' => my_('Export Subnets'), 'url' => $BASE_URL . '/admin/exportbaseform.php'),
                        array('label' => my_('Export IPs'), 'url' => $BASE_URL . '/admin/exportipform.php'),
                        array('label' => my_('---')),
                        array('label' => my_('Maintenance'), 'url' => $BASE_URL . '/admin/maintenance.php'),
                        array('label' => my_('Audit Log'), 'url' => $BASE_URL . '/admin/displayauditlog.php')
                    )
                ),
                array(
                    'label' => my_('Settings'),
                    'url' => $BASE_URL . '/user/changesettings.php',
                    'match' => array('changesettings.php', 'tplbaseform.php', 'changepassword.php'),
                    'icon' => 'settings',
                    'children' => array(
                        array('label' => my_('Display Settings'), 'url' => $BASE_URL . '/user/changesettings.php'),
                        array('label' => my_('Change Password'), 'url' => $BASE_URL . '/admin/changepassword.php')
                    )
                )
            )
        ),
        // Miscellaneous section
        array(
            'header' => my_('Miscellaneous'),
            'items' => array(
                array(
                    'label' => my_('Help'),
                    'url' => $BASE_URL . '/help.php',
                    'match' => array('help.php', 'about.php', 'license.php'),
                    'icon' => 'help',
                    'children' => array(
                        array('label' => my_('User Guide'), 'url' => $BASE_URL . '/help.php?section=user&page=index'),
                        array('label' => my_('Admin Guide'), 'url' => $BASE_URL . '/help.php?section=admin&page=index'),
                        array('label' => my_('---')),
                        array('label' => my_('About'), 'url' => $BASE_URL . '/about.php'),
                        array('label' => my_('License'), 'url' => $BASE_URL . '/license.php')
                    )
                )
            )
        )
    );

    $html = '';

    foreach ($sections as $section) {
        // Add section header if present
        if (!empty($section['header'])) {
            $html .= '<div class="ipplan-sidebar-section">' . $section['header'] . '</div>';
        }

        $html .= '<nav class="ipplan-sidebar-nav">';

        foreach ($section['items'] as $item) {
            $isActive = in_array($currentPage, $item['match']);
            $activeClass = $isActive ? ' active' : '';
            $hasChildren = isset($item['children']) && count($item['children']) > 0;
            $chevron = $hasChildren ? '<span class="ipplan-sidebar-nav-chevron">&#9656;</span>' : '';

            if ($hasChildren) {
                $html .= '<div class="ipplan-sidebar-nav-dropdown">';
            }

            $icon = isset($item['icon']) ? '<span class="ipplan-sidebar-nav-icon">' . getSidebarIcon($item['icon']) . '</span>' : '';
            $html .= '<a href="' . $item['url'] . '" class="ipplan-sidebar-nav-item' . $activeClass . '">';
            $html .= $icon;
            $html .= '<span class="ipplan-sidebar-nav-label">' . $item['label'] . '</span>';
            $html .= $chevron;
            $html .= '</a>';

            // Render submenu if present
            if ($hasChildren) {
                $html .= '<div class="ipplan-sidebar-submenu">';
                foreach ($item['children'] as $child) {
                    if ($child['label'] === my_('---')) {
                        $html .= '<div class="ipplan-sidebar-submenu-divider"></div>';
                    } else {
                        $childActive = isset($child['url']) && strpos($_SERVER['REQUEST_URI'], $child['url']) !== false ? ' active' : '';
                        $html .= '<a href="' . $child['url'] . '" class="ipplan-sidebar-submenu-item' . $childActive . '">' . $child['label'] . '</a>';
                    }
                }
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</nav>';
    }

    return $html;
}

// add Copy and Paste links - completes a cookie with a serialized copy of the form values
// or unserializes and pastes a cookie into a form
// phpserializer.js and cookies.js must be pre-loaded on the webpage
function myCopyPaste(&$f, $cookie, $formname) {
    insert($f,block(" <a href='#' onclick='var php = new PHP_Serializer(); setCookie(\"".$cookie."\", php.serialize(getElements(\"".$formname."\"))); return false'>".my_('Copy')."</a>"));

    insert($f,block(" <a href='#' onclick='var php = new PHP_Serializer(); setElements(php.unserialize(getCookie(\"".$cookie."\")), \"".$formname."\"); return false'>".my_('Paste')."</a>"));
}

// draw a search box with associated search type if required
class mySearch {

    // w - the layout display container to draw this in
    // vars - the hidden vars to maintain in this subform for submission - array, probably get or post
    // search - the search string
    // frmvar - the form variable to use
    public $w, $vars, $search, $frmvar;

    public $expr="";           // the last expression used - for form resubmit
    public $expr_disp=FALSE;   // do we require an expression drop down?
    public $method="get";
    public $legend;

    public function __construct(&$w, $vars, $search, $frmvar) {
        $this->w=$w;
        $this->vars=$vars;
        $this->search=$search;
        $this->frmvar=$frmvar;
        $this->legend=my_("Refine Search");
    }

    function Search() {

        unset($this->vars[$this->frmvar]);
        unset($this->vars["block"]);
        unset($this->vars["expr"]);
        //    $url=my_http_build_query($vars);

        // start form
        insert($this->w, $f = form(array("name"=>"SEARCH",
                        "method"=>$this->method,
                        "action"=>$_SERVER["PHP_SELF"])));

        insert($f, $con=container("fieldset",array("class"=>"fieldset")));
        insert($con, $legend=container("legend",array("class"=>"legend")));
        insert($legend, text($this->legend));
        if ($this->expr_disp) {
            $lst=array("START"=>my_("Starts with"),
                    "END"=>my_("Ends with"),
                    "LIKE"=>my_("Contains"),
                    "NLIKE"=>my_("Does not contain"),
                    "EXACT"=>my_("Equal to"));
            if (DBF_TYPE=="mysql" or DBF_TYPE=="maxsql" or DBF_TYPE=="postgres7") {
                $lst["RLIKE"]=my_("Regex contains");
            }
            // only supported by mysql
            if (DBF_TYPE=="mysql" or DBF_TYPE=="maxsql") {
                $lst["NRLIKE"]=my_("Does not regex contain");
            }
            insert($con,selectbox($lst, array("name"=>"expr"), $this->expr));
        }

        insert($con,input_text(array("name"=>$this->frmvar,
                        "value"=>$this->search,
                        "size"=>"20",
                        "maxlength"=>"80")));

        foreach ($this->vars as $key=>$value) {
            insert($con,hidden(array("name"=>"$key",
                            "value"=>"$value")));
        }

        insert($con,submit(array("value"=>my_("Submit"))));
        insert($con,block(" <a href='#' onclick='SEARCH.".$this->frmvar.".value=\"\"; SEARCH.submit();'>".my_("Reset Search")."</a>"));


    }
}

// select field to focus on in html form
// form and field variables must be static text variables
function myFocus($w, $form, $field) {

    insert($w, script("document.$form.$field.focus();",
                array("language"=>"JavaScript", "type"=>"text/javascript")));
}

// IPplan error handler function
function myErrorHandler ($errno, $errstr, $errfile, $errline) {

    static $beenhere=FALSE;

    // ugly hack to filter out E_STRICT php5 messages - needs fixing 
    // error_reporting level appears to be ignored?
    if (phpversion() >= "5.0.0" and $errno==2048) {
        return;
    }

    if (DEBUG==FALSE) {
        // check what we actually want to report on, ignore rest
        if (!($errno & error_reporting())) return;
    }
    else {
        // for debugging - ignore pesky messages
        if (strstr($errstr, "var: Deprecated")) return;
        if (strstr($errfile, "layersmenu.inc.php")) return;
    }


    echo "<div class=errorbox>";
    if (!$beenhere) {
        $trackerName = defined('ISSUE_TRACKER_NAME') ? ISSUE_TRACKER_NAME : 'GitHub';
        $trackerUrl = defined('ISSUE_TRACKER_URL') ? ISSUE_TRACKER_URL : 'https://github.com/visuafusion/ipplan/issues';
        echo "If you see this message, submit a detailed bug report on ";
        echo "<a href=\"" . htmlspecialchars($trackerUrl) . "\" target=\"_blank\">" . htmlspecialchars($trackerName) . "</a> ";
        echo "including the message below, the database platform used and the steps to perform to recreate ";
        echo "the problem.<p>";
        echo "PHP ".PHP_VERSION." (".PHP_OS.")<br>\n";
        $beenhere=TRUE;
    }

    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>FATAL</b> [$errno] $errstr<br>\n";
            echo "  Fatal error in line ".$errline." of file ".$errfile."<br>";
            echo "Aborting...<br>\n";
            echo "</div>";
            exit;
            break;
        case E_USER_WARNING:
            echo "<b>ERROR:</b> [$errno] $errstr Line: $errline File: $errfile<br>\n";
            break;
        case E_USER_NOTICE:
            echo "<b>WARNING:</b> [$errno] $errstr Line: $errline File: $errfile<br>\n";
            break;
        case E_NOTICE:
            echo "<b>NOTICE:</b> [$errno] $errstr Line: $errline File: $errfile<br>\n";
            break;
        case 2048:  // E_STRICT error type of php5 - undefined in php4
            echo "<b>STRICT:</b> [$errno] $errstr Line: $errline File: $errfile<br>\n";
            break;
        default:
            echo "<b>Unknown error type:</b> [$errno] $errstr Line: $errline File: $errfile<br>\n";
            break;
    }
    echo "</div>";

}

function color_flip_flop() {
    // Added by Stephen Blackstone
    // Simple Function to alternate the two pieces by color.

    $color1="oddrow";   // Define row color A.
    $color2="evenrow"; 	 // Define row color B.
    static $currentcolor;
    if ($currentcolor==$color1) { 
        $currentcolor=$color2; 
    }
    else {
        $currentcolor=$color1; 
    }

    return($currentcolor);

}

function stripslashes_deep($value) {
    $value = is_array($value) ?
        array_map('stripslashes_deep', $value) :
            stripslashes($value);

    return $value;
}

// emulates php 5 function to build urlencoded query string from array
function my_http_build_query($arr) {

    $str="";

    foreach ($arr as $key=>$value) {
        if (empty($str)) {
            $str .= $key."=".urlencode($value);
        }
        else {
            $str .= "&".$key."=".urlencode($value);
        }
    }

    return $str;

}

// start a user defined trigger on an ipplan database event
// $action is associative array with at least one index called "event"
// events must be unique, user_trigger function is in ipplanlib.php
// eg array("event"=>100)
// eg array("event"=>100, "cust"=>$cust)
// function called from AuditLog, empty currently, returns nothing
// only called if EXT_FUNCTION in config.php is TRUE
// error handling must be done internal to function

// see TRIGGERS file for list of event codes and variables passed
function user_trigger($action) {

/*
    switch ($action["event"]) {
        case 100:
            system("updatedns.pl ".$action["domain"]);
            break;
        case 200:
            system("deletezone.pl");
            break;
    }
    */

}

// expects a string formatted as follows:
// "code:variablename code:variablename ..."
// where code is A for array, S for string, I for integer
// returns an array of the sanitized variables
function myRegister($vars) {

    $newvars=array();
    $tokens = explode(" ", $vars);

    foreach ($tokens as $value) {
        list($code, $variable) = explode(":", $value);
        switch ($code) {
            case "A":
                $newvars[]=isset($_REQUEST["$variable"]) ? stripslashes_deep($_REQUEST["$variable"]) : array();
                break;
            case "S":
                $newvars[]=isset($_REQUEST["$variable"]) ? stripslashes((string)$_REQUEST["$variable"]) : "";
                break;
            case "B":
                // use floor here to convert to float as int is just not big enough for ip addresses
                $newvars[]=isset($_REQUEST["$variable"]) ? floor($_REQUEST["$variable"]) : 0;
                break;
            case "I":
                $newvars[]=isset($_REQUEST["$variable"]) ? (int)$_REQUEST["$variable"] : 0;
                break;
        }
    }

    return $newvars;

}

/*
// test code
$t1=""; $t2="";
foreach ($_REQUEST as $key=>$value) {
    $t1 .= "S:$key ";
    $t2 .= "$".$key.", ";
}

define_syslog_variables();
openlog("TextLog", LOG_PID, LOG_LOCAL0);
syslog(LOG_INFO, $t1);
syslog(LOG_INFO, $t2);
closelog();
*/

?>
