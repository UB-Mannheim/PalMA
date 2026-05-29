<?php

namespace palma;

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Authors: Alexander Wagner, Stefan Weil

class DBConnector extends \SQLite3
{
  private const SQL_CREATE_TABLES = <<< eod
  BEGIN EXCLUSIVE TRANSACTION;
  PRAGMA foreign_keys = ON;

  CREATE TABLE IF NOT EXISTS setting (
    key VARCHAR (10) PRIMARY KEY,
    value VARCHAR (20)
  );
  INSERT OR IGNORE INTO setting VALUES ('layout', 'g1x1');
  INSERT OR IGNORE INTO setting VALUES ('pin', '');

  CREATE TABLE IF NOT EXISTS user (
    userid INTEGER PRIMARY KEY,
    name VARCHAR (30) UNIQUE,
    count INTEGER,
    enabled INTEGER
  );

  -- Table with user name, IP address and device type (laptop, tablet, mobile).
  CREATE TABLE IF NOT EXISTS address (
    userid INTEGER,
    address VARCHAR (30),
    device VARCHAR (6),
    FOREIGN KEY(userid) REFERENCES user(userid)
  );

  -- userid INTEGER REFERENCES user(userid)
  -- The software currently does not handle this correctly, so we had to remove
  -- the reference because it fails with pragma foreign_keys.
  CREATE TABLE IF NOT EXISTS window (
    id INTEGER PRIMARY KEY,
    win_id VARCHAR (3),
    section INTEGER,
    state VARCHAR (10),
    file VARCHAR (255),
    handler VARCHAR (255),
    userid INTEGER,
    date DATETIME
  );
  END TRANSACTION;
  eod;

  private const SQL_RESET_TABLES = <<< eod
  DROP TABLE address;
  DROP TABLE setting;
  DROP TABLE IF EXISTS settings;
  DROP TABLE user;
  DROP TABLE window;
  eod;

  private static ?DBConnector $instance = null;

  public static function getInstance(): DBConnector
  {
    if (is_null(self::$instance)) {
      self::$instance = new DBConnector('palma.db');
    }
    return self::$instance;
  }

  private function __construct(string $filename)
  {
    parent::__construct($filename);

    // Wait up to 10000 ms when the database is locked.
    $this->busyTimeout(10000);

    // Create any missing tables.
    $this->exec(self::SQL_CREATE_TABLES);
  }

  public function resetTables(): void
  {
    $this->exec(self::SQL_RESET_TABLES . self::SQL_CREATE_TABLES);
  }

  public function countWindows(): int
  {
    $numRows = $this->querySingle('SELECT count(*) FROM window WHERE state="active"');
    return $numRows;
  }

  public function nextID(): int
  {
    // Find the first unused monitor section and return its number.
    $quadrant_ids = array(1, 2, 3, 4);
    $window_db_ids = array();

    $window_keys = @$this->query('SELECT DISTINCT(id) FROM window');
    while ($row = $window_keys->fetchArray()) {
      array_push($window_db_ids, $row['id']);
    }
    $window_keys->finalize();
    $next_ids = array_values(array_diff($quadrant_ids, $window_db_ids));
    if (count($next_ids) > 0) {
      $next = $next_ids[0];
    } else {
      $next = $this->querySingle('SELECT MAX(id) FROM window') + 1;
    }
    return $next;
  }

  public function ipAddress(): string
  {
    $ip = 'unknown';
    if (isset($_SERVER['HTTP_X_FORWARED_FOR'])) {
      // Server is hidden behind a proxy.
      // Proxy for loopback ip might indicate spoofing
      if (!in_array($_SERVER['HTTP_X_FORWARED_FOR'], array('127.0.0.1', '::1'))) {
        $ip = $_SERVER['HTTP_X_FORWARED_FOR'];
      }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
      // Client has direct access to the server.
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  public function checkPermission(): bool
  {
    // Check whether the remote IP address is allowed to run commands.
    // Only localhost or hosts of registered users are allowed.
    $ip = $this->ipAddress();
    // Currently PalMA also makes local connections.
    $ip_list = array('127.0.0.1', '::1');
    $rows = $this->query("SELECT address FROM address");
    while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
      array_push($ip_list, $row['address']);
    }
    $rows->finalize();
    $allowed = in_array($ip, $ip_list);
    return $allowed;
  }

  public function addUser(string $username, string $address, string $device = 'laptop'): void
  {
    // Add a new user with his/her address and the device to the database.
    $this->exec("INSERT OR IGNORE INTO user VALUES (NULL, '$username', 1, 0)");
    $usercount = intval($this->querySingle("SELECT COUNT(*) FROM user"));
    $userid = $this->querySingle("SELECT userid from user where name='" . $username . "'");
    $this->exec("INSERT INTO address VALUES ('$userid', '$address', '$device')");
    require_once 'globals.php';
    debug("DBConnector::addUser: user $username connected with $device, $usercount user(s) now connected");
    if ($usercount == 1) {
      // First user connected. Always enable this person.
      $this->enableUser($username);
    }
  }

  public function delUser(string $username, string $address): void
  {
    // Remove an existing user with his/her address from the database.
    $ip = $this->ipAddress();
    $userid = $this->querySingle("SELECT userid from user where name='$username'");
    if (!is_null($userid)) {
      // Kill VNC connection associated with the user
      $this->deleteVNCWindow($userid);
      $this->exec("DELETE FROM user WHERE userid = '$userid'");
    }
    $this->exec("DELETE FROM address WHERE userid = '$userid' AND address = '$ip'");
    $usercount = intval($this->querySingle("SELECT COUNT(*) FROM user"));
    require_once 'globals.php';
    debug("DBConnector::delUser: user $username disconnected, $usercount user(s) now connected");
    if ($usercount == 0) {
      // Last user disconnected.
      // Clean some tables, just to be sure that nothing is left.
      $this->exec("DELETE FROM address; DELETE FROM window;");
    }
  }

  public function enableUser(string $username): void
  {
    $state = $this->exec("UPDATE user SET enabled=1 WHERE name='$username'");
    if (!$state) {
      require_once 'globals.php';
      debug("DBConnector::enableUser: failed for $username");
    }
  }

  /** @return array<string> */
  public function getUsers(): array
  {
    $users = array();
    $rows = $this->query("SELECT name FROM user");
    while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
      array_push($users, $row['name']);
    }
    $rows->finalize();
    return $users;
  }

