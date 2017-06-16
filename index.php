<?php
/*
Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Authors: Alexander Wagner, Stefan Weil

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
    require('auth.php');

    // Connect to database and get configuration constants.
    require_once('DBConnector.class.php');
    $dbcon = new DBConnector();

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

    /*
     * file paths for vnc downloads
     */
    $winvnc = CONFIG_START_URL . "theme/" . CONFIG_THEME . "/winvnc-palma.exe";
    $macvnc = CONFIG_START_URL . "theme/" . CONFIG_THEME . "/VineServer.dmg";
    $linuxsh = CONFIG_START_URL . "theme/" . CONFIG_THEME . "/x11.sh";


    /*
     * contact form elements
     * might be sourced out and included
     */
    if(isset($_POST['submit'])){
        $to = "alexander.wagner@bib.uni-mannheim.de";
        $from = $_POST['email'];
        $name = $_POST['name'];
        $subject = "Feedback for PalMA";
        $message = $name . " wrote the following:" . "\n\n" . $_POST['message'];

        $headers = "From:" . $from;
        mail($to,$subject,$message,$headers);
        // echo "Mail Sent. Thank you for your feedback " . $name . ", we will get in touch with you shortly.";
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

<style "text/css">
#tabcontainer ul
{
    margin: 0;
    padding: 0;
    list-style-type: none;
    text-align: left;
    }

#tabcontainer ul li {
    display: inline;
    }

#tabcontainer ul li a
{
    text-decoration: none;
    padding: .4em 2em;
    color: #fff;
    background-color: #990000;
    border-radius: 5px 5px 0px 0px;
    line-height:2em;
    }

#tabcontainer ul li a:hover
{
color: #fff;
    background-color: #992930;
    }

#tabcontainer {
    margin-top:1em;
    }

#tabCtrl {
    padding:1em;
    border: 1px solid #990000;
    border-radius: 0px 10px 10px 10px;
    background-color:#FFFFFF;"
    }

/*
 * contact form elements
 * 2do: source out to css file
 */
#contactwindow div#contactcontainer div form#contact div.container input, textarea {
    line-height: normal;
    margin: 0.2em;
    padding: 0.7em;
    width: 80%;
}
#contactwindow div#contactcontainer div form#contact div.container textarea {
    height: 200px;
}

</style>

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

function keyControl(number, image, key, handler, disabled, title) {
  var td = document.createElement('td');

  var keyHandler = getHandlerCommand(handler, key);
  if ( (keyHandler == "") || (keyHandler == null) ) {
    keyHandler = "default";
  }

  var button = document.createElement('button');
  var i = document.createElement('i');
  i.setAttribute('class', 'fa fa-fw ' + image);
  button.setAttribute('class', 'controlbutton pure-button pure-button-primary pure-input-rounded');
  button.appendChild(i);
  button.setAttribute('onmousedown',
                     'sendToNuc("window=' + number + '&keydown=' + encodeURIComponent(keyHandler) + '")');
  button.setAttribute('onmouseup',
                     'sendToNuc("window=' + number + '&keyup=' + encodeURIComponent(keyHandler) + '")');
  button.setAttribute('title', title);
  if (disabled) {
    button.setAttribute('disabled', '');
  }
  td.appendChild(button);
  return td;
}

function remoteControl(number, control) {

    var overlay = document.createElement('div');
    overlay.setAttribute('id', 'overlay');
    overlay.setAttribute('class', 'overlay');

    var container = document.createElement('div');
    container.setAttribute('id', 'container');

    var caption = document.createElement('div');
    caption.setAttribute('id', 'caption');
    caption.appendChild(document.createTextNode('<?=__("Screen section")?> '+ number));

    var close = document.createElement('div');
    var i = document.createElement('i');
    i.setAttribute('class', 'fa fa-times');
    i.setAttribute('title', '<?=__("Close the advanced control")?>');
    close.setAttribute('id', 'close');
    close.appendChild(i);
    close.setAttribute('onclick',       'restore()');

    container.appendChild(close);
    container.appendChild(caption);
    var table = addDetailedControlsDiv(number, control);
    container.appendChild(table);
    overlay.appendChild(container);
    var appendTo = document.getElementById('maindisplay');
    appendTo.appendChild(overlay);

    document.body.appendChild(overlay);
}

