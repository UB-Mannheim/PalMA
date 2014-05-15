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

// initialize database
require_once('DBConnector.class.php');
$dbcon = new DBConnector();

if (!$unittest[__FILE__]) {
    trace("QUERY_STRING=" . $_SERVER['QUERY_STRING']);
}

// TODO: Get display dimensions automatically:
// xdpyinfo
//   dimensions:    1600x900 pixels (423x238 millimeters)
// xrandr
//   Screen 0: minimum 320 x 200, current 1600 x 900, maximum 8192 x 8192
// xwininfo -root
//   -geometry 1600x900+0+0

if (file_exists('palma.ini') && !$unittest[__FILE__]) {
    // Get configuration from ini file.
    if (isset($_SERVER['HTTP_REFERER'])) {
        $url = dirname($_SERVER['HTTP_REFERER']) . '/' . basename($_SERVER['PHP_SELF']);
        trace("alt = $url");
    }
    $conf = parse_ini_file("palma.ini", true);
    $display = $conf['display']['id'];
    if (isset($conf['display']['ssh'])) {
        $ssh = $conf['display']['ssh'];
    }
    $screenWidth = $conf['display']['width'];
    $screenHeight = $conf['display']['height'];
    $url = $conf['path']['control_file'];
    trace("url = $url");
} else {
    // Guess configuration from global PHP variables.
    $display = ':0';
    if (!$unittest[__FILE__]) {
        $screenWidth = 1024;
        $screenHeight = 768;
        $url = dirname($_SERVER['HTTP_REFERER']) . '/' . basename($_SERVER['PHP_SELF']);
        trace("url = $url");
    }
}

function displayCommand($cmd) {
    global $display;
    global $ssh;
    if (isset($ssh)) {
        $cmd = "$ssh 'DISPLAY=$display $cmd'";
    } else {
        $cmd = "DISPLAY=$display HOME=/var/www $cmd";
    }
    $result = shell_exec($cmd);
    trace("cmd=$cmd, result=$result");
    return $result;
}

function windowListOnScreen() {
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

function windowList() {
    $list = array();
    global $dbcon;
    $field = "name";
    $order = "ASC";

    $windows = $dbcon->getWindowsOrderBy($field, $order);
    foreach ($windows as $w) {
// trace("w = " . serialize($w));
        $id = $w['win_id'];
// trace("id = $id");
        if ($id != '') {
            array_push($list, $id);
        }
    }
    return $list;
}

function closeAll() {
    global $dbcon;

    $windows_on_screen = windowListOnScreen();

    foreach($windows_on_screen as $id) {
        displayCommand('wmctrl -ic "' . $id . '"');
        // trace("closeAllWindows: " . $id);
        if($dbcon->getState_Window($id)!=null) {
            $dbcon->deleteWindow($id);
        }
    }

    clearUploadDir();
}

function doLogout($username) {
    global $dbcon;
    if ($username == 'ALL') {
        // Terminate all user connections and reset system.
        closeAll();
        //restartVNCDaemon();
        $dbcon->resetTables();
    }
}

function clearUploadDir() {
    global $conf;
    $upload_dir = $conf['path']['upload_dir'];

    if (is_dir($upload_dir)) {
        if ($dh = opendir($upload_dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file!="." AND $file !="..") {
                    unlink("$upload_dir/$file");
                }
            }
            closedir($dh);
        }
    }
}

