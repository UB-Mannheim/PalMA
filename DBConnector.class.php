<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Authors: Alexander Wagner, Stefan Weil

// Test whether the script was called directly (used for unit test).
if (!isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = (sizeof(get_included_files()) == 1);

function trace($text)
{
    error_log("palma: $text");
}

class DBConnector extends SQLite3
{
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

-- TODO: Entry 'userid' in table 'window' should refer to user(userid):
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
    public function __construct($filename = false)
    {
        if (!$filename) {
            $filename = dirname(__FILE__) . '/palma.db';
        }
        trace("db file = $filename");
        parent::__construct($filename);

        // Wait up to 10000 ms when the database is locked.
        $this->busyTimeout(10000);

        // Create any missing tables.
        $this->exec(self::SQL_CREATE_TABLES);
    }

    public function resetTables()
    {
        $this->exec(self::SQL_RESET_TABLES . self::SQL_CREATE_TABLES);
    }

    public function countWindows()
    {
        $numRows = $this->querySingle('SELECT count(*) FROM window WHERE state="active"');
        return $numRows;
    }

    public function nextID()
    {
        // Find the first unused monitor section and return its number.
        $quadrant_ids = array(1, 2, 3, 4);
        $window_db_ids = array();

        $window_keys = @$this->query('SELECT DISTINCT(id) FROM window');
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

    public function ipAddress()
    {
        $ip = 'unknown';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Server is hidden behind a proxy.
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            // Client has direct access to the server.
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function addUser($username, $address, $device = 'laptop')
    {
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

    public function delUser($username, $address)
    {
        // Remove an existing user with his/her address from the database.
        // TODO: Support more than one address for a given username.
        $ip = $this->ipAddress();
        $userid = $this->querySingle("SELECT userid from user where name='$username'");
        // Kill VNC connection associated with the user
        $this->deleteVNCWindow($userid);
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

    public function enableUser($username)
    {
        $state = $this->exec("UPDATE user SET enabled=1 WHERE name='$username'");
        if (!$state) {
            trace("enableUser($username) failed");
        }
    }

    public function getUsers()
    {
        $users = array();
        $rows = $this->query("SELECT name FROM user");
        while ($row = $rows->fetchArray(SQLITE3_ASSOC)) {
            array_push($users, $row['name']);
        }
        $rows->finalize();
        return $users;
    }

    public function getWindows()
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

    public function getWindowIDBySection($section)
    {
        $id = $this->querySingle("SELECT win_id FROM window WHERE section='$section'");
        return $id;
    }

    public function getVNC_ClientInfo()
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

    public function getWindowState($window_id)
    {
        $state = @$this->querySingle('SELECT state FROM window WHERE win_id="'.$window_id.'"');
        return $state;
    }

    public function setWindowState($window_id, $state)
    {
        $this->exec('UPDATE window SET state="'.$state.'" WHERE win_id="'.$window_id.'"');
    }

    public function insertWindow($window)
    {
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

    public function deleteWindow($window_id)
    {
        $this->exec('DELETE FROM window WHERE win_id="'.$window_id.'"');
    }

    public function deleteVNCWindow($userid)
    {
        $winid = $this->querySingle('SELECT win_id FROM window WHERE handler="vnc" AND userid="'.$userid.'"');
        require_once('control.php');
        wmClose($winid);
        $this->deleteWindow($winid);
        //$this->exec('DELETE FROM window WHERE handler="vnc" AND userid="'.$userid.'"');
    }

    public function deleteDebug($table, $id, $gt)
    {
        $this->exec('DELETE FROM '.$table.' WHERE '.$id.' >"'.$gt.'"');
    }

    public function updateWindow($window_id, $field, $value)
    {
        $this->exec('UPDATE window SET '.$field.'="'.$value.'" WHERE win_id="'.$window_id.'"');
    }
}

function set_constants()
{
    // Get some constants from a configuration file.

    $conf = parse_ini_file("palma.ini");
    //~ print_r($conf);

    if (!$conf) {
        trace("Error, parsing of palma.ini failed");
    } else {
        // Entries in group 'display'.
        if (array_key_exists('id', $conf)) {
            define('CONFIG_DISPLAY', $conf['id']);
        }
        if (array_key_exists('ssh', $conf)) {
            define('CONFIG_SSH', $conf['ssh']);
        }

        // Entries in group 'general'.
        if (array_key_exists('password', $conf)) {
            define('CONFIG_PASSWORD', $conf['password']);
        }
        if (array_key_exists('pin', $conf)) {
            define('CONFIG_PIN', $conf['pin']);
        }
        if (array_key_exists('stationname', $conf)) {
            define('CONFIG_STATIONNAME', $conf['stationname']);
        }
        if (array_key_exists('theme', $conf)) {
            define('CONFIG_THEME', $conf['theme']);
        }

        // Entries in group 'path'.
        if (array_key_exists('control_file', $conf)) {
            define('CONFIG_CONTROL_FILE', $conf['control_file']);
        }
        if (array_key_exists('policy', $conf)) {
            define('CONFIG_POLICY', $conf['policy']);
        }
        if (array_key_exists('start_url', $conf)) {
            define('CONFIG_START_URL', $conf['start_url']);
        }
        if (array_key_exists('upload_dir', $conf)) {
            define('CONFIG_UPLOAD_DIR', $conf['upload_dir']);
        }
        if (array_key_exists('institution_url', $conf)) {
            define('CONFIG_INSTITUTION_URL', $conf['institution_url']);
        }
    }

    // Set default values for constants missing in the configuration file.
    if (!defined('CONFIG_CONTROL_FILE')) {
        // By default we use control.php.
        define('CONFIG_CONTROL_FILE', CONFIG_START_URL . 'control.php');
    }
    if (!defined('CONFIG_DISPLAY')) {
        // By default we use X display :0.
        define('CONFIG_DISPLAY', ':0');
    }
    if (!defined('CONFIG_PASSWORD')) {
        // Enable password authentisation by default.
        define('CONFIG_PASSWORD', true);
    }
    if (!defined('CONFIG_PIN')) {
        // Enable PIN authentisation by default.
        define('CONFIG_PIN', true);
    }
    // There is no default value for CONFIG_POLICY.
    // There is no default value for CONFIG_SSH.
    // There is no default value for CONFIG_START_URL.
    if (!defined('CONFIG_STATIONNAME')) {
        // Use the host name as the default station name.
        define('CONFIG_STATIONNAME', gethostname());
    }
    if (!defined('CONFIG_THEME')) {
        // The default theme is demo/simple.
        define('CONFIG_THEME', 'demo/simple');
    }
    if (!defined('CONFIG_UPLOAD_DIR')) {
        // The default theme is /var/www/html/uploads.
        define('CONFIG_UPLOAD_DIR', '/var/www/html/uploads');
    }
}

set_constants();

if ($unittest[__FILE__]) {
    // Run unit test.

    print('CONFIG_CONTROL_FILE = ' . CONFIG_CONTROL_FILE . "\n");
    print('CONFIG_DISPLAY = ' . CONFIG_DISPLAY . "\n");
    print('CONFIG_PASSWORD = ' . CONFIG_PASSWORD . "\n");
    print('CONFIG_PIN = ' . CONFIG_PIN . "\n");
    print('CONFIG_POLICY = ' . CONFIG_POLICY . "\n");
    if (defined('CONFIG_SSH')) {
        print('CONFIG_SSH = ' . CONFIG_SSH . "\n");
    }
    print('CONFIG_START_URL = ' . CONFIG_START_URL . "\n");
    print('CONFIG_STATIONNAME = ' . CONFIG_STATIONNAME . "\n");
    print('CONFIG_THEME = ' . CONFIG_THEME . "\n");
    print('CONFIG_UPLOAD_DIR = ' . CONFIG_UPLOAD_DIR . "\n");

    function dbModifiedCallback()
    {
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
