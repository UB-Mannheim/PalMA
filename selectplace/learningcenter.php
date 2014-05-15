<?php

/*
Copyright (C) 2014 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Author: Stefan Weil

Todo:

* Konfiguration der Monitore, Auswahl der richtigen URL
*/

// Support localisation.
require_once('../gettext.php');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA &ndash; <?=_('Select workplace')?></title>

<!-- @http://purecss.io/forms/ -->
<link rel="stylesheet" href="../pure-min.css">

<!-- Customized style. -->
<link rel="stylesheet" href="../palma.css">

</head>

<body>

<div id="monitor_mask">

<h2>PalMA &ndash; <?=_('Select workplace')?></h2>

<form class="pure-form pure-form-aligned" name="form" action="../index.php" method="post">
<table class="monitorchoice">
    <tr><td><input name="monitor" type="hidden" value=""></td></tr>
    <tr>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" onclick="document.form.monitor.value='<?=_('Conference room')?>'"><?=_('Test monitor')?></button></td>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" onclick="document.form.monitor.value='Monitor 2'" disabled><?=_('Mannheim table')?> 1</button></td>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" onclick="document.form.monitor.value='3'" disabled>Leselounge 2</button></td>
    </tr>
    <tr>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" onclick="document.form.monitor.value='4'" disabled>Monitor 4</button></td>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" value="monitor5" disabled>Monitor 5</button></td>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" value="monitor6" disabled>Monitor 6</button></td>
    </tr>
    <tr>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" value="monitor7" disabled>Monitor 7</button></td>
        <td align="center"><div class="selectdisplay"></div><button class="pure-button pure-button-primary pure-input-rounded" value="monitor8" disabled>Monitor 8</button></td>
    </tr>
</table>
</form>
</div>

<?php
     session_start();
     session_destroy();

  # TODO: User sessions should be authorized to one display only.
  # If they select this page, they must not be allowed to select
  # a new display without being logged out.

  # Show authorized user name and allow logout.
  $username = false;
  if (isset($_SESSION['username'])) {
    # PHP session based authorization.
    $username = $_SESSION['username'];
  } elseif (isset($_SERVER["PHP_AUTH_USER"])) {
    # .htaccess basic authorization.
    $username = $_SERVER["PHP_AUTH_USER"];
  }
  if ($username) {
    echo("<p>$username <a href=\"logout.php\">abmelden</a></p>");
  }
?>

</body>
</html>
