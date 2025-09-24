<?php

// Copyright (C) 2014-2016 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

// Test whether the script was called directly (used for unit test).
if (!isset($unittest)) {
    $unittest = array();
}
$unittest[__FILE__] = (sizeof(get_included_files()) == 1);

require_once('php-gettext/gettext.inc');

    // The default translations are in locale/en_US.UTF-8/LC_MESSAGES/palma.mo.
    $locale = '';
if (isset($_REQUEST['lang'])) {
    // User requested language by URL parameter.
    $locale = $_REQUEST['lang'];
    $_SESSION['lang'] = $locale;
} elseif (isset($_SESSION['lang'])) {
    // Get language from session data.
    $locale = $_SESSION['lang'];
} elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    // Get language from browser settings.
    $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
}
switch (substr($locale, 0, 2)) {
  case 'al':
      // Albanian.
      $locale = 'sq_AL.UTF-8';
      break;
  case 'ar':
      // Arabic.
      $locale = 'ar.UTF-8';
      break;
  case 'de':
      // German.
      $locale = 'de_DE.UTF-8';
      break;
  case 'el':
      // Greek.
      $locale = 'el_GR.UTF-8';
      break;
  case 'en':
      // English.
      $locale = 'en_US.UTF-8';
      break;
  case 'es':
      // Spanish.
      $locale = 'es_ES.UTF-8';
      break;
  case 'fr':
      // French.
      $locale = 'fr_FR.UTF-8';
      break;
  case 'hi':
      // Hindi.
      $locale = 'hi_IN.UTF-8';
      break;
  case 'it':
      // Italian.
      $locale = 'it_IT.UTF-8';
      break;
  case 'ja':
      // Japanese.
      $locale = 'ja.UTF-8';
      break;
  case 'kg':
      // Kyrgyz.
      $locale = 'kg_KG.UTF-8';
      break;
  case 'lv':
      // Latvian.
      $locale = 'lv_LV.UTF-8';
      break;
  case 'ru':
      // Russian.
      $locale = 'ru_RU.UTF-8';
      break;
  case 'ur':
      // Urdu.
      $locale = 'ur_PK.UTF-8';
      break;
  case 'zh':
      // Chinese.
      $locale = 'zh_CN.UTF-8';
      break;
  default:
      $locale = 'en_US.UTF-8';
      break;
}
    //~ error_log("setlocale $locale");
    putenv("LANG=$locale");
    _setlocale(LC_MESSAGES, $locale);
    _bindtextdomain('palma', 'locale');
    _bind_textdomain_codeset('palma', 'UTF-8');
    _textdomain('palma');

if ($unittest[__FILE__]) {

  function testlocale($locale = "")
  {
    if ($locale != "") {
        _setlocale(LC_MESSAGES, $locale);
    }
      error_log(sprintf('%-12s ', ($locale ? $locale : 'default') . ':') . addslashes(__('Screen section')));
  }

    // Run unit test.

  if (locale_emulation()) {
      print "locale '$locale' is not supported on your system, using custom gettext implementation.\n";
  } else {
      print "locale '$locale' is supported on your system, using native gettext implementation.\n";
  }

    testlocale();
    testlocale('sq_AL.UTF-8');
    testlocale('ar.UTF-8');
    testlocale('de_DE.UTF-8');
    testlocale('el_GR.UTF-8');
    testlocale('en_US.UTF-8');
    testlocale('es_ES.UTF-8');
    testlocale('fr_FR.UTF-8');
    testlocale('hi_IN.UTF-8');
    testlocale('it_IT.UTF-8');
    testlocale('ja.UTF-8');
    testlocale('kg_KG.UTF-8');
    testlocale('lv_LV.UTF-8');
    testlocale('ru_RU.UTF-8');
    testlocale('ur_PK.UTF-8');
    testlocale('zh_CN.UTF-8');
}
