<?php
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['username'])) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            error_log("auth.php referred by " . $_SERVER['HTTP_REFERER']);
        }
        $header = 'Location: login.php';
        $separator = '?';
        if (isset($_REQUEST['lang'])) {
            $header = $header . $separator . 'lang=' . $_REQUEST['lang'];
            $separator = '&';
        }
        if (isset($_REQUEST['pin'])) {
            $header = $header . $separator . 'pin=' . $_REQUEST['pin'];
            $separator = '&';
        }
        header($header);

        exit;
    }
