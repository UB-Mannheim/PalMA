<?php

require_once 'globals.php';
debug('auth.php: begin');

if (!isset($_SESSION)) {
  session_start();
}

require_once 'DBConnector.class.php';
$db = palma\DBConnector::getInstance();

if (!isset($_SESSION['username'])) {
  // empty session
  showLogin();
} elseif (!$db->checkUser($_SESSION['username'])) {
  // empty db (e.g. after PalMA restart) or old session
  showLogin();
}
