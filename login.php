<?php

// Copyright (C) 2014-2018 Universitätsbibliothek Mannheim
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

// Connect to database and get configuration constants.
require_once('DBConnector.class.php');
$dbcon = new palma\DBConnector();

require_once('i12n.php');
require_once('globals.php');

$errtext = false;

function getDevice()
{
    // Try to determine the user's device type. The device which is
    // returned is used to select the matching icon for the user list.
    $agent = $_SERVER["HTTP_USER_AGENT"];
    if (preg_match('/iPad/', $agent)) {
        $device = 'tablet';
    } elseif (preg_match('/iPhone/', $agent)) {
        $device = 'mobile';
    } elseif (preg_match('/Android/', $agent)) {
        $device = 'android';
    } elseif (preg_match('/Linux/', $agent)) {
        $device = 'linux';
    } elseif (preg_match('/OS X/', $agent)) {
        $device = 'apple';
    } elseif (preg_match('/Windows/', $agent)) {
        $device = 'windows';
    } else {
        $device = 'laptop';
    }
    return $device;
}

function checkCredentials($username, $password)
{
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
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_PROXY, $proxy);
    curl_setopt($curl, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
    curl_setopt($curl, CURLOPT_PROXYUSERPWD, "$username:$password");
    //~ trace("Start curl");
    $out = curl_exec($curl);
    curl_close($curl);

    if (!$out) {
        trace("curl failed for user '$username'");
        $errtext = __('Invalid credentials!');
    } elseif (preg_match('/404 Not Found/', $out)) {
        return true;
    } elseif (preg_match('/Could not resolve proxy/', $out)) {
        trace('proxy authentisation was not possible');
        $errtext = __('Cannot check credentials, sorry!');
    } elseif (preg_match('/Cache Access Denied/', $out)) {
        trace("access denied for user '$username'");
        $errtext = __('Invalid credentials!');
    } else {
        trace("access not possible for user '$username'");
        $errtext = __('Invalid credentials!');
    }
    return false;
}

    $username = '';
    $pin = '';
    $posted_pin = '';
if (isset($_REQUEST['pin'])) {
    $posted_pin = $_REQUEST['pin'];
}

require_once('globals.php');
monitor("login.php: page loaded");
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    $username = escapeshellcmd($_POST['username']);
    $password = '';
    if (CONFIG_PASSWORD) {
        // The password must not be escaped.
        $password = $_POST['userpassword'];
    }
    if (CONFIG_PIN) {
        $posted_pin = escapeshellcmd($_POST['pin']);
        $pin = $dbcon->querySingle("SELECT value FROM setting WHERE key = 'pin'");
    }

    if (CONFIG_PASSWORD && !checkCredentials($username, $password)) {
        monitor("login.php: access denied for user '$username'");
        // Invalid username or password.
    } elseif (CONFIG_PIN && ($pin != $posted_pin)) {
        monitor("login.php: access denied for user '$username': invalid pin");
        trace("access denied for user '$username', wrong pin $posted_pin");
        $errtext = __('Invalid PIN.');
    } else {
        // Successfully checked username, password and PIN.
        monitor("login.php: access granted for user '$username'");
        trace("access granted for user '$username'");
        $_SESSION['username'] = $username;
        $_SESSION['address'] = $dbcon->ipAddress();
        $_SESSION['pin'] = $pin;
        $_SESSION['starturl'] = CONFIG_START_URL;
        $_SESSION['monitor'] = CONFIG_STATIONNAME;
        $dbcon->addUser($username, $dbcon->ipAddress(), getDevice());

       // Weiterleitung zur geschützten Startseite
        if ($_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.1') {
            if (php_sapi_name() == 'cgi') {
                header('Status: 303 See Other');
            } else {
                header('HTTP/1.1 303 See Other');
            }
        }

        header('Location: ' . CONFIG_START_URL);
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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=__("PalMA &ndash; Login")?></title>

<link rel="icon" href="theme/<?=CONFIG_THEME?>/favicon.ico" type="image/x-icon">
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
        <img src="theme/<?=CONFIG_THEME?>/palma-logo-67x25.png" alt="PalMA" height="25"/>
        &ndash; <?=__("Login")?>
    </legend>
    <div id="login_fields">
        <div class="pure-control-group">
            <label for="username"><?=__("User name")?></label
            ><input id="username" name="username" type="text" value="<?=$username?>">
        </div>
<?php
if (CONFIG_PASSWORD) {
    ?>
        <div class="pure-control-group">
            <label for="userpassword"><?=__("Password")?></label
            ><input id="userpassword" name="userpassword" type="password">
        </div>
    <?php
}
if (CONFIG_PIN) {
    ?>
        <div class="pure-control-group">
            <label for="pin"><?=__("PIN")?></label
            ><input id="pin" name="pin" type="text" value="<?=$posted_pin?>">
        </div>
    <?php
}
?>
    </div>
    <div class="pure-controls">
        <button type="submit" class="pure-button pure-button-primary"><?=__("Log in")?><i class="fa fa-sign-in"></i></button>
    </div>
</fieldset>

</form>

<?php
if ($errtext) {
    echo("<p>$errtext</p>");
}
if (defined('CONFIG_POLICY')) {
    echo('<div class="policy">' . CONFIG_POLICY . '</div>');
}
?>

</div>

</body>
</html>
