<?php

function trace($text)
{
    static $firstRun = true;
  if ($firstRun) {
      $firstRun = false;
      openlog("palma", LOG_PID, LOG_USER);
  }
    syslog(LOG_NOTICE, $text);
}

function monitor($action)
{
  if (!defined('CONFIG_MONITOR_URL')) {
    //trace('CONFIG_MONITOR_URL is undefined');
      return;
  }
  //trace("monitor $action");
    $ch = curl_init();
    $url = CONFIG_MONITOR_URL;

    curl_setopt_array($ch, array(
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_URL => CONFIG_MONITOR_URL . '/' . CONFIG_STATIONNAME . '/' . base64_encode($action),
                        CURLOPT_USERAGENT => 'PalMA cURL Request'
                               ));
    $resp = curl_exec($ch);
    curl_close($ch);
}

function set_constants()
{
    // Get some constants from a configuration file.
    $conf_fn = 'palma.ini';
  if (!file_exists($conf_fn)) {
      $conf_fn = '/etc/palma.ini';
  }
    $conf = parse_ini_file($conf_fn);
    //~ print_r($conf);

    // Entries in group 'display'.
  if (array_key_exists('id', $conf)) {
      define('CONFIG_DISPLAY', $conf['id']);
  } else {
      define('CONFIG_DISPLAY', ':1');
  }
  if (array_key_exists('ssh', $conf)) {
      define('CONFIG_SSH', $conf['ssh']);
  } // There is no default value for CONFIG_SSH.

    // Entries in group 'general'.
  if (array_key_exists('password', $conf)) {
      define('CONFIG_PASSWORD', $conf['password']);
  } else {
      define('CONFIG_PASSWORD', false);
  }
  if (array_key_exists('pin', $conf)) {
      define('CONFIG_PIN', $conf['pin']);
  } else {
      define('CONFIG_PIN', true);
  }
  if (array_key_exists('stationname', $conf)) {
      define('CONFIG_STATIONNAME', $conf['stationname']);
  } else {
      define(
          'CONFIG_STATIONNAME',
          str_replace(array("\r", "\n", " "), '', `hostname -f`)
      );
  }
  if (array_key_exists('theme', $conf)) {
      define('CONFIG_THEME', $conf['theme']);
  } else {
      define('CONFIG_THEME', 'demo/simple');
  }

    // Entries in group 'path'.
  if (array_key_exists('start_url', $conf)) {
      define('CONFIG_START_URL', $conf['start_url']);
  } else {
      // By default we use the FQDN of the host
      define(
          'CONFIG_START_URL',
          'http://' . str_replace(array("\r", "\n", " "), '', `hostname -f`) . '/'
      );
  }
  if (array_key_exists('control_file', $conf)) {
      define('CONFIG_CONTROL_FILE', $conf['control_file']);
  } else {
      define('CONFIG_CONTROL_FILE', CONFIG_START_URL . 'control.php');
  }
  if (array_key_exists('policy', $conf)) {
      define('CONFIG_POLICY', $conf['policy']);
  } // There is no default value for CONFIG_POLICY.
  if (array_key_exists('upload_dir', $conf)) {
      define('CONFIG_UPLOAD_DIR', $conf['upload_dir']);
  } else {
      define('CONFIG_UPLOAD_DIR', '/tmp/palma');
  }
  if (array_key_exists('institution_url', $conf)) {
      define('CONFIG_INSTITUTION_URL', $conf['institution_url']);
  } else {
      define('CONFIG_INSTITUTION_URL', '');
  }

    // Entries in group 'monitoring'.
  if (array_key_exists('monitor_url', $conf)) {
      define('CONFIG_MONITOR_URL', $conf['monitor_url']);
  } // There is no default value for CONFIG_MONITOR_URL.
}
set_constants();