  public function checkUser(string $username): bool
  {
    $users = $this->getUsers();
    // empty db (e.g. after PalMA restart) or old session
    if (count($users) === 0 || !in_array($username, $users)) {
      return false;
    }
    return true;
  }

  /** @return array<array<string>> */
  public function getWindows(): array
  {
    // Get list of all windows, ordered by their section.
    $window_objs = array();
    $windows = @$this->query('SELECT * FROM window ORDER BY section ASC');
    while ($row = $windows->fetchArray()) {
      array_push($window_objs, $row);
    }
    $windows->finalize();

    return $window_objs;
  }

  public function getWindowIDBySection(string $section): ?string
  {
    $id = $this->querySingle("SELECT win_id FROM window WHERE section='$section'");
    return $id;
  }

  /** @return array<array<string>> */
  public function getVNCClientInfo(): array
  {

    $info = array();

    $i = @$this->query('SELECT * FROM window WHERE handler="vnc"');
    while ($row = $i->fetchArray()) {
      array_push($info, $row);
    }
    $i->finalize();

    return $info;
  }

  /*
     public function getVNC_ClientWindowIDs() {
     $ids = array();
     $rows = @$this->query('SELECT win_id FROM window WHERE handler="vnc"');
     while ($row = $rows->fetchArray()) {
     array_push($ids, $row['win_id']);
     }
     $rows->finalize();
     return $ids;
     }
   */

  public function getWindowState(string $window_id): string
  {
    $state = @$this->querySingle('SELECT state FROM window WHERE win_id="' . $window_id . '"');
    return $state;
  }

  public function setWindowState(string $window_id, string $state): void
  {
    $this->exec('UPDATE window SET state="' . $state . '" WHERE win_id="' . $window_id . '"');
  }

  /**
   * @param array<string> $window
   */
  public function insertWindow(array $window): void
  {
    // transfer ob complete window object/array necessary
    $sql = 'INSERT INTO window (id, win_id, section, state, file, handler, userid, date) ' .
           'VALUES ' . '("' .
           $window[0] . '", "' . $window[1] . '", "' .
           $window[2] . '", "' . $window[3] . '", "' .
           $window[4] . '", "' . $window[5] . '", "' .
           $window[6] . '", "' . $window[7] . '")';
    $new = $this->exec($sql);
    require_once 'globals.php';
    debug("DBConnector::insertWindow: sql=$sql, result=$new");
  }

  public function deleteWindow(string $window_id): void
  {
    $this->exec('DELETE FROM window WHERE win_id="' . $window_id . '"');
  }

  public function deleteVNCWindow(string $userid): void
  {
    $winid = $this->querySingle('SELECT win_id FROM window WHERE handler="vnc" AND userid="' . $userid . '"');
    if (is_null($winid)) {
      return;
    }
    require_once 'globals.php';
    wmClose($winid);
    $this->deleteWindow($winid);
    //$this->exec('DELETE FROM window WHERE handler="vnc" AND userid="'.$userid.'"');
  }

  public function deleteDebug(string $table, string $id, string $gt): void
  {
    $this->exec('DELETE FROM ' . $table . ' WHERE ' . $id . ' >"' . $gt . '"');
  }

  public function updateWindow(?string $window_id, string $field, string $value): void
  {
    if (is_null($window_id)) {
      return;
    }
    $this->exec('UPDATE window SET ' . $field . '="' . $value . '" WHERE win_id="' . $window_id . '"');
  }
}
