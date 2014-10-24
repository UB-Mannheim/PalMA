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
#Variant3 {
    color: white;
}
#Variant3 #QR-Code {
    top: 30%;
}
#Variant3 #URL {
    left: 14%;
    top: 32%;
}
#Variant4 #QR-Code {
    top: 58%;
}
#Variant4 #URL {
    font-size: 250%;
    top: 64%;
}
#Variant5 #QR-Code {
    right: 12%;
    top: 25%;
}
#Variant5 #URL {
    color: white;
    font-size: 250%;
    left: 14%;
    top: 35%;
}
#PIN {
    background-color: white;
    border-radius: 6px;
    font-size: 200%;
    position: absolute;
    left: 0px;
    top: 0px;
    z-index: 2;
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
    <div id="PIN">PIN: <?=$pin?></div>

    <div id="Variant1">
    <img class="screen" src="palma_d.png">
    <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
    <div id="URL"><?=$url?></div>
    </div>

    <div id="Variant2" hidden>
    <img class="screen" src="palma_e.png">
    <img id="QR-Code" src="../../../qrcode/php/qr_img.php?d=<?=$url?>?pin=<?=$pin?>&amp;e=H" alt="QR Code">
    <div id="URL"><?=$url?></div>
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

//Random Movement - http://www.btinternet.com/~kurt.grigg/javascript

if  ((document.getElementById) &&
window.addEventListener || window.attachEvent){

(function(){

var imgh = 163;
var imgw = 156;
var timer = 40; // SetTimeout speed.
var min = 0.2;  // Slowest speed.
var max = 1.0;  // Fastest speed.

var h,w,r,temp;
var d = document;
var y = 2;
var x = 2;
var dir = 45;   //direction.
var acc = 1;    //acceleration.
var newacc = new Array(1,0,1);
var vel = 1;    //initial speed.
var sev = 0;
var newsev = new Array(1,-1,2,-2,0,0,1,-1,2,-2);

//counters.
var c1 = 0;    //time between changes.
var c2 = 0;    //new time between changes.

var pix = "px";
var domWw = (typeof window.innerWidth == "number");
var domSy = (typeof window.pageYOffset == "number");

if (domWw) r = window;
else{
  if (d.documentElement &&
  typeof d.documentElement.clientWidth == "number" &&
  d.documentElement.clientWidth != 0)
  r = d.documentElement;
 else{
  if (d.body &&
  typeof d.body.clientWidth == "number")
  r = d.body;
 }
}



function winsize(){
var oh,sy,ow,sx,rh,rw;
if (domWw){
  if (d.documentElement && d.defaultView &&
  typeof d.defaultView.scrollMaxY == "number"){
  oh = d.documentElement.offsetHeight;
  sy = d.defaultView.scrollMaxY;
  ow = d.documentElement.offsetWidth;
  sx = d.defaultView.scrollMaxX;
  rh = oh-sy;
  rw = ow-sx;
 }
 else{
  rh = r.innerHeight;
  rw = r.innerWidth;
 }
h = rh - imgh;
w = rw - imgw;
}
else{
h = r.clientHeight - imgh;
w = r.clientWidth - imgw;
}
}


function scrl(yx){
var y,x;
if (domSy){
 y = r.pageYOffset;
 x = r.pageXOffset;
 }
else{
 y = r.scrollTop;
 x = r.scrollLeft;
 }
return (yx == 0)?y:x;
}


function newpath(){
sev = newsev[Math.floor(Math.random()*newsev.length)];
acc = newacc[Math.floor(Math.random()*newacc.length)];
c2 = Math.floor(20+Math.random()*50);
}


function moveit(){
var vb,hb,dy,dx,curr;
if (acc == 1) vel +=0.05;
if (acc == 0) vel -=0.05;
if (vel >= max) vel = max;
if (vel <= min) vel = min;
c1++;
if (c1 >= c2){
 newpath();
 c1=0;
}
curr = dir+=sev;
dy = vel * Math.sin(curr*Math.PI/180);
dx = vel * Math.cos(curr*Math.PI/180);
y+=dy;
x+=dx;
//horizontal-vertical bounce.
vb = 180-dir;
hb = 0-dir;
//Corner rebounds?
if ((y < 1) && (x < 1)){y = 1; x = 1; dir = 45;}
if ((y < 1) && (x > w)){y = 1; x = w; dir = 135;}
if ((y > h) && (x < 1)){y = h; x = 1; dir = 315;}
if ((y > h) && (x > w)){y = h; x = w; dir = 225;}
//edge rebounds.
if (y < 1) {y = 1; dir = hb;}
if (y > h) {y = h; dir = hb;}
if (x < 1) {x = 1; dir = vb;}
if (x > w) {x = w; dir = vb;}

//Assign it all to image.
temp.style.top = y + scrl(0) + pix;
temp.style.left = x + scrl(1) + pix;
setTimeout(moveit,timer);
}

function init(){
temp = document.getElementById("PIN");
winsize();
moveit();
}


if (window.addEventListener){
 window.addEventListener("resize",winsize,false);
 window.addEventListener("load",init,false);
}
else if (window.attachEvent){
 window.attachEvent("onresize",winsize);
 window.attachEvent("onload",init);
}

})();
}//End.

</script>
</body>
</html>