function setLayout($layout) {
    // Set layout of team display.
    global $screenHeight;
    global $screenWidth;

    trace("layout $layout");

    global $dbcon;
    $dbcon->exec("UPDATE setting SET value='$layout' WHERE key='layout'");

    $geom['g1x1'] = array(
                    array(0, 0, 1, 1), array(0, 0, 0, 0),
                    array(0, 0, 0, 0), array(0, 0, 0, 0)
                  );
    $geom['g2x1'] = array(
                    array(0, 0, 2, 1), array(1, 0, 2, 1),
                    array(0, 0, 0, 0), array(0, 0, 0, 0)
                  );
    $geom['g1x2'] = array(
                    array(0, 0, 1, 2), array(0, 1, 1, 2),
                    array(0, 0, 0, 0), array(0, 0, 0, 0)
                  );
    $geom['g1a2'] = array(
                    array(0, 0, 2, 1), array(1, 0, 2, 2),
                    array(1, 1, 2, 2), array(0, 0, 0, 0)
                  );
    $geom['g2x2'] = array(
                    array(0, 0, 2, 2), array(1, 0, 2, 2),
                    array(0, 1, 2, 2), array(1, 1, 2, 2)
                  );

    $dim = $geom[$layout];

    $wi = 0;
    foreach (windowList() as $id) {
        $x = $dim[$wi][0];
        $y = $dim[$wi][1];
        $dx = $dim[$wi][2];
        $dy = $dim[$wi][3];
        if ($dx == 0 || $dy == 0) {
            // Hide window by moving it to desktop 1.
            $ret = displayCommand('wmctrl -r ' . $id . ' -i -t 1');
        } else {
            // Show window by moving it to desktop 0, set size and position.
            $dx = $screenWidth / $dx;
            $dy = $screenHeight / $dy;
            $x = $dx * $x;
            $y = $dy * $y;
            $ret = displayCommand('wmctrl -s 0');
            $ret = displayCommand('wmctrl -R ' . $id . ' -i');
            $ret = displayCommand('wmctrl -r ' . $id . ' -i -t 0');
            $ret = displayCommand('wmctrl -r ' . $id . ' -i -e 0,' . $x . ',' . $y . ',' . $dx . ',' . $dy);
        }
        trace('wmctrl -r ' . $id . ' -i -e 0,' . $x . ',' . $y . ',' . $dx . ',' . $dy);
        $wi += 1;
    }
}

function activateControls($windowhex) {

global $dbcon;

$fhandler = $dbcon->querySingle('SELECT handler FROM window WHERE win_id ="$windowhex"');
error_log("activateControls for Handler " . $fhandler);

    // experimental: in case of Libre-Office possibly activate pageview-mode for Zoom
    // if(strpos($fhandler, 'libreoffice') > -1)
    //    displayCommand("xdotool windowfocus $windowhex key Ctrl+Shift+O");
}

function restartVNCDaemon() {
    global $display;

    trace("+++ Restart SSVNC Daemon +++ ");

    $pinfo = shell_exec("ps -ef | grep SSVNC | nawk '{ print $2 }'");

    $pid = preg_split("/\n/", $pinfo);
    $kill = "";

    for($i=0; $i<count($pid)-1; $i++) {
        $kill = shell_exec("kill ".$pid[$i]);
        trace("proc with procid " . $pid[$i] . " killed ");
    }


    $webroot = $_SERVER['DOCUMENT_ROOT'];
    $subdir = $_SERVER['PHP_SELF'];
    $path = $webroot.substr($subdir, 0, (strlen($subdir)-(strrpos($subdir, '/')+4)));

    $startup = shell_exec("export DISPLAY=".$display."; cd ".$path."; php SSVNCDaemon.php");
    // $user = shell_exec("whoami");

    // trace("Execute Task as User : " . $user);
    trace("Return value for kill Command : " . $kill);
    // trace("Return value for startup Command : " . $startup);

    trace("+++ SSVNC Daemon restarted +++");
}

