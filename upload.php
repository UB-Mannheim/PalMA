<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

function trace($text) {
    error_log("palma: $text");
}

if (file_exists('palma.ini')) {
    // Get configuration from ini file.
    $conf = parse_ini_file("palma.ini", true);
    $url = $conf['path']['control_file'];
    $targetPath = $conf['path']['upload_dir'];
} else {
    // Guess configuration from global PHP variables.
    // TODO: There is no HTTP_REFERER, basename is wrong, so ini file is still needed.
    $url = dirname($_SERVER['HTTP_REFERER']) . '/' .
           basename($_SERVER['PHP_SELF']);
    $targetPath = dirname(__FILE__) . '/uploads';
    trace("url=$url");
}

if (empty($_FILES)) {
    $error = 99;
    $filename = 'unknown';
} else {
    $error = $_FILES['file']['error'];
    $filename = $_FILES['file']['name'];
}

if (!is_dir($targetPath)) {
    /* Target directory is missing, so create it now. */
    mkdir($targetPath, 0755);
}

if ($error == UPLOAD_ERR_OK) {
    $tempFile = $_FILES['file']['tmp_name'];
    $targetFile = "$targetPath/$filename";
    trace("upload '$tempFile' to '$targetFile'");
    move_uploaded_file($tempFile, $targetFile);
} else {
    // Support localisation.
    require_once('gettext.php');

    $targetFile = "$targetPath/error.html";
    $f = fopen($targetFile, 'w');
    if ($f) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = _("This file is too large.");
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = _("Large files are not supported.");
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = _("File was only partially uploaded.");
                break;
            default:
                $message = sprintf(_("Error code %s."), $error);
                break;
        }
        fprintf($f, _("File '%s' cannot be shown.") . "<br>\n%s\n",
                $filename, $message);
        fclose($f);
    }
    $targetFile = "file:///$targetFile";
}

  // get information of application for uploaded file
  require_once ('FileHandler.class.php');
  $handler = FileHandler::getFileHandler($targetFile);

  // create window object and send to nuc

  $dt = new DateTime();
  $date = $dt->format('Y-m-d H:i:s');

    $window = array(
        "id" => "",
        "win_id" => "",
        "name" => "",
        "state" => "",
        "file" => $targetFile,
        "handler" => $handler,
        "userid" => "",
        "date" => $date
    );

    //echo "<body onLoad=\"sendToNuc('newWindow=".serialize($window)."')\" /></body>";

    $serializedWindow = serialize($window);

    $sw = urlencode($serializedWindow);
    // Get cURL resource
    $curl = curl_init();
    // Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url . '?newWindow=' . $sw,
        CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    ));
    // Send the request & save response to $resp
    $resp = curl_exec($curl);
    // Close request to clear up some resources
    curl_close($curl);

    trace("upload closed, result='$resp'");

?>
