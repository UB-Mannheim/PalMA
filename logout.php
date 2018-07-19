<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Datenbankanbindung initialisieren.
require_once('DBConnector.class.php');
$dbcon = new palma\DBConnector();

session_start();
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $dbcon->delUser($username, $dbcon->ipAddress());
}
session_destroy();
header('Location: index.php');

require_once('globals.php');
monitor('logout.php: logout');

/*
    // Alternate code, currently unused, not working with Apache2 proxy.
    $hostname = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/login.php');
*/
