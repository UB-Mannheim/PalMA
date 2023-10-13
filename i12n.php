<?php

// Copyright (C) 2014-2023 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

require_once 'php-gettext/gettext.inc';

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
