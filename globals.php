<?php

// Copyright (C) 2014-2023 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

function confValue(string $name): ?string
{
  // Try setting constants from a configuration file.
  static $conf = null;
  if (is_null($conf)) {
    $conf_fn = 'palma.ini';
    if (!file_exists($conf_fn)) {
      $conf_fn = '/etc/palma.ini';
    }
    $conf = parse_ini_file($conf_fn);
  }
  if (array_key_exists($name, $conf)) {
    return $conf[$name];
  }
  return null;
}

// Entries in group 'display'.
define('CONFIG_DISPLAY', confValue('id') ?? ':1');
define('CONFIG_SSH', confValue('ssh'));

// Entries in group 'general'.
define('CONFIG_PASSWORD', confValue('password') ?? false);
define('CONFIG_PIN', confValue('pin') ?? true);
define('CONFIG_STATIONNAME', confValue('stationname') ??
       str_replace(array("\r", "\n", " "), '', `hostname -f`));
define('CONFIG_THEME', confValue('theme') ?? 'demo/simple');
define('CONFIG_BROWSER', confValue('browser'));
define('CONFIG_DEBUG', confValue('debug') ?? false);

// Entries in group 'path'.
define('CONFIG_START_URL', confValue('start_url') ??
       'http://' . str_replace(array("\r", "\n", " "), '', `hostname -f`) . '/');
define('CONFIG_POLICY', confValue('policy'));
define('CONFIG_CONTROL_FILE', confValue('control_file') ?? CONFIG_START_URL . 'control.php');
define('CONFIG_UPLOAD_DIR', confValue('upload_dir') ?? '/tmp/palma');
define('CONFIG_INSTITUTION_URL', confValue('institution_url') ?? '');

// Entries in group 'monitoring'.
define('CONFIG_MONITOR_URL', confValue('monitor_url') ?? null);

function getDevice(): string
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

function checkCredentials(string $username, string $password): bool
{
  // Check username + password against fixed internal value and
  // external proxy with authentisation.

  global $errtext;

  $remote = $_SERVER['REMOTE_ADDR'];
  if ($username == 'chef' && $password == 'chef') {
    if (
        $remote == '::1' || $remote == '127.0.0.1' ||
        preg_match('/^134[.]155[.]36[.]/', $remote) &&
        $remote != '134.155.36.48'
    ) {
      // Allow test access for restricted remote hosts (localhost,
      // UB Mannheim library staff, but not via proxy server).
      // TODO: PalMA installations which are accessible from
      // the Internet may want to remove this test access.
      return true;
    } else {
      trace("checkCredentials: Test access not allowed for IP address $remote");
      return false;
    }
  }

  if ($username == '' || $password == '') {
    // Don't allow empty user name or password.
    // Proxy authentisation can fail with empty values.
    trace("checkCredentials: access denied for user '$username'");
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
  //~ trace("checkCredentials: Start curl");
  $out = curl_exec($curl);
  curl_close($curl);

  if (!$out) {
    trace("checkCredentials: curl failed for user '$username'");
    $errtext = addslashes(__('Invalid credentials!'));
  } elseif (preg_match('/404 Not Found/', $out)) {
    return true;
  } elseif (preg_match('/Could not resolve proxy/', $out)) {
    trace('checkCredentials: proxy authentication was not possible');
    $errtext = addslashes(__('Cannot check credentials, sorry!'));
  } elseif (preg_match('/Cache Access Denied/', $out)) {
    trace("checkCredentials: access denied for user '$username'");
    $errtext = addslashes(__('Invalid credentials!'));
  } else {
    trace("checkCredentials: access not possible for user '$username'");
    $errtext = addslashes(__('Invalid credentials!'));
  }
  return false;
}

function showLogin(): void
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

function trace(string $text): void
{
  static $firstRun = true;
  if ($firstRun) {
    $firstRun = false;
    openlog("palma", LOG_PID, LOG_USER);
  }
  syslog(LOG_NOTICE, $text);
}

function debug(string $text): void
{
  if (CONFIG_DEBUG) {
    trace($text);
  }
}

function monitor(string $action): void
{
  if (is_null(CONFIG_MONITOR_URL)) {
    return;
  }

  debug("monitor $action");

  $ch = curl_init();
  $url = CONFIG_MONITOR_URL;

  curl_setopt_array(
      $ch,
      array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => CONFIG_MONITOR_URL . '/' . CONFIG_STATIONNAME . '/' . base64_encode($action),
      CURLOPT_USERAGENT => 'PalMA cURL Request'
      )
  );
  $resp = curl_exec($ch);
  curl_close($ch);
}

