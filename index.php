<?php
/*
Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Authors: Alexander Wagner, Stefan Weil, Dennis Müller

References:

File upload (general)

* http://www.php.net/manual/en/features.file-upload.post-method.php

File upload with dropzone

* http://www.dropzonejs.com/
* http://www.startutorial.com/articles/view/how-to-build-a-file-upload-form-using-dropzonejs-and-php
* http://maxoffsky.com/code-blog/howto-ajax-multiple-file-upload-in-laravel/

Websockets:

* https://en.wikipedia.org/wiki/Server-sent_events
* https://developer.mozilla.org/en-US/docs/WebSockets/Writing_WebSocket_client_applications
* http://code.google.com/p/phpwebsocket/
* http://dharman.eu/?menu=phpWebSocketsTutorial

Keyboard input

* http://jsfiddle.net/angusgrant/E3tE6/
* http://stackoverflow.com/questions/3181648/how-can-i-handle-arrowkeys-and-greater-than-in-a-javascript-function-which
* http://stackoverflow.com/questions/5597060/detecting-arrow-key-presses-in-javascript
* http://www.quirksmode.org/js/keys.html

Key symbols

* http://www.tcl.tk/man/tcl8.4/TkCmd/keysyms.htm

* wmctrl, suckless-tools (lsw, sprop, wmname, ...)

* display.im6, evince

Authorization

* http://aktuell.de.selfhtml.org/artikel/php/loginsystem/

Overlays

* http://answers.oreilly.com/topic/1823-adding-a-page-overlay-in-javascript/

*/

    session_start();
if (isset($_REQUEST['monitor'])) {
    $monitor = $_REQUEST['monitor'];
    $_SESSION['monitor'] = $monitor;
} elseif (!isset($_SESSION['monitor'])) {
    $_SESSION['monitor'] = '???';
}
    $_SESSION['referer'] = 'index.php';
    require_once('auth.php');

    // Connect to database and get configuration constants.
    require_once('DBConnector.class.php');
    $dbcon = new palma\DBConnector();

    // Support localisation.
    require_once('i12n.php');

    $user = false;
if (isset($_SESSION['username'])) {
    # PHP session based authorization.
    $username = $_SESSION['username'];
    $address = $_SESSION['address'];
    $user = "$username@$address";
} elseif (isset($_SERVER['PHP_AUTH_USER'])) {
    # .htaccess basic authorization.
    $user = $_SERVER['PHP_AUTH_USER'];
}

    require_once('globals.php');
    /*
     * file paths for vnc downloads
     */
    $winvnc = CONFIG_START_URL . "theme/" . CONFIG_THEME . "/winvnc-palma.exe";
    $macvnc = "https://www.bib.uni-mannheim.de/palma/VineServer.dmg";
    $linuxsh = CONFIG_START_URL . "theme/" . CONFIG_THEME . "/x11.sh";

    /*
     * contact form elements
     * might be sourced out and included
     */
