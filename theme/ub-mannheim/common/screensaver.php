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
$dbcon = new palma\DBConnector();
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
    font-weight: 220;
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

#QR-Code {
    position: absolute;
}
#URL {
    color: #2e668b;
    font-size: 500%;
    position: absolute;
    margin: 0 auto;
    width: 100%;
    text-align: center;
}
#PIN {
    font-size: 525%;
    color: #2e668b;
    position: absolute;
}
#Variant1, #Variant2 {
    position: relative;
}

#Variant1 #QR-Code {
    top: 57.25%;
    left: 3.75%;
}
#Variant1 #URL {
    top: 41%;
}
#Variant1 #PIN {
    right: 5.75%;
    top: 57.25%;
}

#Variant2 #QR-Code {
    top: 22%;
    left: 4%;
}
#Variant2 #URL {
    top: 6.75%;
}
#Variant2 #PIN {
    right: 5.5%;
    top: 22%;
}

</style>
</head>

<body>
    <div id="Variant1">
        <img class="screen" src="palma_d.png">
        <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
        <div id="URL"><?=$url?></div>
        <div id="PIN"><?=$pin?></div>
    </div>

    <div id="Variant2" hidden>
        <img class="screen" src="palma_e.png">
        <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
        <div id="URL"><?=$url?></div>
        <div id="PIN"><?=$pin?></div>
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
setInterval("switchImage()", 300000);

</script>
</body>
</html>
