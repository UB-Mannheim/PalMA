<?php

/*
Copyright (C) 2014 Universitätsbibliothek Mannheim
See file LICENSE for license details.

Author: Stefan Weil

Todo:

* Konfiguration der Monitore, Auswahl der richtigen URL
*/

// Support localisation.
require_once('../gettext.php');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
       "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>PalMA &ndash; <?=_('Select workplace')?></title>

<link rel="stylesheet" href="../font-awesome/css/font-awesome.min.css">

<!-- @http://purecss.io/forms/ -->
<link rel="stylesheet" href="../pure-min.css">

<!-- Customized style. -->
<link rel="stylesheet" href="../palma.css">
<!--
-->

</head>

<body>

<script type="text/javascript">

function select(name, url) {
    window.open(url + '/index.php?monitor=' + name);
}

</script>

<div id="monitor_selection">

<h2>PalMA &ndash; <?=_('Select workplace')?></h2>

<div class="pure-u">
    <button class="pure-button pure-button-primary"
            onclick="select('DHC176', 'http://dhcp176.bib.uni-mannheim.de/test')">
            <i class="fa fa-desktop fa-3x"></i>DHC176 (test)</button>
    <button class="pure-button pure-button-primary"
            onclick="select('LC 02', 'http://lc02.bib.uni-mannheim.de/palma')">
            <i class="fa fa-desktop fa-3x"></i>LC 02 (palma)</button>
    <button class="pure-button pure-button-primary"
            onclick="select('LC 02', 'http://lc02.bib.uni-mannheim.de/test')">
            <i class="fa fa-desktop fa-3x"></i>LC 02 (test)</button>
    <button class="pure-button pure-button-primary"
            disabled
            onclick="">
            <i class="fa fa-desktop fa-3x"></i><?=_('Mannheim table')?> 1</button>
    <button class="pure-button pure-button-primary"
            disabled
            onclick="">
            <i class="fa fa-desktop fa-3x"></i>Leselounge 2</button>
    <button class="pure-button pure-button-primary"
            onclick="select('Testmonitor', '..')">
            <i class="fa fa-desktop fa-3x"></i><?=_('Test monitor')?></button>
    <button class="pure-button pure-button-primary"
            onclick="select('EDV32', 'http://edv32/~stefan/nuc')">
            <i class="fa fa-desktop fa-3x"></i>EDV32</button>
    <button class="pure-button pure-button-primary"
            disabled
            onclick="select('LC 09', 'http://lc09.bib.uni-mannheim.de/test')">
            <i class="fa fa-desktop fa-3x"></i>LC 09</button>
</div>
</div>

</body>
</html>
