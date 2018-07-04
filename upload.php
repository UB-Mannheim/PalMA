<?php

// Copyright (C) 2014-2015 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

require_once('globals.php');

if (empty($_FILES)) {
    $error = 99;
    $filename = 'unknown';
} else {
    $error = $_FILES['file']['error'];
    $filename = $_FILES['file']['name'];
}

if (!is_dir(CONFIG_UPLOAD_DIR)) {
    /* Target directory is missing, so create it now. */
    mkdir(CONFIG_UPLOAD_DIR, 0755);
}

if ($error == UPLOAD_ERR_OK || "downloaded_from_url") {
    # All uploaded files are collected in the upload directory.
    # If necessary, an index is added to get a unique filename.
    $tempFile = $_FILES['file']['tmp_name'];
    $targetFile = CONFIG_UPLOAD_DIR . "/$filename";
    $index = 0;
    $fparts = pathinfo($filename);
    $fname = $fparts['filename'];
    $ftype = null;
    if (isset($fparts['extension'])) {
        $ftype = $fparts['extension'];
    }
    while (file_exists($targetFile)) {
        $index++;
        if ($ftype) {
            $targetFile = CONFIG_UPLOAD_DIR . "/$fname-$index.$ftype";
        } else {
            $targetFile = CONFIG_UPLOAD_DIR . "/$fname-$index";
        }
    }
    trace("upload '$tempFile' to '$targetFile'");
    if (is_uploaded_file($tempFile)) {
        move_uploaded_file($tempFile, $targetFile);
    } elseif ($error == "downloaded_from_url") {
        rename($tempFile, $targetFile);
    } else {
        trace("upload failed!");
    }
} else {
    // Support localisation.
    require_once('i12n.php');

    $targetFile = CONFIG_UPLOAD_DIR . "/error.html";
    $f = fopen($targetFile, 'w');
    if ($f) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = __("This file is too large.");
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = __("Large files are not supported.");
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = __("File was only partially uploaded.");
                break;
            default:
                $message = sprintf(__("Error code %s."), $error);
                break;
        }
        fprintf($f, "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\"");
        fprintf($f, "\"http://www.w3.org/TR/html4/strict.dtd\">");
        fprintf($f, "<html>\n");
        fprintf($f, "<head>\n");
        fprintf($f, "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n");
        fprintf($f, "<title>Error</title>\n");
        fprintf($f, "</head>\n");
        fprintf($f, "<body>\n");
        fprintf($f, "<p>\n");
        fprintf(
            $f,
            __("File '%s' cannot be shown.") . "<br>\n%s\n",
            $filename,
            $message
        );
        fprintf($f, "</p>\n");
        fprintf($f, "</body>\n");
        fprintf($f, "</html>\n");
        fclose($f);
    }
    $targetFile = "file:///$targetFile";
}

// create window object and send to nuc

$dt = new DateTime();
$date = $dt->format('Y-m-d H:i:s');

$window = array(
    "id" => "",
    "win_id" => "",
    "name" => "",
    "state" => "",
    "file" => $targetFile,
    "userid" => "",
    "date" => $date);

//echo "<body onLoad=\"sendToNuc('newWindow=".serialize($window)."')\" /></body>";

$serializedWindow = serialize($window);

$sw = urlencode($serializedWindow);
// Get cURL resource
$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
                      CURLOPT_RETURNTRANSFER => 1,
                      CURLOPT_URL => CONFIG_CONTROL_FILE . '?newWindow=' . $sw,
                      CURLOPT_USERAGENT => 'PalMA cURL Request'
                               ));
// Send the request & save response to $resp
$resp = curl_exec($curl);
// Close request to clear up some resources
curl_close($curl);

trace("upload closed, result='$resp'");
