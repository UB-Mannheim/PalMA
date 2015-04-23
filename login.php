<?php

// Copyright (C) 2014, 2015 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// This file implements user authorization.

// PalMA installation can authorize users by a PIN, by a
// user name and by a password.

// The PIN is a four digit random number which is changed for
// every new session.

// Authorization with a user name and a password requires code
// which implements the authorization mechanism (for example
// proxy based authorization, LDAP, Shibboleth, fixed password).
// Password authorization can optionally be disabled.

    // Enable or disable passwords. Set to true to enable passwords.

    // Connect to database.
    require_once('DBConnector.class.php');
    $dbcon = new DBConnector();

    require_once('gettext.php');

    $conf = parse_ini_file("palma.ini", true);
    $theme = $conf['general']['theme'];
    if (array_key_exists('password', $conf['general'])) {
        define("CONFIG_PASSWORD", $conf['general']['password']);
    } else {
        define("CONFIG_PASSWORD", true);
    }

    $errtext = false;

    function getDevice() {
        // Try to determine the user's device type. The device which is
        // returned is used to select the matching icon for the user list.
        $agent = $_SERVER["HTTP_USER_AGENT"];
        if (false) {
        } else if (preg_match('/iPad/', $agent)) {
            $device = 'tablet';
        } else if (preg_match('/iPhone/', $agent)) {
            $device = 'mobile';
        } else if (preg_match('/Android/', $agent)) {
            $device = 'android';
        } else if (preg_match('/Linux/', $agent)) {
            $device = 'linux';
        } else if (preg_match('/OS X/', $agent)) {
            $device = 'apple';
        } else if (preg_match('/Windows/', $agent)) {
            $device = 'windows';
        } else {
            $device = 'laptop';
        }
        return $device;
    }

  function checkCredentials($username, $password) {
    // Check username + password against fixed internal value and
    // external proxy with authentisation.

    global $errtext;

    $remote = $_SERVER['REMOTE_ADDR'];
    if ($username == 'chef' && $password == 'chef') {
        if ($remote == '::1' || $remote == '127.0.0.1' ||
            preg_match('/^134[.]155[.]36[.]/', $remote) &&
            $remote != '134.155.36.48') {
            // Allow test access for restricted remote hosts (localhost,
            // UB Mannheim library staff, but not via proxy server).
            // TODO: PalMA installations which are accessible from
            // the Internet may want to remove this test access.
            return true;
        } else {
            trace("Test access not allowed for IP address $remote");
            return false;
        }
    }

    if ($username == '' || $password == '') {
        // Don't allow empty user name or password.
        // Proxy authentisation can fail with empty values.
        trace("access denied for user '$username'");
        return false;
    }
    // TODO: testurl sollte auf einem lokalen Server liegen.
    $testurl = 'http://www.weilnetz.de/proxytest';
    $proxy = 'proxy.bib.uni-mannheim.de:3150';
    $curl = curl_init($testurl);
    curl_setopt($curl, CURLOPT_HEADER, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_PROXY, $proxy);
    curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
    curl_setopt($curl, CURLOPT_PROXYUSERPWD, "$username:$password");
    //~ trace("Start curl");
    $out = curl_exec($curl);
    curl_close($curl);

    if (!$out) {
        trace("curl failed for user '$username'");
        $errtext = _('Invalid credentials!');
    } else if (preg_match('/404 Not Found/', $out)) {
        trace("access granted for user '$username'");
        return true;
    } else if (preg_match('/Could not resolve proxy/', $out)) {
        trace('proxy authentisation was not possible');
        $errtext = _('Cannot check credentials, sorry!');
    } else if (preg_match('/Cache Access Denied/', $out)) {
        trace("access denied for user '$username'");
        $errtext = _('Invalid credentials!');
    } else {
        trace("access not possible for user '$username'");
        $errtext = _('Invalid credentials!');
    }
    return false;
  }

    $username = '';
    $posted_pin = '';
    if (isset($_REQUEST['pin'])) {
        $posted_pin = $_REQUEST['pin'];
    }

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    $username = escapeshellcmd($_POST['username']);
    $password = '';
    if (CONFIG_PASSWORD) {
        // The password must not be escaped.
        $password = $_POST['userpassword'];
    }
    $posted_pin = escapeshellcmd($_POST['pin']);
    $pin = $dbcon->querySingle("SELECT value FROM setting WHERE key = 'pin'");

    if (CONFIG_PASSWORD && !checkCredentials($username, $password)) {
        // Invalid username or password.
    } else if ($pin != $posted_pin) {
        $errtext = _('Invalid PIN.');
    } else {
        // Successfully checked username, password and PIN.
        $_SESSION['username'] = $username;
        $_SESSION['address'] = $dbcon->ipAddress();
        $_SESSION['pin'] = $pin;
        $_SESSION['starturl'] = $conf['path']['start_url'];
        $_SESSION['monitor'] = $conf['general']['stationname'];
        $dbcon->addUser($username, $dbcon->ipAddress(), getDevice());

       // Weiterleitung zur geschützten Startseite
       if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1') {
        if (php_sapi_name() == 'cgi') {
         header('Status: 303 See Other');
         }
        else {
         header('HTTP/1.1 303 See Other');
         }
        }

        header('Location: ' . $conf['path']['start_url']);
        exit;
    }
  }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html lang="de">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?=_("PalMA &ndash; Login")?></title>

<link rel="icon" href="theme/<?=$theme?>/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="pure-min.css">
<link rel="stylesheet" href="palma.css">

</head>

<!--

Copyright (C) 2014 Stefan Weil, Universitätsbibliothek Mannheim

TODO:
* Use 'placeholder' attribute for input fields.

-->

<body onLoad="document.forms.auth.username.focus()">

<div id="login_mask">

<form name="auth" class="pure-form pure-form-aligned" action="login.php" method="post">

<fieldset class="login">
    <legend>
        <img src="theme/<?=$theme?>/palma-logo-67x25.png" alt="PalMA" height="25"/>
        &ndash; <?=_("Login")?>
    </legend>
        <div class="pure-control-group">
            <label for="username"><?=_("User name")?></label>
            <input id="username" name="username" type="text" value="<?=$username?>">
        </div>
<?php
        if (CONFIG_PASSWORD) {
?>
        <div class="pure-control-group">
            <label for="userpassword"><?=_("Password")?></label>
            <input id="userpassword" name="userpassword" type="password">
        </div>
<?php
        }
?>
        <div class="pure-control-group">
            <label for="pin"><?=_("PIN")?></label>
            <input id="pin" name="pin" type="text" value="<?=$posted_pin?>">
        </div>
        <div class="pure-controls">
            <button type="submit" class="pure-button pure-button-primary"><?=_("Log in")?><i class="fa fa-sign-in"></i></button>
        </div>
</fieldset>

</form>

<?php
if ($errtext) {
    echo("<p>$errtext</p>");
}
?>

</div>

</body>
</html>
