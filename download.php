<?php
// Copyright (C) 2014-2016 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// This action requires an authorized user.
require_once('auth.php');

// a valid request has to contain a file to be downloaded
if (!isset($_GET['file']) || empty($_GET['file'])) {
    header('Bad Request', true, 400);
    exit();
}

// avoid directory traversal vulnerability
$filename = basename($_GET['file']);

require_once('globals.php');
$filepath = CONFIG_UPLOAD_DIR . '/' . $filename;

if (file_exists($filepath)) {
    // file exists: return file for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' .
           addslashes($filename) . '"');
    readfile($filepath);
} else {
    // file does not exist: 404 Not Found
    header('Not Found', true, 404);
    exit();
}