function restore() {
    document.body.removeChild(document.getElementById('overlay'));
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
    return url.match(/(^(ht|f)tps?:\/\/)([a-z0-9\.-])+(\.([a-z]{2,}))(\/([^\s\<\>\,\{\}\\\|\^\[\]\'])*)?$/);
}

function urlToNuc() {

    var url = document.getElementById('url_field').value;
    //~ alert(url);
    if (is_valid_url(url)) {
        // Convert URL to UTF-8 because of special characters
        url = encodeURIComponent(url);
        sendToNuc('openURL='+url);
    } else {
        var urlfield = document.getElementById('url_field');
        urlfield.setAttribute('value', '<?=__("Enter valid URL")?>');
    }

    setTimeout(function(){location.reload()}, 1000);
}

function addControls(number, control, is_overlay) {
  // Add the basic controls up, down, left, right.
  var tr;
  var td;
  var button;

  if (typeof control == "undefined") {
    control = ["default", false, false, false, false,
               false, false, false, false, false, false, false];
  }

  // get handler
  var handler = control[0];

  var up = control[1];
  var down = control[2];
  var left = control[3];
  var right = control[4];

  // Show the overlay button only if extended controls are supported
  // (zoomin, zoomout, home, end, prior, next, download).
  var disableOverlayButton = is_overlay ||
    !(control[5] || control[6] || control[7] || control[8] ||
      control[9] || control[10] || control[11]);

  var div = document.createElement('div');
  var table = document.createElement('table');
  table.setAttribute('class', 'control');
  tr = document.createElement('tr');
  tr.appendChild(document.createElement('td'));
  // TODO: try fa-arrow-up, fa-carret-up, fa-long-arrow-up, fa-angle-up, fa-play
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-270', 'up',
                            handler, !up, '<?=__("Cursor control")?>'));
  tr.appendChild(document.createElement('td'));
  table.appendChild(tr);
  tr = document.createElement('tr');
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-180', 'left',
                            handler, !left, '<?=__("Cursor control")?>'));
  td = document.createElement('td');
  button = document.createElement('button');
  button.appendChild(document.createTextNode(number));
  button.setAttribute('class', 'pure-button pure-button-primary pure-input-rounded');
  if (disableOverlayButton) {
      button.setAttribute('disabled', '');
  } else {
      button.setAttribute('id', 'more');
      button.setAttribute('onclick', 'remoteControl(' + number + ', ' + JSON.stringify(control) + ')');
      button.setAttribute('title', '<?=__("Advanced control")?>');
  }
  td.appendChild(button);
  tr.appendChild(td);
  tr.appendChild(keyControl(number, 'fa-play', 'right',
                            handler, !right, '<?=__("Cursor control")?>'));
  table.appendChild(tr);
  tr = document.createElement('tr');
  tr.appendChild(document.createElement('td'));
  tr.appendChild(keyControl(number, 'fa-play fa-rotate-90', 'down',
                            handler, !down, '<?=__("Cursor control")?>'));
  tr.appendChild(document.createElement('td'));
  table.appendChild(tr);
  div.appendChild(table);

  return div;
}

function addSimpleControlsTd(number, controls) {
    var td = document.createElement('td');
    td.appendChild(addControls(number, controls[number], false));
    td.setAttribute('id', 'section' + number);
    return td;
}

function addDetailedControlsDiv(number, control) {

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

    var controlpanel;
    var updown, zoom, bar;

    var table, tr, td;

    controlpanel = document.createElement('div');
    controlpanel.setAttribute('class', 'controlpanel');

    updown = addControls(number, control, true);
    updown.setAttribute('class', 'updown');

    controlpanel.appendChild(updown);

    zoom = document.createElement('div');
    zoom.setAttribute('class', 'zoom');
    table = document.createElement('table');

    tr = document.createElement('tr');
    td = keyControl(number, 'fa-search-plus', 'zoomin',
                    handler, !zoomin, '<?=__("Zoom in")?>');
    tr.appendChild(td);
    table.appendChild(tr);

    tr = document.createElement('tr');
    td = keyControl(number, 'fa-search-minus', 'zoomout',
                    handler, !zoomout, '<?=__("Zoom out")?>');
    tr.appendChild(td);
    table.appendChild(tr);

    zoom.appendChild(table);

    controlpanel.appendChild(zoom);

    bar = document.createElement('div');
    bar.setAttribute('class', 'bar');
    table = document.createElement('table');

    tr = document.createElement('tr');

    td = keyControl(number, 'fa-step-backward', 'home',
                    handler, !home, "<?=__('Jump to start')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-backward', 'prior',
                    handler, !prior, "<?=__('Page up')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-forward', 'next',
                    handler, !next, "<?=__('Page down')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-step-forward', 'end',
                    handler, !end, "<?=__('Jump to end')?>");
    tr.appendChild(td);
    td = keyControl(number, 'fa-download', 'download',
                    handler, !download, '<?=__("Download this file")?>');
    td.setAttribute('onclick', 'downloadFile(' + number + ')');
    tr.appendChild(td);

    table.appendChild(tr);

    bar.appendChild(table);

    controlpanel.appendChild(bar);

    return controlpanel;
}

function showLayout(layout, controls) {

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

  var md = document.getElementById('maindisplay');
  var tr;
  var td;
  while (md.firstChild) {
    md.removeChild(md.firstChild);
  }
  switch (layout) {
  case 'g1x1':
    // Show only one segment (full screen).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    md.appendChild(tr);
    break;
  case 'g2x1':
    // Show two segments (side by side).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    break;
  case 'g1x2':
    // Show two segments (one on top of the other).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    break;
  case 'g1a2':
    // Show three segments (one on the left, two on the right).
    tr = document.createElement('tr');
    td = addSimpleControlsTd(1, controls);
    td.setAttribute('rowspan', '2');
    tr.appendChild(td);
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(3, controls));
    md.appendChild(tr);
    break;
  case 'g2x2':
    // Show four segments (one in each quadrant).
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(1, controls));
    tr.appendChild(addSimpleControlsTd(2, controls));
    md.appendChild(tr);
    tr = document.createElement('tr');
    tr.appendChild(addSimpleControlsTd(3, controls));
    td = document.createElement('td');
    tr.appendChild(addSimpleControlsTd(4, controls));
    md.appendChild(tr);
    break;
  default:
    // No supported screen layout selected. This should never happen.
    tr = document.createElement('tr');
    td = document.createElement('td');
    td.appendChild(document.createTextNode('Bitte Bildschirmaufteilung auswählen!'));
    tr.appendChild(td);
    md.appendChild(tr);
    break;
  }
}

