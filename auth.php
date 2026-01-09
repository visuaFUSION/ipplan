<?php

// PHP BasicAuthenticator Class Version 1.3 (24th March 2001)
//  
// Copyright David Wilkinson 2001. All Rights reserved.
// 
// This software may be used, modified and distributed freely
// providing this copyright notice remains intact at the head 
// of the file.
//
// This software is freeware. The author accepts no liability for
// any loss or damages whatsoever incurred directly or indirectly 
// from the use of this script. The author of this software makes 
// no claims as to its fitness for any purpose whatsoever. If you 
// wish to use this software you should first satisfy yourself that 
// it meets your requirements.
//
// URL:   http://www.cascade.org.uk/software/php/auth/
// Email: davidw@cascade.org.uk

// Modified 7/6/2001 RE
// Added so that authentication module returns groups that user belongs
// to once authenticated

// workaround for funky behaviour of nested includes with older
// versions of php


require_once(dirname(__FILE__)."/config.php");
if (defined("AUTH_CAS") && AUTH_CAS==TRUE)
    include_once('CAS/CAS.php');

    
class BasicAuthenticator {
    public $realm = "<private>";
    public $message;
    public $authenticated = -1;
    public $users;

    // not normally required, only for SQL authentication
    public $grps = FALSE;

    public function __construct($realm, $message = "Access Denied") {
        $this->realm = $realm;
        $this->message = $message;
    }
         
    
    function authenticate() {
        // Check if user has explicitly logged out
        $logoutCookie = isset($_COOKIE["ipplanNoAuth"]) ? $_COOKIE["ipplanNoAuth"] : "";

        // Three-phase logout handling to defeat browser credential caching:
        // Phase 1: cookie="yes" - reject, set cookie to "1" (first rejection)
        // Phase 2: cookie="1" - reject again (catches browser's auto-retry), set to "2"
        // Phase 3: cookie="2" - user has actually seen prompt, accept valid credentials
        //
        // This is necessary because browsers automatically retry 401 responses with
        // cached credentials BEFORE showing the login dialog to the user.

        $realm = $this->realm . " (Login Required)";

        if ($logoutCookie === "yes" || $logoutCookie === "1") {
            // Phase 1 or 2: Reject and increment counter
            $nextPhase = ($logoutCookie === "yes") ? "1" : "2";
            setcookie("ipplanNoAuth", $nextPhase, 0, "/");

            Header("WWW-Authenticate: Basic realm=\"$realm\"");
            if ($_SERVER["SERVER_PROTOCOL"]=="HTTP/1.0") {
               Header("HTTP/1.0 401 Unauthorized");
            }
            else {
               Header("Status: 401 Unauthorized");
            }
            $this->displayAuthFailurePage();
            exit();
        }

        if ($logoutCookie === "2") {
            // Phase 3: Browser's auto-retries have been rejected, now accept valid credentials
            if ($this->isAuthenticated() == 1) {
                // Valid credentials - clear cookie and allow access
                setcookie("ipplanNoAuth", "", time() - 3600, "/");
                Header("HTTP/1.0 200 OK");
                return $this->grps;
            } else {
                // Invalid credentials - send 401 again but keep in phase 3
                Header("WWW-Authenticate: Basic realm=\"$realm\"");
                if ($_SERVER["SERVER_PROTOCOL"]=="HTTP/1.0") {
                   Header("HTTP/1.0 401 Unauthorized");
                }
                else {
                   Header("Status: 401 Unauthorized");
                }
                $this->displayAuthFailurePage();
                exit();
            }
        }

        // Normal authentication (no logout cookie)
        if ($this->isAuthenticated() == 0) {
            Header("WWW-Authenticate: Basic realm=\"$this->realm\"");
            if ($_SERVER["SERVER_PROTOCOL"]=="HTTP/1.0") {
               Header("HTTP/1.0 401 Unauthorized");
            }
            else {
               Header("Status: 401 Unauthorized");
            }
            $this->displayAuthFailurePage();
            exit();
        }
        else {
            Header("HTTP/1.0 200 OK");
            return $this->grps;
        }
    }


    function displayAuthFailurePage() {
        // Get the base URL for assets
        $baseUrl = '';
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $baseUrl = dirname(dirname($_SERVER['SCRIPT_NAME']));
            if ($baseUrl == '/' || $baseUrl == '\\') {
                $baseUrl = '';
            }
        }

        // Determine current theme
        $theme = 'classic';
        if (isset($_COOKIE['ipplanTheme'])) {
            $theme = $_COOKIE['ipplanTheme'];
        }

