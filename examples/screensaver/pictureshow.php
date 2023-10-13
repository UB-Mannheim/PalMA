<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Author: Stefan Weil

// References:
// http://responsiveslides.com/
// http://unslider.com/
// http://www.java2s.com/Code/JavaScript/GUI-Components/AnimationRandomMovement.htm
// http://www.tutorialspoint.com/javascript/javascript_animation.htm

// TODO:

$servername = $_SERVER["SERVER_NAME"];
$serveraddress = $_SERVER["SERVER_ADDR"];
$serveruri = dirname($_SERVER["REQUEST_URI"]);
$pin = sprintf("%04u", rand(0, 9999));
$url = "http://${servername}${serveruri}/index.php";

// Store PIN in database.
require_once('../../DBConnector.class.php');
$dbcon = new DBConnector();
$dbcon->exec("UPDATE setting SET value='$pin' WHERE key='pin'");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html lang="de">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA &ndash; <?=__("Picture Show")?></title>
<script type="text/javascript" src="/jquery.min.js"></script>
<script type="text/javascript" src="/unslider.min.js"></script>
<style type="text/css">
* {
    margin: 0;
    padding: 0;
}

html {
    font-family: sans-serif;
    font-size: 120%;
    height: 100%;
}
li {
    height: 600px;
}
.banner {
    position: relative;
    overflow: auto;
}
.banner ul {
    list-style: none;
}
.banner ul li {
    float: left;
}
body {
    background-color: black;
}
h1, p {
    color: white;
}
#pin {
    position: absolute;
    left: 500px;
    top: 40px;
    z-index: 2;
}
img {
    position: absolute;
    right: 40px;
    top: 40px;
    z-index: 2;
}
</style>
</head>

<body>
    <div><img src="../../qrcode/php/qr_img.php?d=<?=$url?>&amp;e=H" alt="QR Code"></div>
    <div id="pin"><h1>PIN: <?=$pin?></h1></div>

  <div class="banner">
    <ul>
<?php
    //~ <ul id="bannerlist">
    $pictures = '../../pictures';
if (is_dir($pictures)) {
    $filelist = scandir($pictures);
    sort($filelist, SORT_NATURAL);
  foreach ($filelist as $file) {
      $picture = "$pictures/$file";
    if (is_file($picture)) {
        echo("<li style=\"background-image: url('$picture'); background-repeat: no-repeat\"></li>\n");
        //~ echo("<li><img src=\"$picture\"></li>\n");
    }
  }
} else {
    echo <<<EOD
        <li style="background-image: url('http://www.bib.uni-mannheim.de/typo3temp/pics/b1ad582e53.jpg');"></li>
        <li style="background-image: url('http://www.bib.uni-mannheim.de/typo3temp/pics/640e1eafcd.jpg');"></li>
        <li style="background-image: url('http://www.bib.uni-mannheim.de/typo3temp/pics/998e4505ca.jpg');"></li>
        <li style="background-image: url('http://www.bib.uni-mannheim.de/typo3temp/pics/e4843ceada.jpg');"></li>
        <li style="background-image: url('http://www.bib.uni-mannheim.de/typo3temp/pics/565d17487b.jpg');"></li>
        <li style="background-image: url('http://edz.bib.uni-mannheim.de/www-edz/images/edz2.jpg');"></li>
EOD;
}
?>
    </ul>
  </div>

    <h1>maTeam &ndash; Mannheim Team Monitor / Share your desktop.</h1>
    <p>Just go to <?=$url?> and enter the PIN.</p>

<script type="text/javascript">

$(function() {
    $('.banner').unslider({
        speed: 500,               //  The speed to animate each slide (in milliseconds)
        delay: 10000,             //  The delay between slide animations (in milliseconds)
        complete: function() {},  //  A function that gets called after every slide animation
        keys: true,               //  Enable keyboard (left, right) arrow shortcuts
        dots: true,               //  Display dot navigation
        fluid: false              //  Support responsive design. May break non-responsive designs
    });
});

//Random Movement - http://www.btinternet.com/~kurt.grigg/javascript

if  ((document.getElementById) &&
window.addEventListener || window.attachEvent){

(function(){

var imgh = 163;
var imgw = 156;
var timer = 40; //setTimeout speed.
var min = 1;    //slowest speed.
var max = 5;    //fastest speed.

var h,w,r,temp;
var d = document;
var y = 2;
var x = 2;
var dir = 45;   //direction.
var acc = 1;    //acceleration.
var newacc = [1, 0, 1];
var vel = 1;    //initial speed.
var sev = 0;
var newsev = [1, -1, 2, -2, 0, 0, 1, -1, 2, -2];

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
temp = document.getElementById("pin");
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
