<?php

function showLogin()
{
    //if (isset($_SERVER['HTTP_REFERER'])) {
    //    error_log("auth.php referred by " . $_SERVER['HTTP_REFERER']);
    //}
    $header = 'Location: login.php';
    $separator = '?';
  if (isset($_REQUEST['lang'])) {
      $header = $header . $separator . 'lang=' . $_REQUEST['lang'];
      $separator = '&';
  }
  if (isset($_REQUEST['pin'])) {
      $header = $header . $separator . 'pin=' . $_REQUEST['pin'];
      $separator = '&';
  }
    header($header);

    exit;
}

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['username'])) {
    // empty session
    showLogin();
} else {
    require_once('DBConnector.class.php');
    $dbcon = new palma\DBConnector();
  if (!$dbcon->checkUser($_SESSION['username'])) {
      // empty db (e.g. after PalMA restart) or old session
      showLogin();
  }
}