if (isset($_POST['submit'])) {
    $to = "infol@bib.uni-mannheim.de";
    $to = "alexander.wagner@bib.uni-mannheim.de";
    $from = $_POST['email'];
    $name = $_POST['name'];
    $subject = "Feedback for PalMA";
    $message = $name . " wrote the following:" . "\n\n" . $_POST['message'];

    $headers = "From:" . $from;
    mail($to, $subject, $message, $headers);
    echo "Mail Sent. Thank you for your feedback " . $name . ", we will get in touch with you shortly.";
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PalMA</title>

<link rel="icon" href="theme/<?=CONFIG_THEME?>/favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="pure-min.css">
<link rel="stylesheet" href="palma.css" type="text/css">

<script type="text/javascript" src="/javascript/jquery/jquery.js"></script>

<script type="text/javascript" src="dropzone.js"></script>

<script type="text/javascript">

// Screen section which responds to keyboard input.
var focus_section = '1';

function sendToNuc(command) {
  var xmlHttp = new XMLHttpRequest();
  if (!xmlHttp) {
    // TODO
    alert('XMLHttpRequest failed!');
    return;
  }
  var url = 'control.php?' + command;
  var response = "";
  xmlHttp.open("get", url, true);
  xmlHttp.onreadystatechange = function () {
      if (xmlHttp.readyState == 1) {
          // Server connection established (IE only).
      } else if (xmlHttp.readyState == 2) {
          // Data transferred to server.
      } else if (xmlHttp.readyState == 3) {
          // Server is answering.
      } else if (xmlHttp.readyState == 4) {
          // Received all data from server.
          return xmlHttp.responseText;
      } else {
          alert("Got xmlHttp.readyState " + xmlHttp.readyState);
      }
  };
  xmlHttp.send(null);
  //~ alert("sendToNuc " + url);
}

function keyControl(number, image, controlClass, key, handler, disabled, title) {
    // "number" refers to the slected screensection

  var keyHandler = getHandlerCommand(handler, key);
  if ( (keyHandler == "") || (keyHandler == null) ) {
    keyHandler = "default";
  }

  var button = document.createElement('button');
  var icon = document.createElement('i');
  if (!disabled) {
    icon.setAttribute('class', image);
    button.appendChild(icon);
    button.setAttribute('class', controlClass);
    button.setAttribute('onmousedown',
                     'sendToNuc("window=' + number + '&keydown=' + encodeURIComponent(keyHandler) + '")');
    button.setAttribute('onmouseup',
                     'sendToNuc("window=' + number + '&keyup=' + encodeURIComponent(keyHandler) + '")');
    button.setAttribute('title', title);
  } else {
    icon.setAttribute('class', image + " unavailable");
    button.appendChild(icon);
    button.setAttribute("class", "unavailable");
    button.setAttribute('title', title + " " + "<?=__('not available')?>");
  }
  return button;
}

function downloadFile(screensection) {

    // wrong path if copied to /home/directory
    // TODO: check file and path

   var url = document.URL;
   var url_path = url.split("/");

   var file = document.getElementById("file" + screensection).innerHTML;
   var file = document.getElementById("file" + screensection).getAttribute("title");

   // Download with download.php
   var download = url_path[0]+"/"+url_path[1]+"/"+url_path[2]+"/"+url_path[3]+"/download.php?file="+encodeURIComponent(file);

   var name = "Download";

   if(file.indexOf("www.") > -1) {
        window.open(file, name);
   } else {
        window.open(download, name);
    }
}

function is_valid_url(url)
{
    return url.match(/(^(ht|f)tps?:\/\/)([a-zA-Z0-9\.-])+(\.([a-zA-Z]{2,}))(\/([^\s\<\>\,\{\}\\\|\^\[\]\'])*)?$/);
}

function urlToNuc() {

    var url = document.getElementById('url_field').value;
    if (is_valid_url(url)) {
        // Encode special characters
        url = encodeURIComponent(url);
        sendToNuc('openURL='+url);
    } else {
        var urlfield = document.getElementById('url_field');
        urlfield.setAttribute('value', '<?=__("Enter valid URL")?>');
    }

    setTimeout(function(){location.reload()}, 1000);
}

function showLayout(layout, controls, window) {
    //~ console.log("Layout: " + layout);
    //~ for (i = 0; i < controls.length; i++) {
    //~     console.log("SL " + i + ": " + controls[i]);
    //~ }

    document.onkeydown = function(evt) {
        evt = evt || window.event;
        var section = focus_section;
        var handler = controls[focus_section][0];
        var keyHandler;
        //~ console.log("Key down: " + evt.keyCode);
        switch (evt.keyCode) {
        case 33: // page up
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'prior'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 34: // page down
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'next'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 35: // end
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'end'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 36: // home
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'home'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 37: // left
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'left'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 38: // up
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'up'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 39: // right
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'right'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case 40: // down
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'down'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        }
    };

    document.onkeypress = function(evt) {
        evt = evt || window.event;
        //~ console.log("Key press: " + evt.keyCode);
        var charCode = evt.which || evt.keyCode;
        var charStr = String.fromCharCode(charCode);
        var section = focus_section;
        var handler = controls[focus_section][0];
        var keyHandler;
        switch (charStr) {
        case "1": // select section 1
        case "2": // select section 2
        case "3": // select section 3
        case "4": // select section 4
            focus_section = charStr;
            break;
        case "+": // zoom in
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'zoomin'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        case "-": // zoom out
            keyHandler = encodeURIComponent(getHandlerCommand(handler, 'zoomout'));
            sendToNuc('window=' + section + '&key=' + keyHandler);
            break;
        }
    };

    var windowlist = document.getElementById('windowlist');
    var entries = windowlist.getElementsByClassName('window_entry');
    var screensection, file, status;
    markCurrentLayout(layout);
    for (var n = 0; n < window.length; n++) {
        screensection = window[n].section;
        file = window[n].file;
        status = window[n].state;
        entries[n].appendChild(addWindowControls(layout, controls, screensection, file, status));
    }
}

function markCurrentLayout(layout) {
    var layoutDivs = document.getElementsByClassName("screenlayout");
    for (var i = 0 ; i < layoutDivs.length ; i++) {
        var children = layoutDivs[i].getElementsByClassName("pure-button");
        for (var k = 0 ; k < children.length ; k++) {
            children[k].style.backgroundColor = "";
        }
    }
    document.getElementById(layout).style.backgroundColor = "#990000";
}

function miniDisplaySelect(element) {
    sendToNuc('layout=' + element.id);
    markCurrentLayout(element.id);
}

function getHandlerCommand(handle, task) {

    // console.log("getHandlerCommand "+handle+" - "+task);
    // to deactivate buttons just add 'undefined' as keystroke

    var handler = [];

    handler["default"] = {};
    // handler["default"]["init"] = "";
    handler["default"]["up"] = "Up";
    handler["default"]["down"] = "Down";
    handler["default"]["left"] = "Left";
    handler["default"]["right"] = "Right";
    handler["default"]["next"] = "Next";
    handler["default"]["prior"] = "Prior";
    handler["default"]["home"] = "Home";
    handler["default"]["end"] = "End";
    handler["default"]["zoomin"] = "ctrl+plus";
    handler["default"]["zoomout"] = "ctrl+minus";
    handler["default"]["download"] = "download";
    handler["default"]["counterclockwise"] = "undefined";
    handler["default"]["clockwise"] = "undefined";

    // Handler for web pages.
    handler["midori"] = {};
    handler["midori"]["up"] = "Up";
    handler["midori"]["down"] = "Down";
    handler["midori"]["left"] = "Left";
    handler["midori"]["right"] = "Right";
    handler["midori"]["next"] = "Next";
    handler["midori"]["prior"] = "Prior";
    handler["midori"]["home"] = "Home";
    handler["midori"]["end"] = "End";
    handler["midori"]["zoomin"] = "ctrl+plus";
    handler["midori"]["zoomout"] = "ctrl+minus";
    handler["midori"]["download"] = "undefined";
    handler["midori"]["counterclockwise"] = "undefined";
    handler["midori"]["clockwise"] = "undefined";

    // Handler for images.
    handler["feh"] = {};
    handler["feh"]["up"] = "alt+Up";
    handler["feh"]["down"] = "alt+Down";
    handler["feh"]["left"] = "alt+Left";
    handler["feh"]["right"] = "alt+Right";
    handler["feh"]["next"] = "alt+Next";
    handler["feh"]["prior"] = "alt+Prior";
    handler["feh"]["home"] = "undefined";
    handler["feh"]["end"] = "undefined";
    handler["feh"]["zoomin"] = "KP_Add";
    handler["feh"]["zoomout"] = "KP_Subtract";
    handler["feh"]["download"] = "download";
    handler["feh"]["counterclockwise"] = "less";
    handler["feh"]["clockwise"] = "greater";

    // Controls in LibreOffice: no zoom in calc and writer, has to be activated first
    // by pressing <Ctrl+Shift+o> (switch view mode on/off) not implemented yet
    handler["libreoffice"] = {};
    handler["libreoffice"]["up"] = "Up";
    handler["libreoffice"]["down"] = "Down";
    handler["libreoffice"]["left"] = "Left";
    handler["libreoffice"]["right"] = "Right";
    handler["libreoffice"]["next"] = "Next";
    handler["libreoffice"]["prior"] = "Prior";
    handler["libreoffice"]["home"] = "undefined";
    handler["libreoffice"]["end"] = "undefined";
    handler["libreoffice"]["zoomin"] = "undefined";
    handler["libreoffice"]["zoomout"] = "undefined";
    handler["libreoffice"]["download"] = "download";
    handler["libreoffice"]["counterclockwise"] = "undefined";
    handler["libreoffice"]["clockwise"] = "undefined";

    // Handler for MS Excel and LibreOffice Calc documents.
    handler["libreoffice-calc"] = {};
    handler["libreoffice-calc"]["up"] = "Up";
    handler["libreoffice-calc"]["down"] = "Down";
    handler["libreoffice-calc"]["left"] = "Left";
    handler["libreoffice-calc"]["right"] = "Right";
    handler["libreoffice-calc"]["next"] = "Next";
    handler["libreoffice-calc"]["prior"] = "Prior";
    handler["libreoffice-calc"]["home"] = "Home";
    handler["libreoffice-calc"]["end"] = "End";
    handler["libreoffice-calc"]["zoomin"] = "undefined";
    handler["libreoffice-calc"]["zoomout"] = "undefined";
    handler["libreoffice-calc"]["download"] = "download";
    handler["libreoffice-calc"]["counterclockwise"] = "undefined";
    handler["libreoffice-calc"]["clockwise"] = "undefined";

    // Handler for MS Powerpoint and LibreOffice Impress documents.
    handler["libreoffice-impress"] = {};
    handler["libreoffice-impress"]["up"] = "Up";
    handler["libreoffice-impress"]["down"] = "Down";
    handler["libreoffice-impress"]["left"] = "Left";
    handler["libreoffice-impress"]["right"] = "Right";
    handler["libreoffice-impress"]["next"] = "Next";
    handler["libreoffice-impress"]["prior"] = "Prior";
    handler["libreoffice-impress"]["home"] = "Home";
    handler["libreoffice-impress"]["end"] = "End";
    handler["libreoffice-impress"]["zoomin"] = "plus";
    handler["libreoffice-impress"]["zoomout"] = "minus";
    handler["libreoffice-impress"]["download"] = "download";
    handler["libreoffice-impress"]["counterclockwise"] = "undefined";
    handler["libreoffice-impress"]["clockwise"] = "undefined";

    // Handler for MS Word and LibreOffice Writer documents.
    handler["libreoffice-writer"] = {};
    handler["libreoffice-writer"]["up"] = "Up";
    handler["libreoffice-writer"]["down"] = "Down";
    handler["libreoffice-writer"]["left"] = "Left";
    handler["libreoffice-writer"]["right"] = "Right";
    handler["libreoffice-writer"]["next"] = "Next";
    handler["libreoffice-writer"]["prior"] = "Prior";
    handler["libreoffice-writer"]["home"] = "undefined";
    handler["libreoffice-writer"]["end"] = "undefined";
    handler["libreoffice-writer"]["zoomin"] = "undefined";
    handler["libreoffice-writer"]["zoomout"] = "undefined";
    handler["libreoffice-writer"]["download"] = "download";
    handler["libreoffice-writer"]["counterclockwise"] = "undefined";
    handler["libreoffice-writer"]["clockwise"] = "undefined";

    // Handler for videos.
    handler["vlc"] = {};
    handler["vlc"]["up"] = "undefined";
    handler["vlc"]["down"] = "undefined";
    handler["vlc"]["left"] = "undefined";
    handler["vlc"]["right"] = "space";
    handler["vlc"]["next"] = "undefined";
    handler["vlc"]["prior"] = "undefined";
    handler["vlc"]["home"] = "undefined";
    handler["vlc"]["end"] = "undefined";
    handler["vlc"]["zoomin"] = "undefined";
    handler["vlc"]["zoomout"] = "undefined";
    handler["vlc"]["download"] = "undefined";
    handler["vlc"]["counterclockwise"] = "undefined";
    handler["vlc"]["clockwise"] = "undefined";

    // Handler for shared desktops (VNC).
    handler["vnc"] = {};
    handler["vnc"]["up"] = "Up";
    handler["vnc"]["down"] = "Down";
    handler["vnc"]["left"] = "Left";
    handler["vnc"]["right"] = "Right";
    handler["vnc"]["next"] = "undefined";
    handler["vnc"]["prior"] = "undefined";
    handler["vnc"]["home"] = "undefined";
    handler["vnc"]["end"] = "undefined";
    handler["vnc"]["zoomin"] = "plus";
    handler["vnc"]["zoomout"] = "minus";
    handler["vnc"]["download"] = "undefined";
    handler["vnc"]["counterclockwise"] = "undefined";
    handler["vnc"]["clockwise"] = "undefined";

    // Handler for PDF documents.
    handler["zathura"] = {};
    handler["zathura"]["up"] = "Up";
    handler["zathura"]["down"] = "Down";
    handler["zathura"]["left"] = "Left";
    handler["zathura"]["right"] = "Right";
    handler["zathura"]["next"] = "Next";
    handler["zathura"]["prior"] = "Prior";
    handler["zathura"]["home"] = "Home";
    handler["zathura"]["end"] = "End";
    handler["zathura"]["zoomin"] = "plus";
    handler["zathura"]["zoomout"] = "minus";
    handler["zathura"]["download"] = "download";
    handler["zathura"]["counterclockwise"] = "undefined";
    handler["zathura"]["clockwise"] = "undefined";

    var send_keys = handler["default"]["up"];

    if (typeof(handler[handle]) !== "undefined") {
        send_keys = handler[handle][task];
    }

    // console.log(send_keys);

    return send_keys;
}

Dropzone.options.palmaDropzone = {
    init: function() {
      this.on("complete", function() {
        if (this.getQueuedFiles().length == 0 && this.getUploadingFiles().length == 0) {
          // File finished uploading, and there aren't any left in the queue.
          // console.log("File(s) uploaded");
          setTimeout(function() {
             location.reload();
          }, 1);
          // location.reload(); // verlangt Eingabe von Enter zum wiederholten Schicken der Daten
        }
      });
    }
};

function updateUserList(address, user) {
    // Update the user list on screen from the table in the database.

    // Get the <tbody> element which contains the user entries.
    var list = document.getElementById('userlist');

    // First we remove all existing <tr> elements.
    while (list.firstChild) {
        list.removeChild(list.firstChild);
    }

    if (address.length > 0) {
        // Add an entry for each user. Iterate over addresses:
        // One user may be connected several times with different devices.
        // We don't expect more than one user from the same device.
        var m;
        for (m = 0; m < address.length; m++) {
            var n;
            for (n = 0; n < user.length; n++) {
                if (address[m].userid == user[n].userid) {
                    break;
                }
            }
            var device = address[m].device;
            var tr = document.createElement('tr');
            var td = document.createElement('td');
            var i = document.createElement('i');
            i.setAttribute('class', 'fa fa-fw fa-' + device);
            td.appendChild(i);
            td.appendChild(document.createTextNode(user[n].name));
            tr.appendChild(td);
            list.appendChild(tr);
        }
    } else {
<?php
if ($user) {
    ?>
        // All users were disconnected.
        alert("<?=__('You were disconnected!')?>");
        window.location = 'logout.php';
    <?php
} else {
    ?>
        // If there is no user, we display an empty entry.
        var tr = document.createElement('tr');
        var td = document.createElement('td');
        td.appendChild(document.createTextNode("\u00a0"));
        tr.appendChild(td);
        list.appendChild(tr);
    <?php
}
?>
    }
}


function addWindowPosition(layout, screensection) {
    var position = document.createElement("div");
    position.setAttribute("class", "position " + layout);
    position.setAttribute('title', '<?=str_replace("'", "\\'", __("Select screen section for display"))?>');

    var s;
    var button;
    var icon;
    var br;

    switch (layout) {
        case 'g1x1':
            s = 1;
            break;
        case 'g1x2':
            s = 2;
            break;
        case 'g2x1':
            s = 2;
            break;
        case 'g1a2':
            s = 3;
            var layout_left = document.createElement('div');
            layout_left.setAttribute("class", "layout_left");
            var layout_right = document.createElement('div');
            layout_right.setAttribute("class", "layout_right");
            break;
        case 'g2x2':
            s = 4;
            break;
    }

    for (var n = 1; n <= s; n++) {
        br = '';
        button = document.createElement("button");
        button.setAttribute('value', n);
        if (n == screensection) {
            button.setAttribute("class", "selected");
        }
        button.setAttribute('onclick', "sendToNuc('switchWindows=TRUE&before=" + screensection + "&after='+(this.value))");
        icon = document.createElement("i");
        if (s == 1) {
            icon.setAttribute("class", "fa fa-desktop fa-2x");
        } else {
            icon.setAttribute("class", "fa fa-desktop fa-1x");
        }
        button.appendChild(icon);

        if ((layout == 'g1x2' && n == 1) || ((layout == 'g1a2' || layout == 'g2x2') && n == 2 )) {
            br = document.createElement("br");
        }

        if (layout == 'g1a2' && n == 1) {
            layout_left.appendChild(button);
        } else if (layout == 'g1a2' && n > 1) {
            layout_right.appendChild(button);
            if (br) {
                layout_right.appendChild(br);
            }
        } else {
            position.appendChild(button);
            if (br) {
                position.appendChild(br);
            }
        }
    }

    if (layout == 'g1a2') {
        position.appendChild(layout_left);
        position.appendChild(layout_right);
    }
    return position;
}


function addWindowControls(layout, controls, screensection, file, status) {
    var control = controls[screensection];
    if (typeof control == "undefined") {
    control = ["default", false, false, false, false,
               false, false, false, false, false, false, false];
    }

    // get handler
    var handler = control[0];
    // up down left right zoomin zoomout home end prior next download
    var up = control[1];
    var down = control[2];
    var left = control[3];
    var right = control[4];
    var zoomin = control[5];
    var zoomout = control[6];
    var home = control[7];
    var end = control[8];
    var prior = control[9];
    var next = control[10];
    var download = control[11];
    var counterclockwise = control[12];
    var clockwise = control[13];

    var windowcontrols = document.createElement("div");
    windowcontrols.setAttribute("class", "windowcontrols");

    var topbar = document.createElement("div");
    topbar.setAttribute("class", "topbar");
    var button = document.createElement('button');
    button.setAttribute("class", "toggle");
    icon = document.createElement('i');
    if (status == 'active') {
        icon.setAttribute("class", "fa fa-eye");
    } else {
        icon.setAttribute("class", "fa fa-eye-slash");
    }
    icon.setAttribute('id', 'status_' + screensection);
    button.setAttribute('title', '<?=__("Toggle visibility")?>');
    button.setAttribute('onclick', "sendToNuc('window=" + screensection + "&toggle=TRUE')");
    button.appendChild(icon);
    topbar.appendChild(button);
    topbar.appendChild(keyControl(screensection, 'fa fa-search-plus', 'zoomin', 'zoomin', handler, !zoomin, '<?=__("Zoom in")?>'));
    topbar.appendChild(keyControl(screensection, 'fa fa-search-minus', 'zoomout', 'zoomout', handler, !zoomout, '<?=__("Zoom out")?>'));
    topbar.appendChild(keyControl(screensection, 'fa fa-rotate-left', 'counterclockwise', 'counterclockwise', handler, !counterclockwise, '<?=__("Rotate counterclockwise")?>'));
    topbar.appendChild(keyControl(screensection, 'fa fa-rotate-right', 'clockwise', 'clockwise', handler, !clockwise, '<?=__("Rotate clockwise")?>'));
    var downloadbutton = keyControl(screensection, 'fa fa-download', 'download', 'download', handler, !download, '<?=__("Download this item")?>');
    downloadbutton.setAttribute('onclick', 'downloadFile(' + screensection + ')');
    topbar.appendChild(downloadbutton);
    button = document.createElement('button');
    button.setAttribute("class", "trash");
    button.setAttribute('onclick', "sendToNuc('window=" + screensection + "&delete=" + file + "')");
    button.setAttribute('title', '<?=__("Remove this item")?>');
    icon = document.createElement('i');
    icon.setAttribute("class", "fa fa-trash-o");
    button.appendChild(icon);
    topbar.appendChild(button);

    var movement = document.createElement("div");
    movement.setAttribute("class", "movement");

    var jump = document.createElement("div");
    jump.setAttribute("class", "jump");
    jump.appendChild(keyControl(screensection, 'fa fa-angle-double-up', 'jumpbeginning', 'home', handler, !home, '<?=__("Jump to start")?>'));
    jump.appendChild(keyControl(screensection, 'fa fa-angle-up', 'pageback', 'prior', handler, !prior, '<?=__("Page up")?>'));
    jump.appendChild(keyControl(screensection, 'fa fa-angle-down', 'pageforward', 'next', handler, !next, '<?=__("Page down")?>'));
    jump.appendChild(keyControl(screensection, 'fa fa-angle-double-down', 'jumpend', 'end', handler, !end, '<?=__("Jump to end")?>'));

    var arrows = document.createElement("div");
    arrows.setAttribute("class", "arrows");
    arrows.appendChild(keyControl(screensection, 'fa fa-toggle-up', 'arrowup', 'up', handler, !up, '<?=__("Up")?>'));
    arrows.appendChild(document.createElement("br"));
    arrows.appendChild(keyControl(screensection, 'fa fa-toggle-left', 'arrowleft', 'left', handler, !left, '<?=__("Left")?>'));
    arrows.appendChild(keyControl(screensection, 'fa fa-toggle-right', 'arrowright', 'right', handler, !right, '<?=__("Right")?>'));
    arrows.appendChild(document.createElement("br"));
    arrows.appendChild(keyControl(screensection, 'fa fa-toggle-down', 'arrowdown', 'down', handler, !down, '<?=__("Down")?>'));

    movement.appendChild(jump);
    movement.appendChild(arrows);

    var position = addWindowPosition(layout, screensection);

    // Putting it all together
    windowcontrols.appendChild(topbar);
    windowcontrols.appendChild(movement);
    windowcontrols.appendChild(position);
    return windowcontrols;
}


function updateWindowList(window){
    var windowlist = document.getElementById('windowlist');
    // remove old entries
    while (windowlist.firstChild) {
        windowlist.removeChild(windowlist.firstChild);
    }

    if (window.length == 0) {
        var entry = document.createElement('div');
        entry.setAttribute("class", "description");
        entry.appendChild(document.createTextNode('<?=__('Welcome')?>, <?=$username?>!'));
        entry.appendChild(document.createElement("br"));
        entry.appendChild(document.createTextNode('<?=__('There is no shared content yet. Click below to get started!')?>'));
        var addbutton = document.createElement('button');
        addbutton.setAttribute("class", "splash_add pure-button");
        addbutton.setAttribute("onclick", "openTab(event, 'Add')");
        var addtext = (document.createTextNode('<?=__("Add")?>'));
        addbutton.appendChild(addtext);
        var addicon = document.createElement("i");
        addicon.setAttribute("class", "fa fa-plus");
        addbutton.appendChild(addicon);
        windowlist.appendChild(entry);
        windowlist.appendChild(addbutton);
        document.getElementById("closeWindows").style.display = "none";
        document.getElementById("Layout").style.display = "none";
        document.getElementById("controlbtn").className = "tablinks";
    } else {
        document.getElementById("closeWindows").style.display = "inline-block";
        document.getElementById("Layout").style.display = "block";
        document.getElementById("controlbtn").className = "tablinks active";
        // Add an entry for each window.
        var n;
        for (n = 0; n < window.length; n++) {
            var file = window[n].file;
            var handler = window[n].handler;
            var screensection = window[n].section;
            var entry = document.createElement('div');
            entry.setAttribute("class", "window_entry");
            var divID = 'file' + screensection;
            entry.setAttribute('id', divID);

            var button = document.createElement('button');
            button.setAttribute("class", "window_entry_button");
            button.setAttribute('onclick', "openAccordion('" + divID + "')");
            var icon = document.createElement('i');
            if (handler.indexOf("midori") > -1) {
                icon.setAttribute("class", "fa fa-globe");
            } else if (handler.indexOf("vnc") > -1) {
                icon.setAttribute("class", "fa fa-video-camera");
            } else if (handler.indexOf("vlc") > -1) {
                icon.setAttribute("class", "fa fa-film");
            } else if (handler.indexOf("feh") > -1) {
                icon.setAttribute("class", "fa fa-picture-o");
            } else if (handler.indexOf("zathura") > -1) {
                icon.setAttribute("class", "fa fa-file-pdf-o");
            } else {
                icon.setAttribute("class", "fa fa-file");
            }
            button.appendChild(icon);
            var title = decodeURI(decodeURIComponent(file));
            // display only the last part of the URL or file name.
            // Long names are truncated, and the truncation is indicated.
            if (title.substring(0, 4) == 'http') {
                // Remove a terminating slash from an URL.
                // The full URL will be shown as a tooltip.
                title = title.replace(/\/$/, '');
                title = title.replace(/^.*\//, '');
                entry.setAttribute('title', file);
            } else {
                // For files only the full base name is shown as a tooltip.
                var fname = file;
                title = title.replace(/^.*\//, '');
                entry.setAttribute('title', fname);
            }
            if (title.length > 25) {
                title = title.substring(0, 25) + '...';
            }
            button.appendChild(document.createTextNode(title));
            var icon = document.createElement('i');
            icon.setAttribute("class", "fa fa-caret-down accordion-icon");
            button.appendChild(icon);
            entry.appendChild(button);
            windowlist.appendChild(entry);
        }
    }
}

function updateControlsBySection(window) {

    // get section and handler for each window
    var sectionControls = [];

    for (n = 0; n < window.length; n++) {
        var win_id = window[n].win_id;
        var section = window[n].section;
        var handler = window[n].handler;

        // alert("Section: " + section + " - Handler: " + handler);

        if (handler.indexOf("feh") > -1) {
            // up down left right zoomin zoomout home end prior next download rotateleft rotateright
            control = ["feh", true, true, true, true, true, true, false, false, false, false, true, true, true];
        } else if (handler.indexOf("libreoffice") > -1) {
            // Controls in LibreOffice: no zoom in calc and writer, has to be activated first
            // by pressing <Ctrl+Shift+o> (switch view mode on/off) not implemented yet
            control = ["libreoffice", true, true, true, true, false, false, false, false, true, true, true, false, false];
                if (handler.indexOf("--calc") > -1) {
                    control = ["libreoffice-calc", true, true, true, true, false, false, true, true, true, true, true, false, false];
                }
                if (handler.indexOf("--impress") > -1) {
                    control = ["libreoffice-impress", true, true, true, true, true, true, true, true, true, true, true, false, false];
                }
                if (handler.indexOf("--writer") > -1) {
                    control = ["libreoffice-writer", true, true, true, true, false, false, false, false, true, true, true, false, false];
                }
        } else if (handler.indexOf("midori") > -1) {
            control = ["midori", true, true, true, true, true, true, true, true, true, true, false, false, false];
        } else if (handler.indexOf("vlc") > -1) {
            control = ["vlc", false, false, false, true, false, false, false, false, false, false, false, false, false];
        } else if (handler.indexOf("vnc") > -1) {
            control = ["vnc", true, true, true, true, true, true, false, false, false, false, false, false, false];
        } else if (handler.indexOf("zathura") > -1) {
            control = ["zathura", true, true, true, true, true, true, true, true, true, true, true, false, false];
        } else {
            control = ["undefined", false, false, false, false, false, false, false, false, false, false, false, false, false];
        }

        sectionControls[section] = control;
    }

    // Fill empty sections with default values.
    for (i = sectionControls.length; i < 5; i++) {
        sectionControls[i] = ["default",
                              false, false, false, false,
                              false, false, false, false,
                              false, false, false, false, false];
    }

    return sectionControls;
}

function updateScreen() {
    sendToNuc('window=all&closeOrphans=true');
}

function clearURLField(defaultText) {
    var browseto = document.getElementById('url_field');
    browseto.setAttribute('value', 'http://');
}

// lastJSON is used to reduce the database polling rate.
var lastJSON = '';

function pollDatabase() {
    var xmlHttp = new XMLHttpRequest();
    if (xmlHttp) {
        xmlHttp.open("get", 'db.php?json=' + lastJSON, true);
        xmlHttp.onreadystatechange = function () {
            if (xmlHttp.readyState == 2) {
                // Data transferred to server.
            } else if (xmlHttp.readyState == 3) {
                // Server is answering.
            } else if (xmlHttp.readyState == 4) {
                // Received all data from server.
                var status = xmlHttp.status;
                if (status == 200) {
                    // Got valid response.
                    var text = xmlHttp.responseText;
                    lastJSON = text;
                    var db = JSON.parse(text);
                    var i;
                    var layout = '';
                    for (i = 0; i < db.setting.length; i++) {
                        if (db.setting[i].key == 'layout') {
                            layout = db.setting[i].value;
                            break;
                        }
                    }
                    var controls = updateControlsBySection(db.window);
                    // for (i = 0; i < controls.length; i++) {
                    //    console.log(i + ": " + controls[i]);
                    //    }
                    updateUserList(db.address, db.user);
                    updateWindowList(db.window);
                    showLayout(layout, controls, db.window);
                    // updateScreen(db.window);
                    setTimeout("pollDatabase()", 1);
                } else {
                    // Got error. TODO: handle it.
                }
            } else {
                // Got unexpected xmlHttp.readyState. TODO: handle it.
            }
        };
        xmlHttp.send(null);
    }
}

// Start polling the database.
pollDatabase();


function getOS() {
    var OSName="Unknown OS";
    if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
    if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
    if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
    if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";
    if (navigator.userAgent.indexOf("Android")!=-1) OSName="Android";
    if ((navigator.userAgent.match(/iPhone/i)) || (navigator.userAgent.match(/iPod/i)) || (navigator.userAgent.match(/iPad/i))) OSName="iOS";
    /* Source for Android and iOS:
    https://davidwalsh.name/detect-android
    https://davidwalsh.name/detect-iphone
    https://davidwalsh.name/detect-ipad */
    return OSName;
}

function getFilePathByOS() {
    var OSName = getOS();
    var windows = 'download-winvnc';
    var macOS = 'download-macvnc';
    var linux = 'download-linux';
    var download = '';
    switch(OSName) {
        case 'Windows': download = windows;
            break;
        case 'MacOS': download = macOS;
            break;
        case 'Linux': download = linux;
            break;
        case 'UNIX': download = linux;
            break;
        case 'Android':
        case 'iOS':
            document.getElementById("Screen").innerHTML = '<div class="description"><?=__('Sorry! Screensharing for your device is currently not supported.')?></div>';
            break;
        default: download = null;
    }
  document.getElementById(download).click();
}

function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    // Hide all elements with class="tabcontent"
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    // Remove "active" class from all elements with class="tablinks"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show current tab and add "active" class to the opening button
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function openSubtab(evt, tabName, subtabName) {
    var i, tab, subtabcontent, subtablinks;
    // Hide all elements with class="subtabcontent"
    tab = document.getElementById(tabName);
    subtabcontent = tab.getElementsByClassName("subtabcontent");
    for (i = 0; i < subtabcontent.length; i++) {
        subtabcontent[i].style.display = "none";
    }

    // Remove "active" class from all elements with class="subtablinks"
    subtablinks = tab.getElementsByClassName("subtablinks");
    for (i = 0; i < subtablinks.length; i++) {
        subtablinks[i].className = subtablinks[i].className.replace(" active", "");
    }

    // Show current subtab and add "active" class to the opening button
    document.getElementById(subtabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function openAccordion(divID) {
    var button = document.getElementById(divID).firstChild;
    var windowcontrols = document.getElementById(divID).lastChild;
    if (windowcontrols.style.display == "block") {
        windowcontrols.style.display = "none";
        button.setAttribute("class", "window_entry_button");
    } else if (windowcontrols.style.display == "none") {
        windowcontrols.style.display = "block";
        button.setAttribute("class", "window_entry_button active");
    } else {
        windowcontrols.style.display = "block";
        button.setAttribute("class", "window_entry_button active");
    }
}

function showDropdown() {
    document.getElementById("languageSelection").classList.toggle("show");
}
// Close the dropdown menu if the user clicks outside of it
// Must use some workaround to support IE.
window.onclick = function(event) {
  var classes = event.target.className.split(' ');
  var found = false; var i = 0;
  while (i < classes.length && !found) {
      if (classes[i]=='dropbutton') found = true;
      else ++i;
  }
  if (!found) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}
</script>
</head>

<body>

<!-- This formating is used to prevent empty textnodes that interfere with the design -->
<div class="tab"
    ><button class="tablinks" onclick="openTab(event, 'Add')"><?=__('Add')?><i class="fa fa-plus"></i></button
    ><button class="tablinks" id="controlbtn" onclick="openTab(event, 'Control')"><?=__('Control')?><i class="fa fa-arrows"></i></button
    ><button class="tablinks" onclick="openTab(event, 'Extras')"><?=__('Extras')?><i class="fa fa-info-circle"></i></button
></div>

<div id="workbench">
    <div id="Add" class="tabcontent">
        <div class="subtab"
        ><button class="subtablinks" onclick="openSubtab(event, 'Add', 'File')"><?=__('File')?><i class="fa fa-file"></i></button
        ><button class="subtablinks" onclick="openSubtab(event, 'Add', 'URL')"><?=__('URL')?><i class="fa fa-globe"></i></button
        ><button class="subtablinks" onclick="openSubtab(event, 'Add', 'Screen')"><?=__('Screen')?><i class="fa fa-video-camera"></i></button
    ></div>
        <div id="File" class="subtabcontent">
            <div id="file_upload">
                <form action="upload.php"
                    class="dropzone"
                    id="palma-dropzone"
                    title="<?=__('Drag and drop files or click here to upload.')?>">
                    <div class="dz-default dz-message">
                        <?=__('Add file (click or drop here)')?>
                        <i class="fa fa-file fa-2x"></i>
                    </div>
                </form>
                <div class="dz-preview dz-file-preview">
                    <div class="dz-details">
                        <div class="dz-filename"><span data-dz-name></span></div>
                        <div class="dz-size" data-dz-size></div>
                        <img data-dz-thumbnail src="" alt="">
                    </div>
                  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
                  <div class="dz-success-mark"><span> </span></div>
                  <div class="dz-error-mark"><span> </span></div>
                  <div class="dz-error-message"><span data-dz-errormessage></span></div>
                </div>
            </div>
        </div>
        <div id="URL" class="subtabcontent">
            <div>
                <input type="text" value="<?=__('Add webpage')?>"
                    id="url_field" maxlength="256" size="46"
                    onkeydown="if (event.keyCode == 13) document.getElementById('url_button').click()"
                    onfocus="clearURLField('<?=__('Enter URL')?>')">
                <button class="pure-button pure-button-primary pure-input-rounded"
                    id="url_button"
                    onClick="urlToNuc()" title="<?=__('Click here to show the webpage on the screen.')?>">
                    <?=__('Enter')?><i class="fa fa-globe"></i>
                </button>
            </div>
        </div>
        <div id="Screen" class="subtabcontent">
            <div id="vnc-button" onclick="javascript:getFilePathByOS()">
                <div id="vnc-button-container">
                    <div id="vnc-button-label"><?=__('Add your screen')?><i class="fa fa-video-camera fa-2x" aria-hidden="true"></i></div>
                    <div id="vnc-button-label-subtext"><?=__('Download screensharing tool')?></div>
                </div>
                <a href="<?php echo $winvnc; ?>" download id="download-winvnc" hidden></a>
                <a href="<?php echo $macvnc; ?>" download id="download-macvnc" hidden></a>
                <a href="<?php echo $linuxsh; ?>" download id="download-linux" hidden></a>
            </div>
            <div class="description">
            <?=__('Download your screensharing tool (Windows, Mac and Linux only). Visit the help section for further information.')?>
            </div>
        </div>
    </div>
    <div id="Control" class="tabcontent">
        <div class="subtab"
            ><button class="subtablinks" onclick="openSubtab(event, 'Control', 'Layout')"><?=__('Layout')?><i class="fa fa-desktop"></i></button
            ><button class="subtablinks" onclick="openSubtab(event, 'Control', 'Navigate')"><?=__('Navigate')?><i class="fa fa-arrows"></i></button
        ></div>
        <div id="Layout" class="subtabcontent">
            <div class="screenlayout">
                <button class="pure-button pure-button-primary pure-input-rounded"
                        id="g1x1" onclick="miniDisplaySelect(this)"
                        title="<?=__('Choose screen layout')?>">
                    <i alt="1" class="fa fa-desktop fa-2x" aria-hidden="true"></i>
                </button>
            </div
            ><div class="screenlayout vertical">
                <button class="pure-button pure-button-primary pure-input-rounded"
                        id="g1x2" onclick="miniDisplaySelect(this)"
                        title="<?=__('Choose screen layout')?>">
                    <i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                </button>
            </div
            ><div class="screenlayout">
                <button class="pure-button pure-button-primary pure-input-rounded"
                        id="g2x1" onclick="miniDisplaySelect(this)"
                        title="<?=__('Choose screen layout')?>">
                    <i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                </button>
            </div
            ><div class="screenlayout">
                <button class="pure-button pure-button-primary pure-input-rounded"
                        id="g1a2" onclick="miniDisplaySelect(this)"
                        title="<?=__('Choose screen layout')?>">
                    <div class="layout_left">
                    <i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    </div>
                    <div class="layout_right vertical">
                    <i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <i alt="3" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    </div>
                </button>
            </div
            ><div class="screenlayout">
                <button class="pure-button pure-button-primary pure-input-rounded"
                        id="g2x2" onclick="miniDisplaySelect(this)"
                        title="<?=__('Choose screen layout')?>">
                    <i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <br />
                    <i alt="3" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                    <i alt="4" class="fa fa-desktop fa-1x" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div id="Navigate" class="subtabcontent">
            <div id="windowlist">
            <!-- filled by updateWindowList and showLayout -->
            </div>
            <button id="closeWindows" class="pure-button pure-button-primary pure-input-rounded"
                onClick="sendToNuc('closeAll=TRUE')"
                title="<?=__('Close all windows and remove uploaded files')?>">
                <?=__('Delete all items')?>
            </button>
        </div>
    </div>
    <div id="Extras" class="tabcontent">
        <div class="subtab"
            ><button class="subtablinks active" onclick="openSubtab(event, 'Extras', 'Help')"><?=__('Help')?><i class="fa fa-question-circle"></i></button
            ><button class="subtablinks" onclick="openSubtab(event, 'Extras', 'Feedback')"><?=__('Feedback')?><i class="fa fa-thumbs-o-up"></i></button
            ><button class="subtablinks" onclick="openSubtab(event, 'Extras', 'Users')"><?=__('Users')?><i class="fa fa-users"></i></button
        ></div>
        <div id="Help" class="subtabcontent">
            <p><?=__('With PalMA, you can share documents, websites and your desktop with your learning group.')?>
            <?=__('The PalMA team monitor shows up to four contributions simultaneously.')?></p>
            <?php
            if (CONFIG_INSTITUTION_URL) { ?>
                <p><?=__('For further information about PalMA in this institution')?> <a href=<?=CONFIG_INSTITUTION_URL?> target="_blank"><?=__('see here.')?></a></p>
                <?php
            }
            ?>
            <h4><?=__('Connect')?><i class="fa fa-wifi"></i></h4>
            <p><?=__('Team members can join the session at any time with this URL or QR-Code:')?></p>
            <p class="url_hint"><?=$_SESSION['starturl']?><br />
            <?php
            if (!empty($_SESSION['pin'])) {
                echo __('PIN: ');
                echo $_SESSION['pin'];
            }
            ?>
            </p>
            <img class="qr-code" src="qrcode/php/qr_img.php?d=<?=$_SESSION['starturl']?>?pin=<?=$_SESSION['pin']?>" alt="QR Code">

            <h4><?=__('Add')?><i class="fa fa-plus"></i></h4>
                <p><?=__('Use the Add-Section to share content on the PalMA monitor.')?></p>
                <ul>
                <li><i class="fa fa-file"></i> <?=__('For PDF files, office files, images or videos use the file section.')?></li>
                <li><i class="fa fa-globe"></i> <?=__('To display a website use the URL field.')?></li>
                <li><i class="fa fa-video-camera"></i> <?=__('To share your desktop in real time download the VNC screen sharing software and')?></li>
                    <ul>
                    <li><i class="fa fa-windows"></i> <?=__('Windows:')?></li>
                        <ul>
                        <li><?=__('Run the downloaded file.')?></li>
                        <li><?=__('Double-click the name of your PalMA station in the appearing list.')?></li>
                        </ul>
                    <li><i class="fa fa-apple"> </i> <?=__('Mac:')?></li>
                        <ul>
                        <li><?=__('CTRL + click the downloaded file and run it.')?></li>
                        <li><?=__('A second window opens, in which you can start &quot;VineServer&quot;.')?></li>
                        <li><?=__('In the upper left corner, click on &quot;VNC Server&quot;.')?></li>
                        <li><?=__('Select &quot;Reverse Connection&quot;.')?></li>
                        <li><?=__('Enter the URL of your PalMA station and click &quot;Connect&quot;.')?></li>
                        </ul>
                    <li><i class="fa fa-linux"></i> <?=__('Linux:')?></li>
                        <ul>
                        <li><?=__('Run the downloaded shell script.')?></li>
                        <li><?=__('Or use this shell command:')?> <code>x11vnc -connect <?=$_SERVER['HTTP_HOST']?></code></li>
                        </ul>
                    </ul>
                </ul>
            <h4><?=__('Control')?><i class="fa fa-arrows"></i></h4>
            <p><?=__('With the grey monitor buttons at the top you can choose how the shared content should be arranged on the PalMA monitor.')?></p>
            <p><?=__('Below that you find the controls for each item:')?></p>
            <ul>
            <li><?=__('In the top bar you can')?></li>
            <ul>
            <li><?=__('Hide and show')?><i class="fa fa-eye"></i>,</li>
            <li><?=__('Zoom in and out')?><i class="fa fa-search-plus"></i><i class="fa fa-search-minus"></i>,</li>
            <li><?=__('Rotate')?><i class="fa fa-rotate-left"></i><i class="fa fa-rotate-right"></i>,</li>
            <li><?=__('Download')?><i class="fa fa-download"></i>,</li>
            <li><?=__('Delete the item')?><i class="fa fa-trash-o"></i>.</li>
            </ul>
            <li><?=__('Below you find the navigation controls.')?></li>
            <ul>
            <li><?=__('Buttons on the left jump to the top, to the end, a page up or a page down')?><i class="fa fa-angle-double-up"></i><i class="fa fa-angle-up"></i><i class="fa fa-angle-down"></i><i class="fa fa-angle-double-up"></i>.</li>
            <li><?=__('Arrow buttons in the middle scroll gradually')?><i class="fa fa-toggle-up"></i><i class="fa fa-toggle-down"></i><i class="fa fa-toggle-left"></i><i class="fa fa-toggle-right"></i>.</li>
            </ul>
            </li>
            <li><?=__('On the right you can choose the position on the PalMA monitor')?><i class="fa fa-desktop"></i>.</li>
            </ul>
            <p><?=__('Controls that are not available for certain kinds of content are marked grey.')?></p>
            <h4><?=__('Extras')?><i class="fa fa-info-circle"></i></h4>
            <p><?=__('Some additional features are:')?></p>
            <ul>
            <li><i class="fa fa-question-circle"></i> <?=__('This help,')?></li>
            <li><i class="fa fa-thumbs-o-up"></i> <?=__('Your chance to recommend us or give us your thoughts in the &quot;Feedback&quot; section,')?></li>
            <li><i class="fa fa-users"></i> <?=__('A list of all logged-in users as well as a button to disconnect everyone and therefore end the session.')?></li>
            </ul>
        </div>
        <div id="Feedback" class="subtabcontent">
            <div id="recommendcontainer">
                <h3>
                    <?=__('Recommend us')?>
                </h3>
                <div>
                    <p><?=__('If you like PalMA, please recommend us by sharing in your social networks.<br />Enjoy PalMA!')?></p>
                    <!-- Social Media Button Integration, Source: http://sharingbuttons.io/ -->
                    <?php $github_url = "https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md"; ?>

                    <!-- Sharingbutton Facebook -->
                    <a class="resp-sharing-button__link" href="https://facebook.com/sharer/sharer.php?u=<?=$github_url?>" target="_blank" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--facebook resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                        </div>
                      </div>
                    </a>

                    <!-- Sharingbutton Twitter -->
                    <a class="resp-sharing-button__link" href="https://twitter.com/intent/tweet/?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;url=<?=$github_url?>" target="_blank" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--twitter resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/></svg>
                        </div>
                      </div>
                    </a>

                    <!-- Sharingbutton Google+ -->
                    <a class="resp-sharing-button__link" href="https://plus.google.com/share?url=<?=$github_url?>" target="_blank" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--google resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.37 12.93c-.73-.52-1.4-1.27-1.4-1.5 0-.43.03-.63.98-1.37 1.23-.97 1.9-2.23 1.9-3.57 0-1.22-.36-2.3-1-3.05h.5c.1 0 .2-.04.28-.1l1.36-.98c.16-.12.23-.34.17-.54-.07-.2-.25-.33-.46-.33H7.6c-.66 0-1.34.12-2 .35-2.23.76-3.78 2.66-3.78 4.6 0 2.76 2.13 4.85 5 4.9-.07.23-.1.45-.1.66 0 .43.1.83.33 1.22h-.08c-2.72 0-5.17 1.34-6.1 3.32-.25.52-.37 1.04-.37 1.56 0 .5.13.98.38 1.44.6 1.04 1.84 1.86 3.55 2.28.87.23 1.82.34 2.8.34.88 0 1.7-.1 2.5-.34 2.4-.7 3.97-2.48 3.97-4.54 0-1.97-.63-3.15-2.33-4.35zm-7.7 4.5c0-1.42 1.8-2.68 3.9-2.68h.05c.45 0 .9.07 1.3.2l.42.28c.96.66 1.6 1.1 1.77 1.8.05.16.07.33.07.5 0 1.8-1.33 2.7-3.96 2.7-1.98 0-3.54-1.23-3.54-2.8zM5.54 3.9c.33-.38.75-.58 1.23-.58h.05c1.35.05 2.64 1.55 2.88 3.35.14 1.02-.08 1.97-.6 2.55-.32.37-.74.56-1.23.56h-.03c-1.32-.04-2.63-1.6-2.87-3.4-.13-1 .08-1.92.58-2.5zM23.5 9.5h-3v-3h-2v3h-3v2h3v3h2v-3h3"/></svg>
                        </div>
                      </div>
                    </a>

                    <!-- Sharingbutton E-Mail -->
                    <a class="resp-sharing-button__link" href="mailto:?subject=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;body=<?=$github_url?>" target="_self" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--email resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 4H2C.9 4 0 4.9 0 6v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM7.25 14.43l-3.5 2c-.08.05-.17.07-.25.07-.17 0-.34-.1-.43-.25-.14-.24-.06-.55.18-.68l3.5-2c.24-.14.55-.06.68.18.14.24.06.55-.18.68zm4.75.07c-.1 0-.2-.03-.27-.08l-8.5-5.5c-.23-.15-.3-.46-.15-.7.15-.22.46-.3.7-.14L12 13.4l8.23-5.32c.23-.15.54-.08.7.15.14.23.07.54-.16.7l-8.5 5.5c-.08.04-.17.07-.27.07zm8.93 1.75c-.1.16-.26.25-.43.25-.08 0-.17-.02-.25-.07l-3.5-2c-.24-.13-.32-.44-.18-.68s.44-.32.68-.18l3.5 2c.24.13.32.44.18.68z"/></svg>
                        </div>
                      </div>
                    </a>

                    <!-- Sharingbutton WhatsApp -->
                    <a class="resp-sharing-button__link" href="whatsapp://send?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.%20<?=$github_url?>" target="_blank" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--whatsapp resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.1 3.9C17.9 1.7 15 .5 12 .5 5.8.5.7 5.6.7 11.9c0 2 .5 3.9 1.5 5.6L.6 23.4l6-1.6c1.6.9 3.5 1.3 5.4 1.3 6.3 0 11.4-5.1 11.4-11.4-.1-2.8-1.2-5.7-3.3-7.8zM12 21.4c-1.7 0-3.3-.5-4.8-1.3l-.4-.2-3.5 1 1-3.4L4 17c-1-1.5-1.4-3.2-1.4-5.1 0-5.2 4.2-9.4 9.4-9.4 2.5 0 4.9 1 6.7 2.8 1.8 1.8 2.8 4.2 2.8 6.7-.1 5.2-4.3 9.4-9.5 9.4zm5.1-7.1c-.3-.1-1.7-.9-1.9-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.1-.2.2-.3.2-.6.1s-1.2-.5-2.3-1.4c-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6s.3-.3.4-.5c.2-.1.3-.3.4-.5.1-.2 0-.4 0-.5C10 9 9.3 7.6 9 7c-.1-.4-.4-.3-.5-.3h-.6s-.4.1-.7.3c-.3.3-1 1-1 2.4s1 2.8 1.1 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.3.2-.7.2-1.2.2-1.3-.1-.3-.3-.4-.6-.5z"/></svg>
                        </div>
                      </div>
                    </a>

                    <!-- Sharingbutton Telegram -->
                    <a class="resp-sharing-button__link" href="https://telegram.me/share/url?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;url=<?=$github_url?>" target="_blank" aria-label="">
                      <div class="resp-sharing-button resp-sharing-button--telegram resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
                          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M.707 8.475C.275 8.64 0 9.508 0 9.508s.284.867.718 1.03l5.09 1.897 1.986 6.38a1.102 1.102 0 0 0 1.75.527l2.96-2.41a.405.405 0 0 1 .494-.013l5.34 3.87a1.1 1.1 0 0 0 1.046.135 1.1 1.1 0 0 0 .682-.803l3.91-18.795A1.102 1.102 0 0 0 22.5.075L.706 8.475z"/></svg>
                        </div>
                      </div>
                    </a>
                </div> <!-- Social media buttons and description -->
            </div> <!-- recommendcontainer -->
            <div id="contactcontainer">
                <h3><?=__('Tell us what you think')?></h3>
                <div>
                    <p><?=__('Please let us know about problems or ideas to improve PalMA. Help us directly by sending crash reports or contributing on ')?><a href="https://github.com/UB-Mannheim/PalMA" target="_blank">GitHub</a>.<br /><?=__('Thank you!')?></p>
                    <iframe width="100%" height="500" frameborder="0" src="https://www.bib.uni-mannheim.de/palma/feedback/form"></iframe>
                </div>
            </div> <!-- contactcontainer -->
        </div> <!-- feedback -->
        <div id="Users" class="subtabcontent">
            <div class="dropdown">
                <button onclick="showDropdown()" class="dropbutton pure-button pure-button-primary"><?=__('Select language:')?> <?=substr($locale, 0, 2)?><i class="fa fa-caret-down"></i></button>
                <div id="languageSelection" class="dropdown-content">
                <?php
                $dirs = array_slice(scandir('locale'), 2);
                $langs = [];
                for ($n = 0; $n < count($dirs); $n++) {
                    if (is_dir("locale/$dirs[$n]")) {
                        array_push($langs, substr($dirs[$n], 0, 2));
                    }
                }
                for ($i = 0; $i < count($langs); $i++) {
                    echo "<a href=$_SERVER[PHP_SELF]?lang=$langs[$i]>$langs[$i]</a>";
                }
                ?>
                </div>
            </div>
            <div class="list_container">
                <table class="userlist" summary="<?=__('User list')?>" title="<?=__('List of connected users')?>">
                    <tbody id="userlist">
                        <tr><td><!-- filled by updateUserList() --></td></tr>
                    </tbody>
                </table>
                <div class="description">
                    <?=__('New users can join at')?><br /><?=$_SESSION['starturl']?><br />
                    <?php
                    if (!empty($_SESSION['pin'])) {
                        echo __('PIN: ');
                        echo $_SESSION['pin'];
                    }
                    ?>
                    <img class="qr-code" src="qrcode/php/qr_img.php?d=<?=$_SESSION['starturl']?>?pin=<?=$_SESSION['pin']?>" alt="QR Code">
                </div>
                <button class="pure-button pure-button-primary pure-input-rounded"
                        onClick="sendToNuc('logout=ALL')"
                        title="<?=__('Disconnect all users and end the session')?>">
                    <?=__('End the session')?>
                </button>
            </div>
        </div>
    </div>
</div> <!-- workbench -->

<div id="footer">
    <?php
      # Show authorized user name (and address) and allow logout.
    if ($user) {
        echo("<a href=\"logout.php\" title=\"" .
          __('Disconnect the current user') .
          "\">" . __('Log out') . "<i class=\"fa fa-sign-out\"></i></a>");
    }
    ?>
</div> <!-- Footer -->

<?php

    $plugin_dir = "plugins/";
    $stats = "piwik.php";

if (file_exists($plugin_dir . $stats)) {
    include $plugin_dir . $stats;
}

?>

</body>
</html>
