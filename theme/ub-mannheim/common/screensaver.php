<?php

/*

Copyright (C) 2014 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Author: Stefan Weil, Universitätsbibliothek Mannheim

*/

$servername = $_SERVER["SERVER_NAME"];
$serveraddress = $_SERVER["SERVER_ADDR"];
$serveruri = dirname(dirname(dirname(dirname($_SERVER["REQUEST_URI"]))));
$pin = sprintf("%04u", rand(0, 9999));
$url = "http://${servername}${serveruri}";

// Store PIN in database.
require_once('../../../DBConnector.class.php');
$dbcon = new DBConnector();
$dbcon->exec("UPDATE setting SET value='$pin' WHERE key='pin'");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html lang="de">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA &ndash; Screen Saver</title>
<script type="text/javascript" src="/javascript/jquery/jquery.js"></script>
<style type="text/css">
* {
    margin: 0;
    padding: 0;
}

html {
    font-family: DejaVu Sans,sans-serif;
    font-weight: 100;
    height: 100%;
    width: 100%;
}
body {
    background-color: black;
}
h1, p {
    color: white;
}
.screen {
    width: 1920px;
    height: 1075px;
    overflow: hidden;
}
#Variant1 #QR-Code {
    top: 63%;
}
#Variant1 #URL {
    color: white;
    top: 70%;
}
#Variant2 #QR-Code {
    top: 22%;
}
#Variant2 #URL {
    color: white;
    left: 13.5%;
    top: 28%;
}
#QR-Code {
    position: absolute;
    right: 15%;
    top: 55%;
}
#URL {
    font-size: 250%;
    position: absolute;
    left: 17%;
    bottom: 35%;
}
</style>
</head>

<body>
    <div id="Variant1">
    <img class="screen" src="palma_d.png">
    <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
    <div id="URL"><?=$url?> (PIN: <?=$pin?>)</div>
    </div>

    <div id="Variant2" hidden>
    <img class="screen" src="palma_e.png">
    <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
    <div id="URL"><?=$url?> (PIN: <?=$pin?>)</div>
    </div>

<script type="text/javascript">

var variant = 1;

function switchImage() {
    element1 = document.getElementById('Variant1');
    element2 = document.getElementById('Variant2');
    element1.setAttribute('hidden', '');
    element2.setAttribute('hidden', '');
    switch (variant) {
    case 1:
        element1.removeAttribute('hidden');
        variant = 2;
        break;
    case 2:
        element2.removeAttribute('hidden');
        variant = 1;
        break;
    }
}

switchImage();
setInterval("switchImage()", 600000);

</script>
</body>
</html>
