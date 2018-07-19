<?php
/*

Librarian console for UB Mannheim Learning Center

Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Authors: Alexander Wagner, Stefan Weil, Sarah Krieg

This web application shows all PalMA stations of the Library Section A5.
The status of each station is displayed. It is also possible to reset
a station.

TODO:

* Display status of each station.

*/

// Connect to database and get configuration constants.
require_once('../../../DBConnector.class.php');
$dbcon = new palma\DBConnector();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>A5 &ndash; PalMA-Stationen</title>

<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="../../../font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="../../../pure-min.css">
<link rel="stylesheet" href="../../../palma.css" type="text/css">

<style type="text/css">
h4 {
    text-align: center;
}
#workbench {
    width: 48em;
}
#stations {
}
#stations .textdiv {
    width: 10em;
}
#stations i {
    float: right;
}
#stations button {
    margin: 0em 1em 1em 1em;
    width: 10em;
}
</style>

<script type="text/javascript">

function sendToNuc(urlBase, command) {
    var xmlHttp = new XMLHttpRequest();
    if (!xmlHttp) {
        // TODO
        alert('XMLHttpRequest failed!');
        return;
    }
    var url = urlBase + 'control.php?' + command;
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
          var status = xmlHttp.status;
          if (status == 200) {
            // Got valid response.
            var text = xmlHttp.responseText;
          } else {
            // Got error. TODO: handle it.
          }
      } else {
          alert("Got xmlHttp.readyState " + xmlHttp.readyState);
      }
    };
    xmlHttp.send(null);
}

function pollDatabase(lastJSON, urlBase, button) {
    //~ trace('polling ' + urlBase);
    var xmlHttp = new XMLHttpRequest();
    if (xmlHttp) {
        xmlHttp.open("get", urlBase + 'db.php?json=' + lastJSON, true);
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
                    var db = JSON.parse(text);
                    //~ var controls = updateControlsBySection(db.window);
                    //~ showLayout(layout, controls);
                    //~ updateUserList(db.address, db.user);
                    //~ updateWindowList(db.window);
                    trace('pollDatabase("' + text + '","' + urlBase + '","' + button +'")');
                    setTimeout('pollDatabase("' + text + '","' + urlBase + '","' + button +'")', 1);
                } else {
                    // Got error. TODO: handle it.
                    trace('poll error');
                }
            } else {
                // Got unexpected xmlHttp.readyState. TODO: handle it.
            }
        };
        xmlHttp.send(null);
    }
}

</script>

</head>

<body id="workbench">

<?php
$remote = $_SERVER['REMOTE_ADDR'];
if ($remote == '::1' || $remote == '127.0.0.1' ||
    $remote == '134.155.88.41' ||
    //Allow access for a532 - Fachauskunft
    $remote == '134.155.88.42' ||
    //Allow access for a533 - Aufsicht
    $remote == '::1' || $remote == '127.0.0.1' ||
    preg_match('/^134[.]155[.]36[.]/', $remote) && $remote != '134.155.36.48') {
    // Allow access for localhost and library staff, but not for the proxy host.
    ?>

    <h2>Übersicht A5 &ndash; PalMA-Stationen</h2>
    <h4>Station anklicken zum Rücksetzen</h4>
    <div id="stationlist">
    <div id="stations" class="pure-u">
    </div>
    </div>

<script type="text/javascript">

function resetStation(urlBase) {
    sendToNuc(urlBase, 'logout=ALL');
}

var stations = [];

stations.push(['A5 01', 'http://palma-a5-01.bib.uni-mannheim.de/']);
stations.push(['A5 02', 'http://palma-a5-02.bib.uni-mannheim.de/']);
stations.push(['A5 03', 'http://palma-a5-03.bib.uni-mannheim.de/']);
stations.push(['A5 04', 'http://palma-a5-04.bib.uni-mannheim.de/']);
stations.push(['A5 05', 'http://palma-a5-05.bib.uni-mannheim.de/']);
stations.push(['A5 06', 'http://palma-a5-06.bib.uni-mannheim.de/']);
stations.push(['A5 07', 'http://palma-a5-07.bib.uni-mannheim.de/']);
stations.push(['A5 08', 'http://palma-a5-08.bib.uni-mannheim.de/']);
stations.push(['A5 09', 'http://palma-a5-09.bib.uni-mannheim.de/']);
stations.push(['A5 10', 'http://palma-a5-10.bib.uni-mannheim.de/']);
stations.push(['A5 11', 'http://palma-a5-11.bib.uni-mannheim.de/']);
stations.push(['A5 12', 'http://palma-a5-12.bib.uni-mannheim.de/']);
stations.push(['A5 13', 'http://palma-a5-13.bib.uni-mannheim.de/']);
stations.push(['A5 14', 'http://palma-a5-14.bib.uni-mannheim.de/']);
stations.push(['A5 15', 'http://palma-a5-15.bib.uni-mannheim.de/']);
stations.push(['A5 16', 'http://palma-a5-16.bib.uni-mannheim.de/']);
stations.push(['A5 17', 'http://palma-a5-17.bib.uni-mannheim.de/']);
stations.push(['A5 18', 'http://palma-a5-18.bib.uni-mannheim.de/']);



// Get the <div> element which contains the station entries.
var list = document.getElementById('stations');

// First we remove all existing child elements.
while (list.firstChild) {
    list.removeChild(list.firstChild);
}

for (var n = 0; n < stations.length; n++) {
    var station = stations[n];
    var stationname = station[0];
    var stationurl = station[1];
    var button = document.createElement('button');
    var i = document.createElement('i');
    button.setAttribute('class', 'pure-button pure-button-primary pure-input-rounded');
    button.setAttribute('id', stationname);
    button.setAttribute('onclick', 'resetStation("' + stationurl + '")');
    i.setAttribute('class', 'fa fa-times');
    i.setAttribute('title', 'zurücksetzen');
    button.appendChild(i);
    button.appendChild(document.createTextNode(stationname + ' (' + 0 + ')'));
    list.appendChild(button);
    // TODO: Polling disabled because it retriggers the timeout of each station.
    //pollDatabase('', stationurl, button);
}

</script>

    <?= $_SERVER['REMOTE_ADDR'] ?>


    <?php
} else {
    ?>

    <h2>Übersicht A5 &ndash; PalMA-Stationen</h2>
    <h4>Sie sind nicht berechtigt für diese Funktion.</h4>

    <?php
}
?>

</body> <!-- workbench -->

</html>
