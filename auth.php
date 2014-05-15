<?php
    if (!isset($_SESSION)) {
        session_start();
    }

    if (!isset($_SESSION['username'])) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            error_log("auth.php referred by " . $_SERVER['HTTP_REFERER']);
        }
        header('Location: login.php');
        // header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/login.php');
        exit;
    }
?>
