<?php

// Copyright (C) 2014-2023 Universitätsbibliothek Mannheim
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

require_once 'globals.php';
debug('login.php: begin');

require_once 'i12n.php';
require_once 'DBConnector.class.php';

$dbcon = palma\DBConnector::getInstance();

$errtext = false;

$username = '';
$pin = '';
$posted_pin = '';
if (isset($_REQUEST['pin'])) {
  $posted_pin = escapeshellcmd($_REQUEST['pin']);
}

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
    debug("login.php: access denied for user '$username', wrong pin $posted_pin");
    $errtext = addslashes(__('Invalid PIN.'));
  } else {
    // Successfully checked username, password and PIN.
    monitor("login.php: access granted for user '$username'");
    debug("login.php: access granted for user '$username'");
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
    debug('login.php: ' . CONFIG_START_URL);
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
    <title><?=addslashes(__("PalMA &ndash; Login"))?></title>

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
            &ndash; <?=addslashes(__("Login"))?>
          </legend>
          <div id="login_fields">
            <div class="pure-control-group">
              <label for="username"><?=addslashes(__("User name"))?></label>
              <input id="username" name="username" type="text" value="<?=htmlspecialchars($username)?>">
            </div>
            <?php
            if (CONFIG_PASSWORD) {
              ?>
              <div class="pure-control-group">
                <label for="userpassword"><?=addslashes(__("Password"))?></label>
                <input id="userpassword" name="userpassword" type="password">
              </div>
              <?php
            }
            if (CONFIG_PIN) {
              ?>
              <div class="pure-control-group">
                <label for="pin"><?=addslashes(__("PIN"))?></label>
                <input id="pin" name="pin" type="text" value="<?=htmlspecialchars($posted_pin)?>">
              </div>
              <?php
            }
            ?>
          </div>
          <div class="pure-controls">
            <button type="submit" class="pure-button pure-button-primary">
              <?=addslashes(__("Log in"))?><i class="fa fa-sign-in"></i>
            </button>
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