function miniDisplaySelect(element) {
    sendToNuc('layout=' + element.id);
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
    handler["midori"]["download"] = "download";

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

function showHelp(visible) {
    // Hide or show an overlay window with help text and some extra functions.
    var element = document.getElementById('helpwindow');
    if (visible) {
        element.style.visibility = "visible";
        element.style.display = "block";
    } else {
        element.style.visibility = "hidden";
        element.style.display = "none";
    }
}

function showContact(visible) {
    // Hide or show an overlay window with help text and some extra functions.
    var element = document.getElementById('contactwindow');
    if (visible) {
        element.style.visibility = "visible";
        element.style.display = "block";
    } else {
        element.style.visibility = "hidden";
        element.style.display = "none";
    }
}

function showRecommendation(visible) {
    // Hide or show an overlay window with help text and some extra functions.
    var element = document.getElementById('recommendwindow');
    if (visible) {
        element.style.visibility = "visible";
        element.style.display = "block";
    } else {
        element.style.visibility = "hidden";
        element.style.display = "none";
    }
}

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

function updateWindowList(window) {
    // Update the window list on screen from the table in the database.

    // Get the <tbody> element which contains the window entries.
    var list = document.getElementById('windowlist');

    // First we remove all existing <tr> elements.
    while (list.firstChild) {
        list.removeChild(list.firstChild);
    }

    var tr;
    var td;

    if (window.length == 0) {
        // If there is no window, we display an empty entry.
        tr = document.createElement('tr');
        td = document.createElement('td');
        td.appendChild(document.createTextNode("\u00a0"));
        tr.appendChild(td);
        list.appendChild(tr);
    } else {
        // Add an entry for each window.
        var n;
        for (n = 0; n < window.length; n++) {
            var screensection = window[n].section;
            var file = window[n].file;
            var handler = window[n].handler;
            var s0 = false;
            var s1 = false;
            var s2 = null;
            var s3 = null;
            var s4 = null;
            var status = window[n].state;
            switch (screensection) {
                case 1:
                    s1 = 'selected';
                    break;
                case 2:
                    s2 = 'selected';
                    break;
                case 3:
                    s3 = 'selected';
                    break;
                case 4:
                    s4 = 'selected';
                    break;
                default:
                    s0 = 'selected';
                    status = 'inactive';
                    break;
            }
            var tr = document.createElement('tr');
            var td;
            var i;
            var div;
            var option;
            td = document.createElement('td');
            td.setAttribute('id', 'itemstatus');
            i = document.createElement('i');
            if (status == 'active') {
                i.setAttribute('class', 'fa fa-eye');
            } else {
                i.setAttribute('class', 'fa fa-ban');
            }
            i.setAttribute('id', 'status_' + screensection);
            i.setAttribute('title', '<?=__("Toggle visibility")?>');
            i.setAttribute('onclick',
                "sendToNuc('window=" + screensection + "&toggle=TRUE')");
            td.appendChild(i);
            tr.appendChild(td);
            var select = document.createElement('select');
            select.setAttribute('onchange', "sendToNuc('switchWindows=TRUE&before=" + screensection + "&after='+(this.value))");
            select.setAttribute('name', 'window');
            select.setAttribute('title', '<?=str_replace("'", "\\'", __("Select screen section for display"))?>');
            option = document.createElement('option');
            option.setAttribute('value', '0');
            if (s0) {
                option.setAttribute(s0, '');
            }
            option.appendChild(document.createTextNode('-'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '1');
            if (s1) {
                option.setAttribute(s1, '');
            }
            option.appendChild(document.createTextNode('1'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '2');
            if (s2) {
                option.setAttribute(s2, '');
            }
            option.appendChild(document.createTextNode('2'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '3');
            if (s3) {
                option.setAttribute(s3, '');
            }
            option.appendChild(document.createTextNode('3'));
            select.appendChild(option);
            option = document.createElement('option');
            option.setAttribute('value', '4');
            if (s4) {
                option.setAttribute(s4, '');
            }
            option.appendChild(document.createTextNode('4'));
            select.appendChild(option);
            td.appendChild(select);
            tr.appendChild(td);
            td = document.createElement('td');
            div = document.createElement('div');
            div.setAttribute('id', 'file' + screensection);
            // display only the last part of the URL or file name.
            // Long names are truncated, and the truncation is indicated.
            var fname = file;
            if (fname.substring(0, 4) == 'http') {
                // Remove a terminating slash from an URL.
                // The full URL will be shown as a tooltip.
                fname = fname.replace(/\/$/, '');
                fname = fname.replace(/^.*\//, '');
                div.setAttribute('title', file);
            } else {
                // For files only the full base name is shown as a tooltip.
                fname = fname.replace(/^.*\//, '');
                div.setAttribute('title', fname);
            }
            if (fname.length > 18) {
                fname = fname.substring(0, 15) + '...';
            }
            div.appendChild(document.createTextNode(fname));
            td.appendChild(div);
            tr.appendChild(td);
            td = document.createElement('td');
            td.setAttribute('id', 'itemtrash');
            i = document.createElement('i');
            i.setAttribute('class', 'fa fa-trash-o');
            i.setAttribute('onclick', "sendToNuc('window=" + screensection + "&delete=" + file + "')");
            i.setAttribute('title', '<?=__("Remove this object")?>');
            td.appendChild(i);
            tr.appendChild(td);
            list.appendChild(tr);
        }
    }
}

function updateControlsBySection(window) {

    // get section and handler for each window
    var sectionControls = [];

    for (n = 0; n < window.length; n++) {
        var win_id = window[n].win_id;
        var section = window[n].section;
        var file = window[n].file;
        var handler = window[n].handler;

        // alert("Section: " + section + " - Handler: " + handler);

        if (handler.indexOf("feh") > -1) {
            // up down left right zoomin zoomout home end prior next download
            control = ["feh", true, true, true, true, true, true, false, false, true, true, true];
        } else if (handler.indexOf("libreoffice") > -1) {
            // Controls in LibreOffice: no zoom in calc and writer, has to be activated first
            // by pressing <Ctrl+Shift+o> (switch view mode on/off) not implemented yet
            control = ["libreoffice", true, true, true, true, false, false, false, false, true, true, true];
                if (handler.indexOf("--calc") > -1) {
                    control = ["libreoffice-calc", true, true, true, true, false, false, true, true, true, true, true];
                }
                if (handler.indexOf("--impress") > -1) {
                    control = ["libreoffice-impress", true, true, true, true, true, true, true, true, true, true, true];
                }
                if (handler.indexOf("--writer") > -1) {
                    control = ["libreoffice-writer", true, true, true, true, false, false, false, false, true, true, true];
                }
        } else if (handler.indexOf("midori") > -1) {
            control = ["midori", true, true, true, true, true, true, true, true, true, true, true];
        } else if (handler.indexOf("vlc") > -1) {
            control = ["vlc", false, false, false, true, false, false, false, false, false, false, false];
        } else if (handler.indexOf("vnc") > -1) {
            control = ["vnc", true, true, true, true, true, true, false, false, false, false, false];
        } else if (handler.indexOf("zathura") > -1) {
            control = ["zathura", true, true, true, true, true, true, true, true, true, true, true];
        } else {
            control = ["undefined", false, false, false, false, false, false, false, false, false, false, false];
        }

        sectionControls[section] = control;
    }

    // Fill empty sections with default values.
    for (i = sectionControls.length; i < 5; i++) {
        sectionControls[i] = ["default",
                              false, false, false, false,
                              false, false, false, false,
                              false, false, false];
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
                    showLayout(layout, controls);
                    updateUserList(db.address, db.user);
                    updateWindowList(db.window);
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

//Show different content on small devices
function showToggleDisplay(source) {
	if (source == "#workbench_right") {
		document.getElementById("maindisplay").style.display = "none";
		document.getElementById("displaylist").style.display = "none";
		document.getElementById("url_doc").style.display = "none";
		$( source ).toggle(250);
	} else {
		if (source == "#maindisplay") {
			document.getElementById("displaylist").style.display = "none";
			document.getElementById("url_doc").style.display = "none";
		} else if (source == "#displaylist") {
		document.getElementById("maindisplay").style.display = "none";
		document.getElementById("url_doc").style.display = "none";
		} else if (source == "#url_doc") {
		document.getElementById("maindisplay").style.display = "none";
		document.getElementById("displaylist").style.display = "none";
		} else {
		}
		document.getElementById("workbench_right").style.display = "none";
		document.getElementById("workbench_left").style.display = "block";
		$( source ).toggle(250);
	}
}

</script>

</head>

<body id="workbench_outer">

<div id="workbench">

<div>
            <img id="palma_logo"
                 src="theme/<?=CONFIG_THEME?>/palma-logo-49x18.png"
                 alt="PalMA"> <?=__('Team display')?>
        <?php
              if (isset($_SESSION['monitor'])) {
                  echo('(' . $_SESSION['monitor'] . ')');
              }
        ?>
        </div>



<div id="show_hide">
		<button class="pure-button pure-button-primary pure-input-rounded" onclick="showToggleDisplay('#maindisplay')"><?=__('Controls')?></button>
		<button class="pure-button pure-button-primary pure-input-rounded" onclick="showToggleDisplay('#displaylist')"><?=__('Screen layout')?></button>
		<button class="pure-button pure-button-primary pure-input-rounded" onclick="showToggleDisplay('#url_doc')"><?=__('URL / Document')?></button>
		<button class="pure-button pure-button-primary pure-input-rounded" onclick="showToggleDisplay('#workbench_right')"><?=__('Menu')?></button>
		</div>

<div id="workbench_right">
    <?php
      # Show authorized user name (and address) and allow logout.
      if ($user) {
        echo("<p><a href=\"logout.php\" title=\"" .
            __('Disconnect the current user') .
            "\">Log Out<i class=\"fa fa-sign-out\"></i></a></p>");
      }
    ?>
<div class="list_container">
    <table class="userlist" summary="<?=__('User list')?>" title="<?=__('List of connected users')?>">
        <caption><?=__('User list')?><i class="fa fa-users"></i></caption>
        <tbody id="userlist">
            <tr><td><!-- filled by function updateUserList() --></td></tr>
        </tbody>
    </table>


    <button class="pure-button pure-button-primary pure-input-rounded"
            onClick="sendToNuc('logout=ALL')"
            title="<?=__('Disconnect all users and reset the work place')?>">
        <?=__('Disconnect all users')?>
    </button>
</div>

<div class="list_container">
        <table class="itemlist" summary="<?=__('Display list')?>"
               title="<?=__('List of files, URLs and shared displays')?>">
            <caption><?=__('Display list')?><i class="fa fa-desktop"></i></caption>
            <tbody id="windowlist">
                <tr><td><!-- filled by function updateWindowList() --></td></tr>
            </tbody>
        </table>
    <button class="pure-button pure-button-primary pure-input-rounded"
            onClick="sendToNuc('closeAll=TRUE')"
            title="<?=__('Close all windows and remove uploaded files')?>">
        <?=__('Close all windows')?>
    </button>
	</div>

    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="showHelp(true)"
        title="<?=__('Show help and offer some extras')?>">
        <?=__('Help + Extras')?>
        <i class="fa fa-question-circle"></i>
    </button>

    <div id="feedback_share" style="margin-top:17em;">

    <button style="background-color:transparent;border:2px solid #333333; color: #333333"
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="showContact(true)"
        title="<?=__('Give us some Feedback')?>">
        <i class="fa fa-bullhorn fa-2x" aria-hidden="true"></i>
        <?=__('Give Feedback')?>
    </button>

    <br />

    <button style="background-color:transparent;border:2px solid #333333; color: #333333"
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="showRecommendation(true)"
        title="<?=__('Recommend us')?>">
        <i class="fa fa-thumbs-o-up fa-2x" aria-hidden="true"></i>
        <?=__('Recommend us')?>
        <? /* =__('Like and share!') */ ?>
    </button>

    </div>

</div> <!-- workbench_right -->

<div id="workbench_left">
    <table class="maindisplay" summary="<?=__('Team display')?>">
        <tbody id='maindisplay'>
            <tr><td><!-- filled by function showLayout() --></td></tr>
        </tbody>
    </table>


    <table class="minidisplaylist" id="displaylist" summary="<?=__('Screen layout')?>">
        <caption><?=__('Screen layout')?></caption>
        <tr>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1x1" onclick="miniDisplaySelect(this)"
                    title="<?=__('Choose screen layout')?>">
              <table><tr><td><i alt="1" class="fa fa-desktop fa-2x" aria-hidden="true"></i></td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1x2" onclick="miniDisplaySelect(this)"
                    title="<?=__('Choose screen layout')?>">
              <table><tr><td><i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr><tr><td><i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g2x1" onclick="miniDisplaySelect(this)"
                    title="<?=__('Choose screen layout')?>">
              <table><tr><td><i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></td><td><i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g1a2" onclick="miniDisplaySelect(this)"
                    title="<?=__('Choose screen layout')?>">
              <table><tr><td rowspan="2"><i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></td><td><i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr><tr><td><i alt="3" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr></table>
            </button>
          </div></td>
          <td><div>
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="g2x2" onclick="miniDisplaySelect(this)"
                    title="<?=__('Choose screen layout')?>">
              <table><tr><td><i alt="1" class="fa fa-desktop fa-1x" aria-hidden="true"></td><td><i alt="2" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr><tr><td><i alt="3" class="fa fa-desktop fa-1x" aria-hidden="true"></td><td><i alt="4" class="fa fa-desktop fa-1x" aria-hidden="true"></td></tr></table>
            </button>
          </div></td>
        </tr>
    </table>

   <!-- Tabbed View Test -->
   <!-- saf: https://stackoverflow.com/questions/1027663/how-do-i-make-a-tabbed-view-in-html -->
   <script type="text/javascript">

      function activateTab(tabId) {
          var tabCtrl = document.getElementById('tabCtrl');
          var pageToActivate = document.getElementById(tabId);
          for (var i = 0; i < tabCtrl.childNodes.length; i++) {
              var node = tabCtrl.childNodes[i];
              if (node.nodeType == 1) { /* Element */
                  node.style.display = (node == pageToActivate) ? 'block' : 'none';
              }
          }
      }

    </script>

   <div id="tabcontainer_heading"><?=__('Actions')?></div>
   <div id="tabcontainer">
   <ul>
      <li>
        <a href="javascript:activateTab('tab1')"><?=__('Share')?></a>
      </li>
      <li>
        <a href="javascript:activateTab('tab2')"><?=__('Upload')?></a>
      </li>
      <li>
        <a href="javascript:activateTab('tab3')"><?=__('URL')?></a>
      </li>
    </ul>
    <div id="tabCtrl">
      <div id="tab1" style="display: block;">
          <div class="description">
            <?=__('Share the desktop of your notebook with others. PalMA uses VNC for screen sharing. Simply download the software by clicking the button below.')?>
          </div>
          <div id="vnc-download">

          <script>

            function getOS() {

                var OSName="Unknown OS";

                if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
                if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
                if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
                if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

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
                    default: download = null;
                }

                document.getElementById(download).click();

            }

            </script>

            <?php
                /*
                 * path for local test cases
                 * 2do: must be removed before release
                 *
                 * $winvnc = "http://localhost/projects/palma-github/theme/demo/simple/winvnc-palma.exe";
                 */
            ?>

            <br />

            <div id="vnc-button" onclick="javascript:getFilePathByOS()">
                <div id="vnc-button-eye"><i class="fa fa-eye fa-3x" aria-hidden="true"></i> </div>

                <div id="vnc-button-container">
                    <div id="vnc-button-label">Download VNC</div>
                    <div id="vnc-button-label-subtext">screensharing for win / mac os</div>
                </div>

                <a href="<?php echo $winvnc; ?>" download id="download-winvnc" hidden></a>
                <a href="<?php echo $macvnc; ?>" download id="download-macvnc" hidden></a>
                <a href="<?php echo $linuxsh; ?>" download id="download-linux" hidden></a>

            </div>

          </div>
          <br />
          <div class="description">
          <?=__('Linux users can also use the built in function of their device and share the X display like this: ')?>
          </div>
          <code>x11vnc -connect <?php echo $_SERVER['HTTP_HOST'] ?></code>
      </div>
      <div id="tab2" style="display: none;">
          <div class="description">
            <?=__('Just upload PDF files, images, OpenDocument or MS Office files – PalMA will show them.')?>
          </div><br />
          <div id="file_upload">
            <form action="upload.php"
                        class="dropzone"
                        id="palma-dropzone"
                        title="<?=__('Drop documents here (or click) to load them up')?>">
                      <div class="dz-default dz-message">
                          <i class="fa fa-upload fa-2x"></i>
                          <div>
                              <?=__('Drop documents here (or click)')?>
                          </div>
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
      <div id="tab3" style="display: none;">
      <div class="description">
        <?=__('Just enter a URL – PalMA will show it.')?>
      </div>
      <br />
      <div id="url_doc">
            <input type="text" value="<?=__('Enter URL')?>"
                   id="url_field" maxlength="256" size="46"
                   onkeydown="if (event.keyCode == 13) document.getElementById('url_button').click()"
                   onfocus="clearURLField('<?=__('Enter URL')?>')">
            <button class="pure-button pure-button-primary pure-input-rounded"
                    id="url_button"
                    onClick="urlToNuc()" title="<?=__('Show this URL in a new browser window')?>">
                 URL
                    <i class="fa fa-folder-open"></i>
             </button>
      </div>
    </div>


  </div>

</div> <!-- workbench_left -->
<div id="workbench_end"></div>

<div id="helpwindow">
    <div id="helpcontainer">
        <div id="helpclose" onclick="showHelp(false)">
            <i class="fa fa-times" title="<?=__('Close the help window')?>"></i>
        </div>
        <h3>
            <?=__('Help + Extras')?>
        </h3>
        <div>
            <p><?=__('With PalMA, you can share your documents and your desktop
            with your learning group.')?></p>
            <p><?=__('Team members can join the group at any time. All they need
            is <b>URL</b> and <b>PIN</b>.')?><br />
            <h4>URL: <?=$_SESSION['starturl']?></h4>
            <h4>PIN: <?=$_SESSION['pin']?></h4>
            <p><?=__('The PalMA team monitor shows up to 4 contributions
            simultaneously.')?></p>
            <p><?=__('Just upload PDF files, images, OpenDocument or
            MS Office files or enter a URL &ndash; PalMA will show them.')?></p>
            <p><?=__('Share the desktop of your notebook with others. PalMA uses
            VNC for screen sharing. Download the VNC software once for your
            <a href="http://www.bib.uni-mannheim.de/fileadmin/pdf/ub/LearningCenter/palma-kurzanleitung.pdf"
            onclick="window.open(this.href); return false;">Windows PC</a>
            (preconfigured UltraVNC) or
            <a href="http://www.bib.uni-mannheim.de/fileadmin/pdf/ub/LearningCenter/palma-anleitung-mac.pdf"
            onclick="window.open(this.href); return false;">MacBook</a>.')?></p>
            <p><?=__('Linux users can share their X display like this:')?><br>
            <code>x11vnc -connect <?=$_SERVER['HTTP_HOST']?></code></p>
        </div>

<!-- testing table layout for buttons to have a better overview -->
<!-- may be removed again, if not necessary -->

<table align="center">
<tr>
<td>
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('<?=$winvnc?>'); return false;">
        <?=__('VNC Viewer for Windows')?>
        <i class="fa fa-download"></i>
    </button>
</td>
<td>
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('<?=$macvnc?>'); return false;">
        <?=__('Viewer for MacOS')?>
        <i class="fa fa-download"></i>
    </button>
</td>
</tr>
</table>

<!-- was already commented out -->
<!--
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('vncviewer.php'); return false;">
        <?=__('Show team display')?>
    </button>
-->
<!-- TODO test code, remove for production. -->
<!--
    <button
        class="pure-button pure-button-primary pure-input-rounded"
        onclick="window.open('phpinfo.php'); return false;">
        Test
    </button>
-->
    </div>
</div>


<div id="contactwindow">
    <div id="contactcontainer">
        <div id="contactclose" onclick="showContact(false)">
            <i class="fa fa-times" title="<?=__('Close the contact window')?>"></i>
        </div>
        <h3>
            <?=__('Provide Feedback')?>
        </h3>
        <div>
            <p><?=__('Please let us know, if something went wrong. Perhaps you have some good ideas as well? Of course we want to know ... Help us to improve PalMA by sending crash reports or contributing on Github.<br />Thank you.')?></p>

            <form id="contact" action="index.php" method="post" >
              <div class="container">
                <div class="head">
                  <h2><?=__('Tell us what you think')?></h2>
                </div>
                <input type="text" name="name" placeholder="Name" /><br />
                <input type="email" name="email" placeholder="Email" /><br />
                <textarea type="text" name="message" placeholder="Message"></textarea><br />
                <div class="message" hidden>Message Sent</div>
            </div>

                <button id="submit" type="submit"
                    class="pure-button pure-button-primary pure-input-rounded"
                    onclick="alert('Send')">
                    <?=__('Send')?>
                    <i class="fa fa-download"></i>
                </button>

                </div>
            </form>

    </div>

</div>


<div id="recommendwindow">
    <div id="recommendcontainer">
        <div id="recommendclose" onclick="showRecommendation(false)">
            <i class="fa fa-times" title="<?=__('Close the recommendation window')?>"></i>
        </div>
        <h3>
            <?=__('Recommend us')?>
        </h3>
        <div>
            <p><?=__('If you want to recommend us and share PalMA on common social networks or send your link by using one of the following communities, feel free to do so.<br />Enjoy PalMA!')?></p>


<!-- Social Media Buttons from http://sharingbuttons.io/ -->

<!-- 2do: source out css -->
<style>
.resp-sharing-button__link,
.resp-sharing-button__icon {
  display: inline-block
}

.resp-sharing-button__link {
  text-decoration: none;
  color: #fff;
  margin: 0.5em
}

.resp-sharing-button {
  border-radius: 5px;
  transition: 25ms ease-out;
  padding: 0.5em 0.75em;
  font-family: Helvetica Neue,Helvetica,Arial,sans-serif
}

.resp-sharing-button__icon svg {
  width: 1em;
  height: 1em;
  margin-right: 0.4em;
  vertical-align: top
}

.resp-sharing-button--small svg {
  margin: 0;
  vertical-align: middle
}

/* Non solid icons get a stroke */
.resp-sharing-button__icon {
  stroke: #fff;
  fill: none
}

/* Solid icons get a fill */
.resp-sharing-button__icon--solid,
.resp-sharing-button__icon--solidcircle {
  fill: #fff;
  stroke: none
}

.resp-sharing-button--twitter {
  background-color: #55acee
}

.resp-sharing-button--twitter:hover {
  background-color: #2795e9
}

.resp-sharing-button--pinterest {
  background-color: #bd081c
}

.resp-sharing-button--pinterest:hover {
  background-color: #8c0615
}

.resp-sharing-button--facebook {
  background-color: #3b5998
}

.resp-sharing-button--facebook:hover {
  background-color: #2d4373
}

.resp-sharing-button--tumblr {
  background-color: #35465C
}

.resp-sharing-button--tumblr:hover {
  background-color: #222d3c
}

.resp-sharing-button--reddit {
  background-color: #5f99cf
}

.resp-sharing-button--reddit:hover {
  background-color: #3a80c1
}

.resp-sharing-button--google {
  background-color: #dd4b39
}

.resp-sharing-button--google:hover {
  background-color: #c23321
}

.resp-sharing-button--linkedin {
  background-color: #0077b5
}

.resp-sharing-button--linkedin:hover {
  background-color: #046293
}

.resp-sharing-button--email {
  background-color: #777
}

.resp-sharing-button--email:hover {
  background-color: #5e5e5e
}

.resp-sharing-button--xing {
  background-color: #1a7576
}

.resp-sharing-button--xing:hover {
  background-color: #114c4c
}

.resp-sharing-button--whatsapp {
  background-color: #25D366
}

.resp-sharing-button--whatsapp:hover {
  background-color: #1da851
}

.resp-sharing-button--hackernews {
background-color: #FF6600
}
.resp-sharing-button--hackernews:hover, .resp-sharing-button--hackernews:focus {   background-color: #FB6200 }

.resp-sharing-button--vk {
  background-color: #507299
}

.resp-sharing-button--vk:hover {
  background-color: #43648c
}

.resp-sharing-button--facebook {
  background-color: #3b5998;
  border-color: #3b5998;
}

.resp-sharing-button--facebook:hover,
.resp-sharing-button--facebook:active {
  background-color: #2d4373;
  border-color: #2d4373;
}

.resp-sharing-button--twitter {
  background-color: #55acee;
  border-color: #55acee;
}

.resp-sharing-button--twitter:hover,
.resp-sharing-button--twitter:active {
  background-color: #2795e9;
  border-color: #2795e9;
}

.resp-sharing-button--google {
  background-color: #dd4b39;
  border-color: #dd4b39;
}

.resp-sharing-button--google:hover,
.resp-sharing-button--google:active {
  background-color: #c23321;
  border-color: #c23321;
}

.resp-sharing-button--email {
  background-color: #777777;
  border-color: #777777;
}

.resp-sharing-button--email:hover,
.resp-sharing-button--email:active {
  background-color: #5e5e5e;
  border-color: #5e5e5e;
}

.resp-sharing-button--whatsapp {
  background-color: #25D366;
  border-color: #25D366;
}

.resp-sharing-button--whatsapp:hover,
.resp-sharing-button--whatsapp:active {
  background-color: #1DA851;
  border-color: #1DA851;
}

.resp-sharing-button--telegram {
  background-color: #54A9EB;
}

.resp-sharing-button--telegram:hover {
  background-color: #4B97D1;}


</style>

<!-- 2do: load hrefs dynamically, url as php var -->

<!-- Sharingbutton Facebook -->
<a class="resp-sharing-button__link" href="https://facebook.com/sharer/sharer.php?u=https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_blank" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--facebook resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
    </div>
  </div>
</a>

<!-- Sharingbutton Twitter -->
<a class="resp-sharing-button__link" href="https://twitter.com/intent/tweet/?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;url=https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_blank" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--twitter resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M23.44 4.83c-.8.37-1.5.38-2.22.02.93-.56.98-.96 1.32-2.02-.88.52-1.86.9-2.9 1.1-.82-.88-2-1.43-3.3-1.43-2.5 0-4.55 2.04-4.55 4.54 0 .36.03.7.1 1.04-3.77-.2-7.12-2-9.36-4.75-.4.67-.6 1.45-.6 2.3 0 1.56.8 2.95 2 3.77-.74-.03-1.44-.23-2.05-.57v.06c0 2.2 1.56 4.03 3.64 4.44-.67.2-1.37.2-2.06.08.58 1.8 2.26 3.12 4.25 3.16C5.78 18.1 3.37 18.74 1 18.46c2 1.3 4.4 2.04 6.97 2.04 8.35 0 12.92-6.92 12.92-12.93 0-.2 0-.4-.02-.6.9-.63 1.96-1.22 2.56-2.14z"/></svg>
    </div>
  </div>
</a>

<!-- Sharingbutton Google+ -->
<a class="resp-sharing-button__link" href="https://plus.google.com/share?url=https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_blank" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--google resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11.37 12.93c-.73-.52-1.4-1.27-1.4-1.5 0-.43.03-.63.98-1.37 1.23-.97 1.9-2.23 1.9-3.57 0-1.22-.36-2.3-1-3.05h.5c.1 0 .2-.04.28-.1l1.36-.98c.16-.12.23-.34.17-.54-.07-.2-.25-.33-.46-.33H7.6c-.66 0-1.34.12-2 .35-2.23.76-3.78 2.66-3.78 4.6 0 2.76 2.13 4.85 5 4.9-.07.23-.1.45-.1.66 0 .43.1.83.33 1.22h-.08c-2.72 0-5.17 1.34-6.1 3.32-.25.52-.37 1.04-.37 1.56 0 .5.13.98.38 1.44.6 1.04 1.84 1.86 3.55 2.28.87.23 1.82.34 2.8.34.88 0 1.7-.1 2.5-.34 2.4-.7 3.97-2.48 3.97-4.54 0-1.97-.63-3.15-2.33-4.35zm-7.7 4.5c0-1.42 1.8-2.68 3.9-2.68h.05c.45 0 .9.07 1.3.2l.42.28c.96.66 1.6 1.1 1.77 1.8.05.16.07.33.07.5 0 1.8-1.33 2.7-3.96 2.7-1.98 0-3.54-1.23-3.54-2.8zM5.54 3.9c.33-.38.75-.58 1.23-.58h.05c1.35.05 2.64 1.55 2.88 3.35.14 1.02-.08 1.97-.6 2.55-.32.37-.74.56-1.23.56h-.03c-1.32-.04-2.63-1.6-2.87-3.4-.13-1 .08-1.92.58-2.5zM23.5 9.5h-3v-3h-2v3h-3v2h3v3h2v-3h3"/></svg>
    </div>
  </div>
</a>

<!-- Sharingbutton E-Mail -->
<a class="resp-sharing-button__link" href="mailto:?subject=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;body=https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_self" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--email resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 4H2C.9 4 0 4.9 0 6v12c0 1.1.9 2 2 2h20c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM7.25 14.43l-3.5 2c-.08.05-.17.07-.25.07-.17 0-.34-.1-.43-.25-.14-.24-.06-.55.18-.68l3.5-2c.24-.14.55-.06.68.18.14.24.06.55-.18.68zm4.75.07c-.1 0-.2-.03-.27-.08l-8.5-5.5c-.23-.15-.3-.46-.15-.7.15-.22.46-.3.7-.14L12 13.4l8.23-5.32c.23-.15.54-.08.7.15.14.23.07.54-.16.7l-8.5 5.5c-.08.04-.17.07-.27.07zm8.93 1.75c-.1.16-.26.25-.43.25-.08 0-.17-.02-.25-.07l-3.5-2c-.24-.13-.32-.44-.18-.68s.44-.32.68-.18l3.5 2c.24.13.32.44.18.68z"/></svg>
    </div>
  </div>
</a>

<!-- Sharingbutton WhatsApp -->
<a class="resp-sharing-button__link" href="whatsapp://send?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.%20https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_blank" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--whatsapp resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20.1 3.9C17.9 1.7 15 .5 12 .5 5.8.5.7 5.6.7 11.9c0 2 .5 3.9 1.5 5.6L.6 23.4l6-1.6c1.6.9 3.5 1.3 5.4 1.3 6.3 0 11.4-5.1 11.4-11.4-.1-2.8-1.2-5.7-3.3-7.8zM12 21.4c-1.7 0-3.3-.5-4.8-1.3l-.4-.2-3.5 1 1-3.4L4 17c-1-1.5-1.4-3.2-1.4-5.1 0-5.2 4.2-9.4 9.4-9.4 2.5 0 4.9 1 6.7 2.8 1.8 1.8 2.8 4.2 2.8 6.7-.1 5.2-4.3 9.4-9.5 9.4zm5.1-7.1c-.3-.1-1.7-.9-1.9-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.1-.2.2-.3.2-.6.1s-1.2-.5-2.3-1.4c-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6s.3-.3.4-.5c.2-.1.3-.3.4-.5.1-.2 0-.4 0-.5C10 9 9.3 7.6 9 7c-.1-.4-.4-.3-.5-.3h-.6s-.4.1-.7.3c-.3.3-1 1-1 2.4s1 2.8 1.1 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.3.2-.7.2-1.2.2-1.3-.1-.3-.3-.4-.6-.5z"/></svg>
    </div>
  </div>
</a>

<!-- Sharingbutton Telegram -->
<a class="resp-sharing-button__link" href="https://telegram.me/share/url?text=Do%20you%20already%20know%20PalMA?%20Take%20a%20Look.&amp;url=https%3A%2F%2Fgithub.com/UB-Mannheim/PalMA/blob/master/README.md" target="_blank" aria-label="">
  <div class="resp-sharing-button resp-sharing-button--telegram resp-sharing-button--small"><div aria-hidden="true" class="resp-sharing-button__icon resp-sharing-button__icon--solid">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M.707 8.475C.275 8.64 0 9.508 0 9.508s.284.867.718 1.03l5.09 1.897 1.986 6.38a1.102 1.102 0 0 0 1.75.527l2.96-2.41a.405.405 0 0 1 .494-.013l5.34 3.87a1.1 1.1 0 0 0 1.046.135 1.1 1.1 0 0 0 .682-.803l3.91-18.795A1.102 1.102 0 0 0 22.5.075L.706 8.475z"/></svg>
    </div>
  </div>
</a>


    </div>

</div>

</body> <!-- workbench -->

</html>
