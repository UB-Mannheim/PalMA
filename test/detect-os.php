<!-- Copyright (C) 2017 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Authors:    Alexander Wagner
Descr.:     Concept for new (VNC) Download Button
Type:       HTML Template
Status:     Testing

Branch:     testing

-->
<html lang="de">
<head>

  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <title>VNC Download Template</title>

  <link rel="stylesheet" href="../font-awesome/css/font-awesome.min.css">

    <style>

      #vnc-button { border:1px solid #990000; padding:10px; border-radius: 7px; width:260px; height:50px; color: #990000; cursor:pointer; cursor: hand; }
      #vnc-button-eye { float:left; }
      #vnc-button-container { float:left; margin-left: 5px; }
      #vnc-button-label { font-size: 25px; font-weight:bold; font-family: Tahoma, Sans-Serif; }
      #vnc-button-label-subtext { font-size: 12px; font-family: Tahoma, Sans-Serif; }

    </style>

    <script>

    function getOS() {

        var OSName="Unknown OS";

        if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
        if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
        if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
        if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

        return OSName;
    }

    // function getFilePathByOS(themepath) {
    function getFilePathByOS() {

        var OSName = getOS();

        // alert(OSName);

        // var fileWindows = 'winvnc-palma.exe';
        // var fileMacOS = 'VineServer.dmg.dmg'

        var fileWindows = 'download-winvnc';
        var fileMacOS = 'download-macvnc';
        var fileLinux = 'x11.sh';

        var file = '';

        switch(OSName) {
            case 'Windows': file = fileWindows;
                break;
            case 'MacOS': file = fileMacOS;
                break;
            case 'Linux': file = fileLinux;
                break;
            case 'UNIX': file = fileLinux;
                break;
            default: file = null;
        }

        // With Path Parameter
        // alert("VNC Download: " + themepath+file);

        document.getElementById(file).click();

        return file;

    }

    /*
    // http://learning04.bib.uni-mannheim.de//theme/ub-mannheim/lc/winvnc-palma.exe
    // http://realvnc.com/download/vnc/
    */

    </script>

    </head>

<body>

<div id="example">
    <script type="text/javascript">

    // Simple OS Recognition
    var OSName="Unknown OS";
    if (navigator.appVersion.indexOf("Win")!=-1) OSName="Windows";
    if (navigator.appVersion.indexOf("Mac")!=-1) OSName="MacOS";
    if (navigator.appVersion.indexOf("X11")!=-1) OSName="UNIX";
    if (navigator.appVersion.indexOf("Linux")!=-1) OSName="Linux";

    // Common Values of User-System: Browser, OS, ...

    txt = "<div id='verbose' style='font-size: 12px; color: #333333; font-family: Courier; display: none;'>";
    txt+= " -DETAILED INFORMATION -<br />";
    txt+= "<p>Browser CodeName: " + navigator.appCodeName + "</p>";
    txt+= "<p>Browser Name: " + navigator.appName + "</p>";
    txt+= "<p>Browser Version: " + navigator.appVersion + "</p>";
    txt+= "<p>Cookies Enabled: " + navigator.cookieEnabled + "</p>";
    txt+= "<p>Platform: " + navigator.platform + "</p>";
    txt+= "<p>User-agent header: " + navigator.userAgent + "</p>";
    txt+= " -SIMPLE INFORMATION- <br />";
    txt+= "<p>Your OS: " + OSName + "</p>";
    txt+= "</div>";
    txt+= "<br />";

    document.getElementById("example").innerHTML=txt;
    </script>
</div>

<?php
    // With Path Parameter
    $theme = CONFIG_START_URL . "/theme/" . CONFIG_THEME . "/";

    // Already exisiting in index.php
    $winvnc = CONFIG_START_URL . "/theme/" . CONFIG_THEME . "/winvnc-palma.exe";
    $macvnc = CONFIG_START_URL . "/theme/" . CONFIG_THEME . "/VineServer.dmg";
    // Test Cases
    $winvnc = "http://localhost/projects/palma-github/theme/demo/simple/winvnc-palma.exe";
?>

<!-- div id="vnc-button" onclick="javascript:getFilePathByOS('<?php echo $theme; ?>')" -->
<div id="vnc-button" onclick="javascript:getFilePathByOS()">
    <!-- local test with img -->
    <!-- img src="eye.png" width="50" / -->
    <div id="vnc-button-eye"><i class="fa fa-eye fa-3x" aria-hidden="true"></i> </div>

    <div id="vnc-button-container">
        <div id="vnc-button-label">Download VNC</div>
        <div id="vnc-button-label-subtext">screensharing for win / mac os</div>
    </div>

    <a href="<?php echo $winvnc; ?>" download id="download-winvnc" hidden></a>
    <a href="<?php echo $macvnc; ?>" download id="download-macvnc" hidden></a>

</div>

</body>
</html>
