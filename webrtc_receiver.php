<?php

// Copyright (C) 2014-2024 Universitätsbibliothek Mannheim
// See file LICENSE for license details.
//
// Receives JPEG frames from a WebRTC client and stores them for display.

session_start();
require_once 'auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$sessionId = session_id();
if (empty($sessionId)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No valid session']);
    exit;
}

$frameFile = sys_get_temp_dir() . '/palma_webrtc_' . $sessionId . '.jpg';

$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No frame data received']);
    exit;
}

// Validate that the data starts with a JPEG magic header (FF D8).
if (strlen($rawInput) < 2 || substr($rawInput, 0, 2) !== "\xFF\xD8") {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JPEG data']);
    exit;
}

// Write the JPEG frame atomically.
$tmpFile = $frameFile . '.tmp';
if (file_put_contents($tmpFile, $rawInput) === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to write frame']);
    exit;
}
rename($tmpFile, $frameFile);

echo json_encode(['status' => 'ok', 'frame_file' => basename($frameFile)]);
