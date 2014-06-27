<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Authors: Alexander Wagner, Stefan Weil

// Test whether the script was called directly (used for unit test).
if (!isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = (sizeof(get_included_files()) == 1);

function trace($text) {
    error_log("palma: $text");
}

class DBConnector extends SQLite3
{
    private $_WINDOWS = array();
    private $_WINDOW_COUNT;

    const SQL_CREATE_TABLES = <<< eod

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

CREATE TABLE IF NOT EXISTS window (
  id INTEGER PRIMARY KEY,
  win_id VARCHAR (3),
  section INTEGER,
  state VARCHAR (10),
  file VARCHAR (255),
  handler VARCHAR (255),
  userid INTEGER REFERENCES user(userid),
  date DATETIME
);
eod;

    const SQL_RESET_TABLES = <<< eod
            DROP TABLE address;
            DROP TABLE setting;
            DROP TABLE IF EXISTS settings;
            DROP TABLE user;
            DROP TABLE window;
eod;

    // TODO: allow additional flags for constructor:
    // $flags = SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE
    // $encryption_key
    public function __construct($filename = false) {
        if (!$filename) {
            $filename = dirname(__FILE__) . '/palma.db';
        }
        trace("db file = $filename");
        parent::__construct($filename);

        // Wait up to 10000 ms when the database is locked.
        $this->busyTimeout(10000);

        // Create any missing tables.
        $this->exec(self::SQL_CREATE_TABLES);

        $q_init = $this->query('SELECT MAX(id) FROM window WHERE state="active"');

        /* if($q_init == true) {
            $this->_WINDOW_COUNT = $this->countWindows();
            $this->_WINDOWS = $this->getWindows();
        }  */
    }

    public function createTables() {
        $this->exec($SQL_CREATE_TABLES);
    }

    public function resetTables() {
        $this->exec(self::SQL_RESET_TABLES . self::SQL_CREATE_TABLES);
    }

    public function countWindows() {
        $numRows = $this->querySingle('SELECT count(*) FROM window WHERE state="active"');
        return $numRows;
    }

    public function nextID() {
        // Find the first unused monitor section and return its number.
        $quadrant_ids = array(1, 2, 3, 4);
        $window_db_ids = array();

        $window_keys = @$this->query('SELECT DISTINCT(id) FROM window');
        $row = array();
        while ($row = $window_keys->fetchArray()) {
            array_push($window_db_ids, $row['id']);
        }
        $window_keys->finalize();
        //~ trace("ids in QD " . serialize($quadrant_ids));
        //~ trace("ids in DB " . serialize($window_db_ids));

        $next_ids = array_values(array_diff($quadrant_ids, $window_db_ids));
        //~ trace("ids in NXT " . serialize($next_ids));
        if (count($next_ids) > 0) {
            $next = $next_ids[0];
        } else {
            $next = $this->querySingle('SELECT MAX(id) FROM window') + 1;
        }
        return $next;
    }

    public function ipAddress() {
        $ip = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Server is hidden behind a proxy.
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            // Client has direct access to the server.
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function addUser($username, $address, $device = 'laptop') {
        // Add a new user with his/her address and the device to the database.
        // TODO: Support more than one address for a given username.
        $this->exec("INSERT OR IGNORE INTO user VALUES (NULL, '$username', 1, 0)");
        $usercount = $this->querySingle("SELECT COUNT(*) FROM user");
        $userid = $this->querySingle("SELECT userid from user where name='".$username."'");
        $this->exec("INSERT INTO address VALUES ('$userid', '$address', '$device')");
        trace("user $username connected with $device, $usercount user(s) now connected");
        if ($usercount == 1) {
            // First user connected. Always enable this person.
            $this->enableUser($username);
        }
    }

    public function delUser($username, $address) {
        // Remove an existing user with his/her address from the database.
        // TODO: Support more than one address for a given username.
        $ip = $this->ipAddress();
        $userid = $this->querySingle("SELECT userid from user where name='$username'");
        $this->exec("DELETE FROM address WHERE userid = '$userid' AND address = '$ip'");
        // TODO: Remove user only when no address refers to it.
        $this->exec("DELETE FROM user WHERE userid = '$userid'");
        $usercount = $this->querySingle("SELECT COUNT(*) FROM user");
        trace("user $username disconnected, $usercount user(s) now connected");
        if ($usercount == 0) {
            // Last user disconnected.
            // Clean some tables, just to be sure that nothing is left.
            $this->exec("DELETE FROM address; DELETE FROM window;");
        }
    }

    public function enableUser($username) {
        $state = $this->exec("UPDATE user SET enabled=1 WHERE name='$username'");
        if (!$state) {
            trace("enableUser($username) failed");
        }
    }

    public function getUsers() {
        $users = array();
        $rows = $this->query("SELECT name FROM user");
        while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
            array_push($users, $row['name']);
        }
        $rows->finalize();
        return $users;
    }

    public function getWindows() {

        $window_objs = array();
        $windows = @$this->query('SELECT * FROM window');
        while ($row = $windows->fetchArray()) {
            array_push($window_objs, $row);
        }
        $windows->finalize();

        return $window_objs;
    }

    public function getWindowsOrderBy($field, $order) {

        $window_objs = array();
        $windows = @$this->query("SELECT * FROM window ORDER BY $field $order");
        while ($row = $windows->fetchArray()) {
            array_push($window_objs, $row);
        }
        $windows->finalize();

        return $window_objs;
    }

    public function getWindowIDBySection($section) {
        $id = $this->querySingle("SELECT win_id FROM window WHERE section='$section'");
        return $id;
    }

    public function getVNC_ClientInfo() {

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

    public function getState_Window($window_id) {
        $state = @$this->querySingle('SELECT state FROM window WHERE win_id="'.$window_id.'"');
        return $state;
    }

    public function setState_Window($window_id, $state) {
        $state = $this->exec('UPDATE window SET state="'.$state.'" WHERE win_id="'.$window_id.'"');
    }

    public function insertWindow($window) {
        // transfer ob complete window object/array necessary
        $sql = 'INSERT INTO window (id, win_id, section, state, file, handler, userid, date) ' .
                'VALUES ' . '("' .
                $window[0] . '", "' . $window[1] . '", "' .
                $window[2] . '", "' . $window[3] . '", "' .
                $window[4] . '", "' . $window[5] . '", "' .
                $window[6] . '", "' . $window[7] . '")';
        $new = $this->exec($sql);
        trace("sql=$sql, result=$new");
    }

    public function deleteWindow($window_id) {
        $delete = $this->exec('DELETE FROM window WHERE win_id="'.$window_id.'"');
    }

    /*
    public function deleteVNCWindow($vnc_id) {
        $delete = $this->exec('DELETE FROM window WHERE file="'.$vnc_id.'"');
    }
    */

    public function deleteDebug($table, $id, $gt) {
        $delete = $this->exec('DELETE FROM '.$table.' WHERE '.$id.' >"'.$gt.'"');
    }

    public function updateWindow($window_id, $field, $value) {
        $update = $this->exec('UPDATE window SET '.$field.'="'.$value.'" WHERE win_id="'.$window_id.'"');
    }

}

if ($unittest[__FILE__]) {
    // Run unit test.

    function dbModifiedCallback() {
        echo("Triggered callback\n");
    }

    //~ var_dump($_SERVER);
    $db = new DBConnector('palma-test.db');
    $db->resetTables();
    //~ http://stackoverflow.com/questions/1964233/does-sqlite3-support-a-trigger-to-automatically-update-an-updated-on-datetime
    //~ https://www.sqlite.org/lang_createtrigger.html
    // TODO: Test database triggers (can be used in db.php).
    //~ $db->createFunction('dbModifiedCallback', 'dbModifiedCallback', 0);
    // {AFTER | BEFORE} {DELETE | INSERT | UPDATE } ON table
    //~ $db->exec("CREATE TRIGGER dbchange AFTER UPDATE ON user
                //~ BEGIN
                  //~ dbModifiedCallback();
                //~ END");
    //~ $db = new DBConnector('palma.db');
    echo "Tables=" . $db->querySingle('SELECT count(*) FROM sqlite_master WHERE type="table"') . "\n";
    var_dump($db->query("SELECT * from sqlite_master"));
    $address = $db->ipAddress();
    $db->addUser('testuser1', $address);
    //~ $db->addUser('testuser2', $address);
    //~ $db->addUser('testuser3', $address);
    //~ $db->addUser('testuser4', $address);
    $users = $db->getUsers();
    $db->enableUser('testuser1');
    var_dump($users);
    $db->delUser('haenger', $address);
    $db->delUser('ivwz', $address);
    $db->delUser('skrieg', $address);
    $db->delUser('testuser1', $address);
    $db->delUser('testuser2', $address);
    $db->delUser('testuser3', $address);
    $db->delUser('testuser4', $address);
    echo "Query=" . $db->querySingle('select name from user') . "\n";
    echo "Query=" . $db->querySingle('select count(name) from user') . "\n";
    echo "Query=" . $db->querySingle('select value from setting where key="layout"') . "\n";
    $db->close();

    //~ $db = new DBConnector('palma.db');
    //~ $users = $db->getUsers();
    //~ var_dump($users);
    //~ $db->close();
}

?>
