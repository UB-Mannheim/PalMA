<?php

// Copyright (C) 2014-2023 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Poll up to 300 s for changes in the database and return its data in
// JSON format. The previous data is passed via URL request (?json=DATA).

require_once 'globals.php';
debug('db.php: begin');

require_once 'DBConnector.class.php';
$dbcon = palma\DBConnector::getInstance();

$remote = $_SERVER['REMOTE_ADDR'];
$isAllowed = false;

$newJSON = '{}';
$oldJSON = '';

if (!empty($_REQUEST['json'])) {
  $oldJSON = $_REQUEST['json'];
  $oldJSONarr = json_decode($oldJSON, true);
  if ($oldJSONarr != null) {
    array_walk_recursive($oldJSONarr, function (&$value, $key) {
      if (is_string($value) && preg_match('/^http/', $value)) {
        $value = rawurlencode($value);
      }
    });
    $oldJSON = json_encode($oldJSONarr);
  }
  debug("db.php: old $oldJSON");
}

for ($t = 0; $t < 300; $t++) {
  //~ echo("waiting for db change...<br>");
  //~ $array = ['username' => 'stweil', 'quux' => 'baz'];
  //~ $newJSON = json_encode($array);

  $database = array();

  $table = $dbcon->query('select * from setting');
  $data = array();
  while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
    array_push($data, $row);
  }
  $database['setting'] = $data;

  $table = $dbcon->query('select * from address');
  $data = array();
  while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
    array_push($data, $row);
    $isAllowed = $isAllowed || ($row['address'] == $remote);
  }
  if (!$isAllowed) {
    // Some unauthorized host tried to read the database.
    // Don't return any data.
    break;
  }
  $database['address'] = $data;

  $table = $dbcon->query('select * from user');
  $data = array();
  while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
    array_push($data, $row);
  }
  $database['user'] = $data;

  $table = $dbcon->query('select * from window');
  $data = array();
  while ($row = $table->fetchArray(SQLITE3_ASSOC)) {
    array_push($data, $row);
  }
  $database['window'] = $data;

  //~ $newJSON = json_encode($database, JSON_PRETTY_PRINT);
  array_walk_recursive($database, function (&$value, $key) {
    if (is_string($value) && preg_match('/^http/', $value)) {
      $value = rawurlencode($value);
    }
  });
  $newJSON = json_encode($database);
  if ($oldJSON != $newJSON) {
    debug("db.php: new $newJSON");
    break;
  }

  sleep(1);
}

touch("/var/run/palma/last_activity");

echo($newJSON);