function displayCommand(string $cmd): ?string
{
  if (is_null(CONFIG_SSH)) {
    $cmd = "DISPLAY=" . CONFIG_DISPLAY . " HOME=/var/www $cmd";
  } else {
    $cmd = CONFIG_SSH . " 'DISPLAY=" . CONFIG_DISPLAY . " $cmd'";
  }

  monitor("control.php: displayCommand $cmd");

  // add directories with palma-browser to PATH
  $result = shell_exec('PATH=/usr/lib/palma:./scripts:$PATH ' . $cmd);
  trace("displayCommand: $cmd, result=$result");
  return $result;
}

function wmClose(string $id): void
{
  monitor("control.php: wmClose");
  // Close window gracefully.
  displayCommand("wmctrl -i -c $id");
}

function wmHide(string $id): void
{
  monitor("control.php: wmHide");
  // Hide window. This is done by moving it to desktop 1.
  displayCommand("wmctrl -i -r $id -t 1");
}

function wmShow(string $id): void
{
  monitor("control.php: wmShow");
  // Show window on current desktop.
  displayCommand("wmctrl -i -R $id");
}

/** @return array<string> */
function windowListOnScreen(): array
{
  monitor("control.php: windowListOnScreen");
  $list = array();
  $windows = explode("\n", displayCommand('wmctrl -l'));
  foreach ($windows as $w) {
    $field = explode(' ', $w);
    $id = $field[0];
    if ($id != '') {
      array_push($list, $id);
    }
  }
  return $list;
}

// simple list with content from database

/** @return array<string> */
function windowList(): array
{
  monitor("control.php: windowList");

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $list = array();

  // Get ordered list of all windows from the database.
  $windows = $db->getWindows();
  foreach ($windows as $w) {
    $id = $w['win_id'];
    if ($id != '') {
      array_push($list, $id);
    }
  }
  return $list;
}

function closeAll(): void
{
  monitor("control.php: closeAll");

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $windows_on_screen = windowListOnScreen();

  foreach ($windows_on_screen as $id) {
    wmClose($id);
    if ($db->getWindowState($id) != null) {
      $db->deleteWindow($id);
    }
  }

  // Remove any remaining window entries in database.
  $db->exec('DELETE FROM window');

  // Remove any remaining files in the upload directory.
  clearUploadDir();
}

function doLogout(string $username): void
{
  monitor("control.php: doLogout");

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  if ($username == 'ALL') {
    // Terminate all user connections and reset system.
    closeAll();
    $db->resetTables();
  }
}

function clearUploadDir(): void
{
  monitor("control.php: clearUploadDir");
  # Remove all files in the upload directory.
  if (is_dir(CONFIG_UPLOAD_DIR)) {
    if ($dh = opendir(CONFIG_UPLOAD_DIR)) {
      while (($file = readdir($dh)) !== false) {
        if ($file != "." and $file != "..") {
          unlink(CONFIG_UPLOAD_DIR . "/$file");
        }
      }
      closedir($dh);
    }
  }
}

function setLayout(?string $layout = null): void
{
  monitor("control.php: setLayout $layout");
  // Set layout of team display. Layouts are specified by their name.
  // We use names like g1x1, g2x1, g1x2, ...
  // Restore the last layout if the function is called with a null argument.

  trace("setLayout: $layout");

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $geom = array();
  $geom['g1x1'] = array(
    array(0, 0, 1, 1)
  );
  $geom['g2x1'] = array(
    array(0, 0, 2, 1), array(1, 0, 2, 1)
  );
  $geom['g1x2'] = array(
    array(0, 0, 1, 2), array(0, 1, 1, 2)
  );
  $geom['g1a2'] = array(
    array(0, 0, 2, 1), array(1, 0, 2, 2),
    array(1, 1, 2, 2)
  );
  $geom['g2x2'] = array(
    array(0, 0, 2, 2), array(1, 0, 2, 2),
    array(0, 1, 2, 2), array(1, 1, 2, 2)
  );

  if ($layout == null) {
    $layout = $db->querySingle("SELECT value FROM setting WHERE key='layout'");
  }

  // Make sure $layout is valid
  if (!array_key_exists($layout, $geom)) {
    trace("setLayout: layout invalid!");
  } else {
    $db->exec("UPDATE setting SET value='$layout' WHERE key='layout'");
    $dim = $geom[$layout];

    // Make sure that desktop 0 is selected.
    displayCommand('wmctrl -s 0');

    // Get width and height of desktop.
    $desktops = displayCommand("wmctrl -d");
    // $desktop looks like this.
    // 0  * DG: 1600x900  VP: 0,0  WA: 0,27 1600x873  Arbeitsfläche 1
    $fields = preg_split("/[\n ]+/", $desktops);
    $geom = preg_split("/x/", $fields[3]);
    $screenWidth = intval($geom[0]);
    $screenHeight = intval($geom[1]);

    // Show all windows for the current layout which are not disabled.

    $maxSection = count($dim);
    // Get ordered list of all windows from the database.
    $windows = $db->getWindows();
    foreach ($windows as $w) {
      $id = $w['win_id'];
      $enabled = $w['state'] == 'active';
      $section = intval($w['section']);
      if ($section >= 1 && $section <= $maxSection && $enabled) {
        // Show window, set size and position.
        $wi = $section - 1;
        $dx = $screenWidth / $dim[$wi][2];
        $dy = $screenHeight / $dim[$wi][3];
        $x = $dim[$wi][0] * $dx;
        $y = $dim[$wi][1] * $dy;
        wmShow($id);
        displayCommand("wmctrl -i -r $id -e 0,$x,$y,$dx,$dy");
      } else {
        // Hide window.
        wmHide($id);
      }
    }
  }
}

