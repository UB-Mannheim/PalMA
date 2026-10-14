<?php

// Copyright (C) 2014-2024 Universitätsbibliothek Mannheim
// See file LICENSE for license details.
//
// Streams received WebRTC frames as an MJPEG stream to the monitor display.
// This URL is opened by the PalMA monitor via palma-browser.

session_start();
require_once 'auth.php';

// The session ID is passed as a URL parameter so the monitor can identify
// which user's stream to display.
$sessionId = isset($_GET['sid']) ? preg_replace('/[^a-zA-Z0-9]/', '', $_GET['sid']) : '';
if (empty($sessionId)) {
    http_response_code(400);
    echo 'Missing or invalid session ID';
    exit;
}

$frameFile = sys_get_temp_dir() . '/palma_webrtc_' . $sessionId . '.jpg';

header('Content-Type: multipart/x-mixed-replace; boundary=frame');
header('Cache-Control: no-cache');
header('Pragma: no-cache');

set_time_limit(0);
$maxAge = 3600; // stream for max 1 hour
$start = time();

while (time() - $start < $maxAge) {
    if (file_exists($frameFile)) {
        $frame = file_get_contents($frameFile);
        if ($frame !== false && strlen($frame) > 0) {
            echo "--frame\r\n";
            echo "Content-Type: image/jpeg\r\n";
            echo "Content-Length: " . strlen($frame) . "\r\n\r\n";
            echo $frame;
            echo "\r\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }
    usleep(100000); // ~10 fps
}
