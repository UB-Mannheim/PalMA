<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Test whether the script was called directly (used for unit test).
if (!isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = (sizeof(get_included_files()) == 1);

    // The default translations are in locale/en_US.UTF-8/LC_MESSAGES/palma.mo.
    $locale = '';
    if (isset($_REQUEST['lang'])) {
        // User requested language by URL parameter.
        $locale = $_REQUEST['lang'];
        $_SESSION['lang'] = $locale;
    } else if (isset($_SESSION['lang'])) {
        // Get language from session data.
        $locale = $_SESSION['lang'];
    } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        // Get language from browser settings.
        $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }
    switch (substr($locale, 0, 2)) {
    case 'de':
        $locale = 'de_DE.UTF-8';
        break;
    case 'en':
        $locale = 'en_US.UTF-8';
        break;
    default:
        $locale = 'en_US.UTF-8';
        break;
    }
    //~ error_log("setlocale $locale");
    putenv("LANG=$locale");
    setlocale(LC_ALL, $locale);
    bindtextdomain('palma', 'locale');
    textdomain('palma');

if ($unittest[__FILE__]) {
    // Run unit test.
    error_log('default:     ' . _('Screen section'));
    setlocale(LC_ALL, 'de_DE.UTF-8');
    error_log('de_DE.UTF-8: ' . _('Screen section'));
    setlocale(LC_ALL, 'en_US.UTF-8');
    error_log('en_US.UTF-8: ' . _('Screen section'));
}

?>