function activateControls(string $windowhex): void
{
  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $fhandler = $db->querySingle("SELECT handler FROM window WHERE win_id='$windowhex'");
  trace("activateControls: handler $fhandler");
  monitor("control.php: activateControls $fhandler");
}

/**
 * @param array<string,string> $new
 */
function addNewWindow(array $new): void
{
  // Add a new window to the monitor. This window either uses the first
  // unused section or it will be hidden.

  monitor('control.php: addNewWindow ' . serialize($new));
  trace('addNewWindow: ' . serialize($new));

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  // '$new' already contains 'file', 'handler' and 'date', as well as the
  // username for VNC connections only.
  // 'win_id', 'section' have to be defined afterwards.

  // Get new window. Wait up to 10 s for it.
  $t_total = 0;
  do {
    $window_ids_on_screen = windowListOnScreen();
    $windows_in_db = $db->getWindows();

    $existing_ids = array();
    $new_window_id = '';

    if (count($windows_in_db) > 0) {
      // Add db windows to existing_ids.
      foreach ($windows_in_db as $win) {
        $existing_ids[] = $win['win_id'];
      }

      $new_window = array_diff($window_ids_on_screen, $existing_ids);
      foreach ($new_window as $win_id) {
        if ($win_id != "") {
          $new_window_id = $win_id;
        }
      }
    } elseif (!empty($window_ids_on_screen)) {
      $new_window_id = $window_ids_on_screen[0];
    }
  } while (!$new_window_id && $t_total++ <= 10 && !sleep(1));

  if (!$new_window_id) {
    trace('addNewWindow: warning: no new window found');
    return;
  }

  trace("addNewWindow: new window $new_window_id");

  // Determine last assigned monitor section.
  //~ $max_section = $db->querySingle('SELECT MAX(section) FROM window');

  // Get first unused monitor section.
  $section = $db->nextID();

  // If all information is available, create window object.

  $new['id'] = $section;
  $new['section'] = $section;

  if ($section <= 4) {
    $new['state'] = "active";
  } else {
    // All sections are used, so there is no free one for the new window.
    $new['state'] = "inactive";
    // We could hide the new window immediately, but don't do it here:
    // Each new window will be shown in the middle of the screen.
    //~ wmHide($new_window_id);
    //~ trace("addNewWindow: hide new window $new_window_id");
  }

  // $new['file'] = $active_window; (?)

  // TODO: check how to insert the userid for all content, not just vnc.
  // Perhaps better add to array in upload.php ?
  $userid = "";
  $queryid = $db->querySingle('SELECT user.userid FROM user WHERE user.name="' . $new['userid'] . '"');
  if (!empty($queryid)) {
    $userid = $queryid;
  } else {
    $userid = "all";
  }

  $myWindow = array(
    $new['id'],
    $new_window_id,
    $new['section'],
    $new['state'],
    $new['file'],
    $new['handler'],
    $userid,
    $new['date']
  );

  // Save window in database.
  $db->insertWindow($myWindow);

  setLayout();
}

/**
 * @param array<string,string> $w
 */
function createNewWindowSafe(array $w): void
{
  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $inFile = $w['file'];
  if (!file_exists($inFile)) {
    trace("createNewWindowSafe: " . escapeshellarg($inFile) . " is not a file, aborting display");
    return;
  }

  require_once 'FileHandler.class.php';
  list ($handler, $targetFile) =
    palma\FileHandler::getFileHandler($inFile);
  trace("createNewWindowSafe: file is now $targetFile, its handler is $handler");

  $window = array(
    "id" => "",
    "win_id" => "",
    "name" => "",
    "state" => "",
    "file" => $targetFile,
    "handler" => $handler,
    "userid" => "",
    "date" => $w['date']);

  createNewWindow($window);
}