function processRequests() {

global $dbcon;

if (array_key_exists('window', $_REQUEST)) {
    // All windows related commands must start with window=.

        $windownumber = $_REQUEST['window'];
        $window = $windownumber-1;

        // TODO: $win_id und $windowname können vermutlich zusammengefasst werden.
        $win_id = $dbcon->getWindowIDBySection($windownumber);
        $windowlist = windowList();

        if (count($windowlist) == 0) {
            trace("no window found for command");
            $windowname = 0;
            $windowhex = 0;
        } else {
            $windowname = $windowlist[$window];
            $windowhex = hexdec($windowname);
        }

    if (array_key_exists('key', $_REQUEST)) {
        $key = $_REQUEST['key'];
        trace("key '$key' in window '$windownumber'");
        displayCommand("wmctrl -R '$windowname' -i");
            // activateControls($windowhex);
        // displayCommand("xdotool windowfocus $windowhex key $key");

        // trying mousemove and mouseclick for better vnc control
        displayCommand("xdotool mousemove --window $windowhex 100 100");
        displayCommand("xdotool mouseclick");
        displayCommand("xdotool key $key");
    }

    if (array_key_exists('keydown', $_REQUEST)) {
        $key = $_REQUEST['keydown'];
        trace("keydown '$key' in window '$windownumber'");
        displayCommand("wmctrl -R '$windowname' -i");
            // activateControls($windowhex);
        // displayCommand("xdotool windowfocus $windowhex key $key");

        // trying mousemove and mouseclick for better vnc control
        displayCommand("xdotool mousemove --window $windowhex 100 100");
        displayCommand("xdotool mouseclick");
        displayCommand("xdotool key $key");
        //~ displayCommand("xdotool windowfocus $windowhex keydown $key");
    }

    if (array_key_exists('keyup', $_REQUEST)) {
        $key = $_REQUEST['keyup'];
        trace("keyup '$key' in window '$windownumber'");
            // activateControls($windowhex);
        //~ displayCommand("wmctrl -R '$windowname' -i");
        //~ displayCommand("xdotool windowfocus $windowhex keyup $key");
    }

    if (array_key_exists('delete', $_REQUEST)) {

        $delete = str_replace(" ","\ ", addslashes($_REQUEST['delete']));
        trace("+++ ENTERING DELETE Section : delete=$delete and close window $win_id +++");

        if (file_exists($delete)) {
            trace("+++ DELETE FILE FROM WEBINTERFACE +++");
            unlink($delete);
        } else if ($delete == "VNC")  {
            trace("+++ DELETE VNC Client FROM DAEMON +++");
            // call via daemon: ?window=vncwin&delete=VNC&vncid=123
            trace("vnc delete in control");
            $win_id = $_REQUEST['vncid'];   // = hexWindow in database, but not on screen
            trace("VNC cia Daemon ... id=$win_id");

        } else if (strstr($delete, "http")) {
            trace("+++ DELETE Browserwindow +++");
        } else if (preg_match('/(^\w{3,}@\w{1,})/', $delete)) {
            trace("+++ DELETE VNC Client FROM WEBINTERFACE +++");
                // call via webinterface
                // if preg_match ~ name@host

            $win_id = $dbcon->querySingle('SELECT win_id FROM window where file="'.$delete.'" AND handler="vnc"');
            trace('DELETE VNC Window with ID='.$win_id.'FROM Database ::
                SELECT win_id FROM window where file="'.$delete.'" AND handler="vnc"');
        } else {
            trace("Unhandled delete for '$delete'");
        }

        // displayCommand("xdotool windowkill $win_id");
        displayCommand("wmctrl -ic $win_id");
        $dbcon->deleteWindow($win_id);
    }

    if (array_key_exists('closeOrphans', $_REQUEST)) {

        // win_ids in db
        $windows_in_db = $dbcon->getWindows();
        $db_ids = array();

        if(count($windows_in_db)>0) {
            foreach($windows_in_db as $win) {
                array_push($db_ids, $win['win_id']);
            }
        }

        // win_ids on screen
        $screen_ids = windowListOnScreen();

        // orphaned windows
        $orphan_ids = array_diff($screen_ids, $db_ids);

        if (count($orphan_ids > 0)) {
            // close windows on screen not existing in database
            foreach($orphan_ids as $id) {
                displayCommand('wmctrl -ic "' . $id . '"');
            }
        }

    }

    if (array_key_exists('toggle', $_REQUEST)) {
        $windowhex = hexdec($win_id);
        trace("toggle in window '$windownumber' (id=$win_id)");
        // displayCommand('wmctrl -R "' . $win_id . '"');
        // displayCommand('xdotool windowkill ' . hexdec($windowname));

        // just change state
        $state = $dbcon->getState_Window($win_id);
        trace("Window Status: $state");
        if ($state =="active") {
            displayCommand("xdotool windowminimize $windowhex");
            $new_state = $dbcon->setState_Window($win_id, "inactive");
        } else {
            displayCommand("xdotool windowactivate $windowhex");
            $new_state = $dbcon->setState_Window($win_id, "active");
        }
    }

}

if (array_key_exists('layout', $_REQUEST)) {
  setLayout($_REQUEST['layout']);
}

if (array_key_exists('logout', $_REQUEST)) {
  doLogout($_REQUEST['logout']);
}

if (array_key_exists('newVncWindow', $_REQUEST)) {
    // TODO: Better write new code for VNC window.
    // This is just a workaround to handle it below.
    $_REQUEST['newWindow'] = $_REQUEST['newVncWindow'];
}

if (array_key_exists('newWindow', $_REQUEST)) {

    $new = urldecode($_REQUEST['newWindow']);
    trace("newWindow '$new'");
    $new = unserialize($new);
    // '$new' already contains 'id', 'file', 'state', 'type', 'userid'
    // 'win_id' + 'name' has to be defined afterwards

    $handler = $new['handler'];
    // TODO: use escapeshellarg() for filename.
    $filename = $new['file'];

    // if new window is no vnc window, just open it with current handler
    if ($handler != "vnc") {

        $webroot = $_SERVER['DOCUMENT_ROOT'];
        $subdir = $_SERVER['PHP_SELF'];
        $path = $webroot.substr($subdir, 0, (strlen($subdir)-(strrpos($subdir, '/')+4)));

        $cmd = "$handler '$filename'";
        displayCommand("/usr/bin/nohup $cmd >/dev/null 2>&1 &");
        // trace("(2) open=".$new['handler'].$new['file'].' > /dev/null 2>/dev/null &');
    }
    // TODO: check if necessary t wait 1 second (if bigger files are uploaded)
    sleep(1);

// TODO: db feld 'name' in 'section' umbenennen

    $window_ids_on_screen = windowListOnScreen();
    $windows_in_db = $dbcon->getWindows();

    $existing_ids = array();
    $new_window_id="";

    if (count($windows_in_db)>0) {
        // add db widnows to existing_ids
        foreach($windows_in_db as $win) {
          $existing_ids[] = $win['win_id'];
        }

        $new_window = array_diff($window_ids_on_screen, $existing_ids);
        // trace(serialize($new_window));

        foreach($new_window as $win_id) {
            if($win_id!="")
                $new_window_id = $win_id;
        }
        trace("neues Fenster hat ID $new_window_id");

    } else {
        $new_window_id = $window_ids_on_screen[0];
        // trace("(5) nothing in database, only one window on display : ".$new_window_id);
    }

  // search window and activate it
  displayCommand('wmctrl -R "' . $new_window_id . '"');
  // trace("(6) searching for window : ".$new_window_id);

  // determine last assigned monitor section
  $max_section = $dbcon->maxSection();

  $section = $max_section + 1;
  trace("(7) maxSection: $max_section");

  // if all information available, create window object

  $new['id'] = $dbcon->nextID();
  $new['name'] = $section;

  if($new['id']<=4) {
    $new['state'] = "active";
  } else {
    $new['state'] = "inactive";
    displayCommand('xdotool windowminimize ' . hexdec($new_window_id));
    trace($new_window_id. " + ". hexdec($new_window_id));
  }

  // $new['file'] = $active_window; (?)

  // TODO: check how to insert the userid
  // perhaps better add to array in upload.php ?
  $new['userid'] = "all";

  $myWindow = array( $new['id'],
                     $new_window_id,
                     $new['name'],
                     $new['state'],
                     $new['file'],
                     $new['handler'],
                     $new['userid'],
                     $new['date']
                     );

  // save window in database
  $dbcon->insertWindow($myWindow);
}

if (array_key_exists('switchWindows', $_REQUEST)) {
    trace("switching " . $_REQUEST['before'] . ' and  ' . $_REQUEST['after']);

    if(isset($_REQUEST['before']))
        $before = $_REQUEST['before'];
    if(isset($_REQUEST['after']))
        $after = $_REQUEST['after'];

    $temp = "tmp";

    // exchange section
    $win_id1 = $dbcon->getWindowIDBySection($before);
    $win_id2 = $dbcon->getWindowIDBySection($after);
trace("+++ Quadrant ".$win_id1." mit Quadrant ".$win_id2." tauschen +++");

    $update1 = $dbcon->updateWindow($win_id1, "name", $after);
    $update2 = $dbcon->updateWindow($win_id2, "name", $before);

trace("++updating database ".$win_id1." name=".$after);
trace("++updating database ".$win_id2." name=".$before);

    // TODO: Update layout automatically.
}

if (array_key_exists('openURL', $_REQUEST)) {
    trace("openURL " . $_REQUEST['openURL']);

    global $url;

    if (isset($_REQUEST['openURL'])) {
        $openURL = $_REQUEST['openURL'];
    }

    trace("MYURL: ".$openURL);

    $dt = new DateTime();
    $date = $dt->format('Y-m-d H:i:s');

    $window = array(
        "id" => "",
        "win_id" => "",
        "name" => "",
        "state" => "",
        "file" => $openURL,
        // "handler" => "iceweasel --new-window ",
        "handler" => "/usr/bin/nohup /usr/bin/netsurf ",
        "userid" => "",
        "date" => $date
        );

      $serializedWindow = serialize($window);

      $sw = urlencode($serializedWindow);
      // Get cURL resource
      $curl = curl_init();
      // Set some options - we are passing in a useragent too here
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url.'?newWindow='.$sw,
        CURLOPT_USERAGENT => 'Codular Sample cURL Request'
        ));
        // Send the request & save response to $resp
        $resp = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
}

