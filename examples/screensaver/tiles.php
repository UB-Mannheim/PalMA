<?php

/*

Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Author: Stefan Weil, Universitätsbibliothek Mannheim

References:
http://responsiveslides.com/
http://unslider.com/
http://www.java2s.com/Code/JavaScript/GUI-Components/AnimationRandomMovement.htm
http://www.tutorialspoint.com/javascript/javascript_animation.htm

*/

$pin = sprintf("%04u", rand(0, 9999));

// Store PIN in database.
require_once '../../DBConnector.class.php';
$dbcon = palma\DBConnector::getInstance();
$dbcon->exec("UPDATE setting SET value='$pin' WHERE key='pin'");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html lang="de">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA &ndash; <?=__('Tiles')?></title>
<script type="text/javascript" src="/jquery.min.js"></script>
<style type="text/css">
* {
    //~ margin: 0;
    //~ padding: 0;
}

html {
    font-family: DejaVu Sans,arial,liberation-sans,sans-serif;
    font-weight: 100;
    font-size: 400%;
    height: 100%;
}
img {
    position: absolute;
    right: 40px;
    top: 140px;
    //~ z-index: 2;
}
td {
    background-color: black;
    border: 5px solid;
    color: white;
    width: 250px;
    height: 250px;
    text-align: center;
}
#LeftSide {
    float: left;
}
#RightSide {
    float: right;
    font-size: 30%;
}
#Pin {
    font-size: 200%;
}
#LearningCenter {
    font-size: 180%;
}
#Beratung {
    background-color: #41a85f;
}
#Gruppenarbeit {
    background-color: #00a885;
}
#Smartboards {
    background-color: #3d8eb9;
}
#Scannen {
    background-color: #493db9;
}
#Lernen {
    background-color: #475677;
}
#Sessions {
    background-color: #8f44ad;
}
#Technikleihe {
    background-color: #b8312e;
}
#Relaxen {
    background-color: #f47935;
}
#Virtualshelf {
    background-color: #faa026;
}
</style>
</head>

<body>

<div id="LeftSide">
<div id="ubmannheim">
UB Mannheim
</div>
<div id="LearningCenter">
<strong>Learning</strong>Center
</div>
<table>
<tr>
<td id="Beratung">Beratung</td>
<td id="Gruppenarbeit">Gruppen<br>arbeit</td>
<td id="Smartboards">Smart<br>boards</td>
</tr>
<tr>
<td id="Scannen">Scannen</td>
<td id="Lernen">Lernen</td>
<td id="Sessions">Sessions</td>
</tr>
<tr>
<td id="Technikleihe">Technik<br>Leihe</td>
<td id="Relaxen">Relaxen</td>
<td id="Virtualshelf">Virtual<br>Shelf</td>
</tr>
</table>
</div>

<div id="RightSide">
    <span>
    <img src="../../qrcode/php/qr_img.php?d=<?=CONFIG_START_URL?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
    <h2>maTeam &ndash; Mannheim Team Monitor / Share your desktop.</h2>
    <p>Just go to <?=CONFIG_START_URL?> and enter the PIN.</p>
    <p id="Pin">PIN: <?= $pin ?></p>
    </span>
</div>

</body>
</html>