/**
 * @param array<string,string> $w
 */
function createNewWindow($w): void
{
  // '$w' already contains 'file', 'handler' and 'date'.
  // 'win_id', 'section' have to be defined afterwards.

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  $handler = $w['handler'];
  // TODO: use escapeshellarg() for filename.
  $filename = $w['file'];

  $cmd = "$handler " . escapeshellarg($filename);
  displayCommand("/usr/bin/nohup $cmd >/dev/null 2>&1 &");

  addNewWindow($w);
  monitor("control.php: createNewWindow");
}

function processRequests(): void
{
  monitor("control.php: processRequests");

  require_once 'DBConnector.class.php';
  $db = palma\DBConnector::getInstance();

  if (array_key_exists('window', $_REQUEST)) {
    // All windows related commands must start with window=.

    $windownumber = escapeshellcmd($_REQUEST['window']);
    $windowname = false;
    $windowhex = 0;
    // TODO: $win_id und $windowname können vermutlich zusammengefasst werden.
    $win_id = 0;

    if ($windownumber != 'vncwin') {
      // This is the normal case.
      // Special handling is needed when called with window=vncwin, see below.
      $window = intval($windownumber) - 1;

      $win_id = $db->getWindowIDBySection($windownumber);
      $windowlist = windowList();

      if (count($windowlist) == 0) {
        trace("processRequests: no window found for command");
      } elseif (is_numeric($window) && count($windowlist) <= $window) {
        trace("processRequests: window $window is out of bounds");
      } elseif (!is_numeric($window)) {
        trace("processRequests: unhandled window $window");
      } else {
        trace("processRequests: command window");
        $windowname = $windowlist[$window];
        $windowhex = hexdec($windowname);
      }
    }

    if ($windowname && array_key_exists('key', $_REQUEST)) {
      $key = escapeshellcmd($_REQUEST['key']);
      trace("processRequests: key '$key' in window '$windownumber'");
      wmShow($windowname);
      // activateControls($windowhex);
      // displayCommand("xdotool windowfocus $windowhex key $key");

      // trying mousemove and click for better vnc control
      displayCommand("xdotool mousemove --window $windowhex 100 100 " .
                     "key $key");
    }

    if ($windowname && array_key_exists('keydown', $_REQUEST)) {
      // TODO: keydown is currently mapped to key because we had problems
      // with sticking keys (no keyup seen). This should be fixed by a
      // better event handling.
      $key = escapeshellcmd($_REQUEST['keydown']);
      trace("processRequests: keydown '$key' in window '$windownumber'");
      wmShow($windowname);
      // activateControls($windowhex);
      // displayCommand("xdotool windowfocus $windowhex key $key");

      // trying mousemove and click for better vnc control
      displayCommand("xdotool mousemove --window $windowhex 100 100 " .
                     "key $key");
      //~ displayCommand("xdotool windowfocus $windowhex keydown $key");
    }

    if ($windowname && array_key_exists('keyup', $_REQUEST)) {
      // TODO: keyup is currently ignored, see comment above.
      $key = escapeshellcmd($_REQUEST['keyup']);
      trace("processRequests: keyup '$key' in window '$windownumber'");
      // activateControls($windowhex);
      //~ wmShow($windowname);
      //~ displayCommand("xdotool windowfocus $windowhex keyup $key");
    }

    if (array_key_exists('delete', $_REQUEST)) {
      $delete = addslashes($_REQUEST['delete']);
      trace("processRequests: delete='$delete', close window $windownumber");

      if ($delete == "VNC") {
        trace("processRequests: delete vnc window");
        // call via daemon: ?window=vncwin&delete=VNC&vncid=123
        $win_id = escapeshellcmd($_REQUEST['vncid']);   // = hexWindow in database, but not on screen
        trace("VNC via Daemon ... id=$win_id");
      } elseif (strstr($delete, "http")) {
        trace("processRequests: delete browser window");
      } elseif (preg_match('/(^\w{3,}@\w{1,})/', $delete)) {
        trace("processRequests: delete vnc client from webinterface");
        // call via webinterface
        $win_id = $db->querySingle("SELECT win_id FROM window WHERE file='$delete' AND handler='vnc'");
      } else {
        // Restrict deletion to files known in the db.
        // TODO: check if given file and section match the values in the DB,
        // but currently, both those values can be ambiguous
        $file_in_db = $db->querySingle("SELECT id FROM window WHERE file='$delete'");
        $delete = str_replace(" ", "\ ", $delete);
        trace("processRequests: file in db: $file_in_db");
        if ($file_in_db) {
          if(file_exists($delete)) {
            trace("processRequests: delete file $delete");
            unlink($delete);
          }
        } else {
          trace("processRequests: given file not present in database!");
        }
      }
      wmClose($win_id);
      $db->deleteWindow($win_id);
    }

    if (array_key_exists('closeOrphans', $_REQUEST)) {
      // win_ids in db
      $windows_in_db = $db->getWindows();
      $db_ids = array();

      if (count($windows_in_db) > 0) {
        foreach ($windows_in_db as $win) {
          array_push($db_ids, $win['win_id']);
        }
      }

      // win_ids on screen
      $screen_ids = windowListOnScreen();

      // orphaned windows
      $orphan_ids = array_diff($screen_ids, $db_ids);

      if (count($orphan_ids) > 0) {
        // close windows on screen not existing in database
        foreach ($orphan_ids as $id) {
          wmClose($id);
        }
      }
    }

    if (array_key_exists('toggle', $_REQUEST)) {
      // Change window state from visible to invisible and vice versa.
      $state = $db->getWindowState($win_id);
      trace("processRequests: toggle window $windownumber, id=$win_id, state=$state");
      if ($state == "active") {
        wmHide($win_id);
        $db->setWindowState($win_id, "inactive");
      } else {
        wmShow($win_id);
        $db->setWindowState($win_id, "active");
      }
    }
  } elseif (array_key_exists('layout', $_REQUEST)) {
    setLayout(escapeshellcmd($_REQUEST['layout']));
  } elseif (array_key_exists('logout', $_REQUEST)) {
    doLogout($_REQUEST['logout']);
  } elseif (array_key_exists('newVncWindow', $_REQUEST)) {
    // TODO: Better write new code for VNC window.
    addNewWindow(unserialize(urldecode($_REQUEST['newVncWindow'])));
  } elseif (array_key_exists('newWindow', $_REQUEST)) {
    createNewWindowSafe(unserialize(urldecode($_REQUEST['newWindow'])));
  }

  if (array_key_exists('switchWindows', $_REQUEST)) {
    $before = escapeshellcmd($_REQUEST['before']);
    $after = escapeshellcmd($_REQUEST['after']);
    trace("processRequests: switchWindows $before -> $after");

    // exchange section
    $win_id1 = $db->getWindowIDBySection($before);
    $win_id2 = $db->getWindowIDBySection($after);

    $db->updateWindow($win_id1, 'section', $after);
    $db->updateWindow($win_id2, 'section', $before);

    debug("processRequests: updating database $win_id1 section=$after");
    debug("processRequests: updating database $win_id2 section=$before");

    // Update display (no layout change).
    setLayout();
  }

  if (array_key_exists('openURL', $_REQUEST)) {
    $openURL = escapeshellcmd($_REQUEST['openURL']);
    trace("processRequests: openURL $openURL");

    // If URL leads to pdf file, download it and treat as upload
    $headers = get_headers($openURL, PHP_MAJOR_VERSION < 8 ? 1 : true);
    if ($headers["Content-Type"] == "application/pdf") {
      debug("processRequests: url seems to lead to a pdf file, so downloading it...");
      $temp_name = basename($openURL);
      $temp_dir = "/tmp";
      file_put_contents("$temp_dir/$temp_name", file_get_contents($openURL));
      $mimetype = mime_content_type("$temp_dir/$temp_name");
      debug("processRequests: mimetype is $mimetype");
      if ($mimetype == "application/pdf") {
        $_FILES['file']['name'] = "$temp_name";
        $_FILES['file']['tmp_name'] = "$temp_dir/$temp_name";
        $_FILES['file']['error'] = "downloaded_from_url";
        debug("processRequests: handing over to upload.php");
        include 'upload.php';
      } else {
        debug("processRequests: deleting file");
        unlink("$temp_dir/$temp_name");
      }
    } else {
      $dt = new DateTime();
      $date = $dt->format('Y-m-d H:i:s');
      $window = array(
        "id" => "",
        "win_id" => "",
        "section" => "",
        "state" => "",
        "file" => $openURL,
        "handler" => "palma-browser",
        "userid" => "",
        "date" => $date
      );
      createNewWindow($window);
    }
  }

  // TODO: check if query redundant?
  if (array_key_exists('closeAll', $_REQUEST)) {
    $close = $_REQUEST['closeAll'];
    trace("processRequests: closeAll $close");
    closeAll();
  }
}