        // Map theme to CSS file
        $themeCss = 'default.css';
        if ($theme == 'current-branch-dark' || $theme == '2026-dark') {
            $themeCss = 'current-branch-dark.css';
        } elseif ($theme == 'current-branch-light' || $theme == '2026-light') {
            $themeCss = 'current-branch-light.css';
        }

        $isCurrentBranch = ($themeCss != 'default.css');
        $loginUrl = $baseUrl . '/user/login.php';
        $homeUrl = $baseUrl . '/index.php';

        if ($isCurrentBranch) {
            // Current Branch themed page
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Required - IPPlan</title>
    <link rel="stylesheet" href="' . htmlspecialchars($baseUrl) . '/themes/' . htmlspecialchars($themeCss) . '">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-base, #1a1a2e);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .auth-card {
            background: var(--bg-card, #16213e);
            border: 1px solid var(--border-color, #0f3460);
            border-radius: 12px;
            padding: 48px;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .auth-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            background: var(--bg-card-alt, #1a1a2e);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-icon svg {
            width: 32px;
            height: 32px;
            stroke: var(--accent-primary, #0097a7);
        }
        .auth-title {
            color: var(--text-primary, #e8e8e8);
            font-size: 24px;
            font-weight: 600;
            margin: 0 0 16px;
        }
        .auth-message {
            color: var(--text-secondary, #a8a8a8);
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 32px;
        }
        .auth-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .auth-buttons a {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }
        .auth-buttons .btn-primary {
            background: #0097a7;
            color: #fff;
        }
        .auth-buttons .btn-primary:hover {
            background: #00acc1;
        }
        .auth-buttons .btn-secondary {
            background: transparent;
            color: #a8a8a8;
            border: 1px solid #0f3460;
        }
        .auth-buttons .btn-secondary:hover {
            background: #1a1a2e;
            color: #e8e8e8;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
        <h1 class="auth-title">Authentication Required</h1>
        <p class="auth-message">You need to enter a valid user-id and password to access data in this system.</p>
        <div class="auth-buttons">
            <a href="' . htmlspecialchars($loginUrl) . '" class="btn-primary" onclick="window.location.href=this.href;return false;">Login</a>
            <a href="' . htmlspecialchars($homeUrl) . '" class="btn-secondary" onclick="window.location.href=this.href;return false;">Home</a>
        </div>
    </div>
</body>
</html>';
        } else {
            // Classic theme - simpler styled page
            echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Required - IPPlan</title>
    <style>
        body {
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #fff;
            border: 1px solid #ccc;
            padding: 40px;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .auth-title {
            color: #333;
            font-size: 20px;
            margin: 0 0 16px;
        }
        .auth-message {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
            margin: 0 0 24px;
        }
        .auth-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .auth-buttons a {
            display: inline-block;
            padding: 10px 20px;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid #ccc;
            cursor: pointer;
        }
        .auth-buttons .btn-primary {
            background: #4a90d9;
            color: #fff;
            border-color: #4a90d9;
        }
        .auth-buttons .btn-primary:hover {
            background: #3a7fc8;
        }
        .auth-buttons .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }
        .auth-buttons .btn-secondary:hover {
            background: #e5e5e5;
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <h1 class="auth-title">Authentication Required</h1>
        <p class="auth-message">You need to enter a valid user-id and password to access data in this system.</p>
        <div class="auth-buttons">
            <a href="' . htmlspecialchars($loginUrl) . '" class="btn-primary" onclick="window.location.href=this.href;return false;">Login</a>
            <a href="' . htmlspecialchars($homeUrl) . '" class="btn-secondary" onclick="window.location.href=this.href;return false;">Home</a>
        </div>
    </div>
</body>
</html>';
        }
    }


    function addUser($user, $passwd) {
        $this->users["$user"] = $passwd;
    }
    
    
    function isAuthenticated() {
        // dummy server vars into new va called MY_SERVER_VARS
        // needed as vars might get updated if running on ISS
        $MY_SERVER_VARS = $_SERVER;

        if ($this->authenticated < 0) {
            // Check for encoded not plain text response
            // to fix php on Windows with ISAPI module
            // Contributed by Brian Epley
            if ( (!(isset($MY_SERVER_VARS["PHP_AUTH_USER"]))) &&
                    (!(isset($MY_SERVER_VARS["PHP_AUTH_PW"]))) &&
                    (isset($MY_SERVER_VARS['HTTP_AUTHORIZATION'])) )
            {
                list($MY_SERVER_VARS["PHP_AUTH_USER"],
                        $MY_SERVER_VARS["PHP_AUTH_PW"]) =
                    explode(':',
                            base64_decode(substr($MY_SERVER_VARS['HTTP_AUTHORIZATION'], 6)));
            }

            // always use PHP_AUTH_USER for basic authentication
            if(isset($MY_SERVER_VARS["PHP_AUTH_USER"])) {
                $this->authenticated = $this->validate($MY_SERVER_VARS["PHP_AUTH_USER"], $MY_SERVER_VARS["PHP_AUTH_PW"]);
            }
            else {
                $this->authenticated = 0;
            }
        }

        return $this->authenticated;
    }
    
    
    function validate($user, $passwd)
    {
        if (strlen(trim($user)) > 0 && strlen(trim($passwd)) > 0) {
            // Both $user and $password are non-zero length
            if (isset($this->users["$user"]) && $this->users["$user"] == $passwd) {
                return 1;
            }
        }
        return 0;
    }
}

class SQLAuthenticator extends BasicAuthenticator {

    // use different method here if using external authetication to take
    // into account that AUTH_VAR may be different
    function isAuthenticated() {
        // dummy server vars into new va called MY_SERVER_VARS
        // needed as vars might get updated if running on ISS
        $MY_SERVER_VARS = $_SERVER;
        // if you have auth issues, uncomment below var_dump line and look at screen output AFTER
        // you have authenticated. look for variables like REMOTE_USER, PHP_AUTH_USER or
        // some variable that contains the user-id that authenticated. This is the setting
        // that needs to be added in the config.php file for AUTH_VAR
        //var_dump($_SERVER);

        if ($this->authenticated < 0) {
            // Check for encoded not plain text response
            // to fix php on Windows with ISAPI module
            // Contributed by Brian Epley
            if ( (!(isset($MY_SERVER_VARS["PHP_AUTH_USER"]))) &&
                    (!(isset($MY_SERVER_VARS["PHP_AUTH_PW"]))) &&
                    (isset($MY_SERVER_VARS['HTTP_AUTHORIZATION'])) )
            {
                list($MY_SERVER_VARS["PHP_AUTH_USER"],
                        $MY_SERVER_VARS["PHP_AUTH_PW"]) =
                    explode(':',
                            base64_decode(substr($MY_SERVER_VARS['HTTP_AUTHORIZATION'], 6)));
            }

            // Added lines for CAS Authentication and bypass HTTP's REMOTE_USER var
            // (define  AUTH_CAS_SERVER  AUTH_CAS_PORT  in config.php)
            if(defined("AUTH_CAS") && AUTH_CAS==TRUE && !(AUTH_INTERNAL)) {
                phpCAS::client(CAS_VERSION_1_0, AUTH_CAS_SERVER, AUTH_CAS_PORT,"");
                phpCAS::forceAuthentication();
                $user=phpCAS::getUser();
                $this->authenticated = $this->validate($user, "");
            }
            elseif(isset($MY_SERVER_VARS[AUTH_VAR])) {
                // PHP_AUTH_PW could be undefined if AUTH_INTERNAL = FALSE
                // this is OK as it is taken care of in the validate()
                // method
                $this->authenticated = $this->validate($MY_SERVER_VARS[AUTH_VAR], $MY_SERVER_VARS["PHP_AUTH_PW"]);
            }
            else {
                $this->authenticated = 0;
            }
        }

        return $this->authenticated;
    }


    function validate($user, $passwd) {

       $ds = ADONewConnection(DBF_TYPE);    # create a connection
       if (DBF_PERSISTENT) {
          $ds->PConnect(DBF_HOST, DBF_USER, DBF_PASSWORD, DBF_NAME) or
              die("Could not connect to database");
       }
       else {
          $ds->Connect(DBF_HOST, DBF_USER, DBF_PASSWORD, DBF_NAME) or
              die("Could not connect to database");
       }

       $ds->SetFetchMode(ADODB_FETCH_ASSOC);


       // only check password for internal authentication
       if (AUTH_INTERNAL) {
          $passwd=crypt($passwd, 'xq');
          $result=$ds->Execute("SELECT usergrp.grp AS grp
                                FROM users, usergrp
                                WHERE users.userid=".$ds->qstr($user)." AND
                                   users.password=".$ds->qstr($passwd)." AND
                                   users.userid=usergrp.userid");
       }
       else {
          $result=$ds->Execute("SELECT usergrp.grp AS grp
                                FROM users, usergrp
                                WHERE users.userid=".$ds->qstr($user)." AND
                                   users.userid=usergrp.userid");
       }
       
        
       // found a user, updates grps
       // result should always be lowercase from database
       $i=0;
       if ($result) { 
          while ($row = $result->FetchRow()) {
             $grp[$i++]=$row["grp"];
          }
          if (empty($grp)) {
             $ret=0;
          }
          else {
             $this->grps=$grp;
             $ret=1;
          }

          $result->Close(); 
          $ds->Close();
       }
       // error
       else {
          $ret=0;
       }

       return $ret;
   }
}

?>
