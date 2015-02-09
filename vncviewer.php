<?php
// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// This is an experimental remote access to the PalMA team monitor.

$url = 'http://test.bib.uni-mannheim.de/guacamole/';

include('auth.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <title>Redirect</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="refresh" content="0; URL=<?=$url?>client.xhtml?id=c/vnc-0">
  </head>
  <body>
    <h1>Redirect</h1>
    <p>
      <a href="<?=$url?>"><?=$url?></a>
    </p>
  </body>
</html>