// TODO: chef if query redundant?
if (array_key_exists('closeAll', $_REQUEST)) {
    trace("close All Windows " . $_REQUEST['closeAll']);

    if(isset($_REQUEST['closeAll']))
        $close = $_REQUEST['closeAll'];

    closeAll();
}

if (array_key_exists('getHandler', $_REQUEST)) {
    trace("getHandler from Section ID " . $_REQUEST['getHandler']);

    if (isset($_REQUEST['getHandler'])) {
        $section = $_REQUEST['getHandler'];
        }

    $fhandler = $dbcon->querySingle('SELECT handler from window where name='.$section);

    if (strpos($fhandler, 'eog') > -1) {
        $fhandler = 'eog';
    } else if (strpos($fhandler, 'libreoffice') > -1) {
        if (strpos($fhandler, '--calc') > -1) {
            $fhandler = 'libreoffice-calc';
        } else if (strpos($fhandler, '--impress') > -1) {
            $fhandler = 'libreoffice-impress';
        } else if (strpos($fhandler, '--writer') > -1) {
            $fhandler = 'libreoffice-writer';
        }
    } else if (strpos($fhandler, 'netsurf') > -1) {
        $fhandler = 'netsurf';
    } else if (strpos($fhandler, 'vlc') > -1) {
        $fhandler = 'vlc';
    } else if (strpos($fhandler, 'vnc') > -1) {
        $fhandler = 'vnc';
    } else if (strpos($fhandler, 'zathura') > -1) {
        $fhandler = 'zathura';
    }

    trace("returning FileHandle " . $fhandler);
    // TODO: no longer print but send return value
    print $fhandler;
    // Return value not accepted by Javascript xmlhttprequest
    return $fhandler;
}


if (array_key_exists('isFile', $_REQUEST)) {
    // trace("get Section ID " . $_REQUEST['isFile']);

    if(isset($_REQUEST['isFile']))
        $section = $_REQUEST['isFile'];

    $filename = $dbcon->querySingle('SELECT file from window where name='.$section);
    $file_exists = 1;

    if(file_exists($filename)!=1) {
        $file_exists = 0;
    }

    trace($filename . " - " . $file_exists);
    // TODO: no longer print but send return value
    print $file_exists;
    // Return value not accepted by Javascript xmlhttprequest
    return $file_exists;
}

} // processRequests

processRequests();

if ($unittest[__FILE__]) {
    // Run unit test.
    echo("<p>Running unit test</p>");
    trace("Running unit test for " . __FILE__);
    trace("Finished unit test");
}

?>
