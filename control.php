<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Authors: Alexander Wagner, Stefan Weil

// TODO: Authentisierung. Funktioniert hier nicht mit auth.php,
// daher vielleicht über Datenbankabfrage.

// Test whether the script was called directly (used for unit test). Use some
// heuristics to detect whether we are not running in a web application.
if (isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = !isset($_SERVER['SERVER_NAME']);

require_once('globals.php');

if (!$unittest[__FILE__]) {
    trace("QUERY_STRING=" . $_SERVER['QUERY_STRING']);
}

// Connect to database and get configuration constants.
require_once('DBConnector.class.php');
$db = new palma\DBConnector();

function displayCommand($cmd)
{
    if (defined('CONFIG_SSH')) {
        $cmd = CONFIG_SSH . " 'DISPLAY=" . CONFIG_DISPLAY . " $cmd'";
    } else {
        $cmd = "DISPLAY=" . CONFIG_DISPLAY . " HOME=/var/www $cmd";
    }

    monitor("control.php: displayCommand $cmd");

    $result = shell_exec($cmd);
    trace("cmd=$cmd, result=$result");
    return $result;
}

function wmClose($id)
{
    monitor("control.php: wmClose");
    // Close window gracefully.
    displayCommand("wmctrl -i -c $id");
}

function wmHide($id)
{
    monitor("control.php: wmHide");
    // Hide window. This is done by moving it to desktop 1.
    displayCommand("wmctrl -i -r $id -t 1");
}

function wmShow($id)
{
    monitor("control.php: wmShow");
    // Show window on current desktop.
    displayCommand("wmctrl -i -R $id");
}

function windowListOnScreen()
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

function windowList()
{
    monitor("control.php: windowList");
    $list = array();
    global $db;

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

function closeAll()
{
    monitor("control.php: closeAll");
    global $db;

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

function doLogout($username)
{
    monitor("control.php: doLogout");
    global $db;
    if ($username == 'ALL') {
        // Terminate all user connections and reset system.
        closeAll();
        $db->resetTables();
    }
}

function clearUploadDir()
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

function setLayout($layout)
{
    monitor("control.php: setLayout $layout");
    // Set layout of team display. Layouts are specified by their name.
    // We use names like g1x1, g2x1, g1x2, ...
    // Restore the last layout if the function is called with a null argument.

    global $db;

    if ($layout == null) {
        $layout = $db->querySingle("SELECT value FROM setting WHERE key='layout'");
    } else {
        $db->exec("UPDATE setting SET value='$layout' WHERE key='layout'");
    }

    trace("layout $layout");

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

    $dim = $geom[$layout];

    // Make sure that desktop 0 is selected.
    displayCommand('wmctrl -s 0');

    // Get width and height of desktop.
    $desktops = displayCommand("wmctrl -d");
    // $desktop looks like this.
    // 0  * DG: 1600x900  VP: 0,0  WA: 0,27 1600x873  Arbeitsfläche 1
    $fields = preg_split("/[\n ]+/", $desktops);
    $geom = preg_split("/x/", $fields[3]);
    $screenWidth = $geom[0];
    $screenHeight = $geom[1];

    // Show all windows for the current layout which are not disabled.

    $maxSection = count($dim);
    // Get ordered list of all windows from the database.
    $windows = $db->getWindows();
    foreach ($windows as $w) {
        $id = $w['win_id'];
        $enabled = $w['state'] == 'active';
        $section = $w['section'];
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

function activateControls($windowhex)
{
    global $db;
    $fhandler = $db->querySingle("SELECT handler FROM window WHERE win_id='$windowhex'");
    trace("activateControls for handler $fhandler");
    monitor("control.php: activateControls $fhandler");
}

function addNewWindow($db, $new)
{
    // Add a new window to the monitor. This window either uses the first
    // unused section or it will be hidden.

    monitor('control.php: addNewWindow ' . serialize($new));
    trace('addNewWindow ' . serialize($new));
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
        trace('warning: no new window found');
        return;
    }

    trace("new window $new_window_id");

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
        //~ trace("hide new window $new_window_id");
    }

    // $new['file'] = $active_window; (?)

    // TODO: check how to insert the userid for all content, not just vnc.
    // Perhaps better add to array in upload.php ?
    $userid = "";
    $queryid = $db->querySingle('SELECT user.userid FROM user WHERE user.name="' . $new['userid'] .'"');
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
}

function createNewWindowSafe($db, $w)
{
    $inFile = $w['file'];
    if (!file_exists($inFile)) {
        trace("".escapeshellarg($inFile)." is not a file, aborting display");
        return;
    }
  
    require_once('FileHandler.class.php');
    list ($handler, $targetFile) =
      palma\FileHandler::getFileHandler($inFile);
    trace("file is now $targetFile, its handler is $handler");

    $window = array(
      "id" => "",
      "win_id" => "",
      "name" => "",
      "state" => "",
      "file" => $targetFile,
      "handler" => $handler,
      "userid" => "",
      "date" => $w['date']);
  
    createNewWindow($db, $window);
}


function createNewWindow($db, $w)
{
    // '$w' already contains 'file', 'handler' and 'date'.
    // 'win_id', 'section' have to be defined afterwards.

    $handler = $w['handler'];
    // TODO: use escapeshellarg() for filename.
    $filename = $w['file'];

    $cmd = "$handler ".escapeshellarg($filename);
    displayCommand("/usr/bin/nohup $cmd >/dev/null 2>&1 &");

    addNewWindow($db, $w);
    monitor("control.php: createNewWindow");
}

function processRequests($db)
{
    monitor("control.php: processRequests");
    if (array_key_exists('window', $_REQUEST)) {
        // All windows related commands must start with window=.

        $windownumber = $_REQUEST['window'];
        $windowname = false;
        $windowhex = 0;
        // TODO: $win_id und $windowname können vermutlich zusammengefasst werden.
        $win_id = 0;

        if ($windownumber != 'vncwin') {
            // This is the normal case.
            // Special handling is needed when called with window=vncwin, see below.
            $window = $windownumber - 1;

            $win_id = $db->getWindowIDBySection($windownumber);
            $windowlist = windowList();

            if (count($windowlist) == 0) {
                trace("no window found for command");
            } else {
                // TODO: improve test whether window exists.
                $windowname = $windowlist[$window];
                $windowhex = hexdec($windowname);
            }
        }

        if ($windowname && array_key_exists('key', $_REQUEST)) {
            $key = $_REQUEST['key'];
            trace("key '$key' in window '$windownumber'");
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
            $key = $_REQUEST['keydown'];
            trace("keydown '$key' in window '$windownumber'");
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
            $key = $_REQUEST['keyup'];
            trace("keyup '$key' in window '$windownumber'");
            // activateControls($windowhex);
            //~ wmShow($windowname);
            //~ displayCommand("xdotool windowfocus $windowhex keyup $key");
        }

        if (array_key_exists('delete', $_REQUEST)) {
            $delete = str_replace(" ", "\ ", addslashes($_REQUEST['delete']));
            trace("delete=$delete, close window $windownumber");

            if (file_exists($delete)) {
                trace("+++ DELETE FILE FROM WEBINTERFACE +++");
                unlink($delete);
            } elseif ($delete == "VNC") {
                trace("+++ DELETE VNC Client FROM DAEMON +++");
                // call via daemon: ?window=vncwin&delete=VNC&vncid=123
                trace("vnc delete in control");
                $win_id = $_REQUEST['vncid'];   // = hexWindow in database, but not on screen
                trace("VNC cia Daemon ... id=$win_id");
            } elseif (strstr($delete, "http")) {
                trace("+++ DELETE Browserwindow +++");
            } elseif (preg_match('/(^\w{3,}@\w{1,})/', $delete)) {
                trace("+++ DELETE VNC Client FROM WEBINTERFACE +++");
                // call via webinterface
                $win_id = $db->querySingle("SELECT win_id FROM window WHERE file='$delete' AND handler='vnc'");
                trace("DELETE VNC Window with ID=$win_id FROM Database ::
                SELECT win_id FROM window WHERE file='$delete' AND handler='vnc'");
            } else {
                trace("Unhandled delete for '$delete'");
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
            trace("toggle window $windownumber, id=$win_id, state=$state");
            if ($state == "active") {
                wmHide($win_id);
                $db->setWindowState($win_id, "inactive");
            } else {
                wmShow($win_id);
                $db->setWindowState($win_id, "active");
            }
        }
    } elseif (array_key_exists('layout', $_REQUEST)) {
        setLayout($_REQUEST['layout']);
    } elseif (array_key_exists('logout', $_REQUEST)) {
        doLogout($_REQUEST['logout']);
    } elseif (array_key_exists('newVncWindow', $_REQUEST)) {
        // TODO: Better write new code for VNC window.
        addNewWindow($db, unserialize(urldecode($_REQUEST['newVncWindow'])));
    } elseif (array_key_exists('newWindow', $_REQUEST)) {
        createNewWindowSafe($db, unserialize(urldecode($_REQUEST['newWindow'])));
    }

    if (array_key_exists('switchWindows', $_REQUEST)) {
        $before = $_REQUEST['before'];
        $after = $_REQUEST['after'];
        trace("switching $before and $after");

        // exchange section
        $win_id1 = $db->getWindowIDBySection($before);
        $win_id2 = $db->getWindowIDBySection($after);

        $db->updateWindow($win_id1, 'section', $after);
        $db->updateWindow($win_id2, 'section', $before);

        trace("++updating database $win_id1 section=$after");
        trace("++updating database $win_id2 section=$before");

        // Update display (no layout change).
        setLayout(null);
    }

    if (array_key_exists('openURL', $_REQUEST)) {
        $openURL = $_REQUEST['openURL'];
        trace("openURL $openURL");

        // If URL leads to pdf file, download it and treat as upload
        if (preg_match("/.(pdf|PDF)$/", $openURL)) {
            trace("openURL $openURL is a pdf file. Downloading it.");
            $date = time();
            $temp_name = basename($openURL);
            $temp_dir = "/tmp/palma_$date";
            shell_exec("mkdir $temp_dir && wget $openURL -P $temp_dir/");

            $_FILES['file']['name'] = "$temp_name";
            $_FILES['file']['tmp_name'] = "$temp_dir/$temp_name";
            $_FILES['file']['error'] = "downloaded_from_url";

            trace("Handing over to upload.php");
            include 'upload.php';
        } else {
            $dt = new DateTime();
            $date = $dt->format('Y-m-d H:i:s');
            $window = array(
                "id" => "",
                "win_id" => "",
                "section" => "",
                "state" => "",
                "file" => $openURL,
                // "handler" => "iceweasel --new-window",
                "handler" => "/usr/bin/midori -e show-navigationbar=false -a",
                "userid" => "",
                "date" => $date
            );
            createNewWindow($db, $window);
        }
    }

    // TODO: chef if query redundant?
    if (array_key_exists('closeAll', $_REQUEST)) {
        $close = $_REQUEST['closeAll'];
        trace("close all windows $close");
        closeAll();
    }
} // processRequests

processRequests($db);

if ($unittest[__FILE__]) {
    // Experimental: Get function call from startx.
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
    if (isset($_GET) && count($_GET) > 0) {
        foreach ($_GET as $key => $value) {
            // Only defined actions allowed.
            if ($key == "doLogout") {
                doLogout($value);
            }
        }
    } else {
        // Run unit test.
        echo("<p>Running unit test</p>");
        trace("Running unit test for " . __FILE__);
        trace("Finished unit test");
    }
}
