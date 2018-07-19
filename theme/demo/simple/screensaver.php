<?php

/*

Copyright (C) 2015 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Author: Stefan Weil, Universitätsbibliothek Mannheim

*/

require_once('../../../DBConnector.class.php');

$servername = $_SERVER["SERVER_NAME"];
$serveraddress = $_SERVER["SERVER_ADDR"];
$serveruri = dirname(dirname(dirname(dirname($_SERVER["REQUEST_URI"]))));
$pin = sprintf("%04u", rand(0, 9999));
$url = "http://${servername}${serveruri}";

// Store PIN in database.
$dbcon = new palma\DBConnector();
$dbcon->exec("UPDATE setting SET value='$pin' WHERE key='pin'");

?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>PalMA &ndash; Screen Saver</title>
    <style type="text/css">
        html {
            font-family: DejaVu Sans,sans-serif;
            font-weight: 100;
            height: 100%;
            width: 100%;
            background-color: black;
            color: white;
            font-size: 250%;
        }
        td {
            padding-right: 20px;
        }
        #box {
            margin: 200px;
        }
    </style>
</head>

<body>
    <div id="box">
    <table>
        <tr>
            <td>URL:</td>
            <td><?=$url?></td>
            <td rowspan="2">
                <img id="QR-Code"
                    src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H"
                    alt="QR Code">
            </td>
        </tr>
        <tr>
            <td>PIN:</td>
            <td><?=$pin?></td>
        </tr>
    </table>
    </div>
</body>
</html>
