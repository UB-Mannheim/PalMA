<?php

function trace($text)
{
    static $firstRun = true;
    if($firstRun) {
        $firstRun = false;
        openlog("palma", LOG_PID, LOG_USER);
    }
    syslog(LOG_NOTICE, $text);
}

function monitor($action)
{
  trace("monitor $action");
  if (!defined('CONFIG_MONITOR_URL')) {
    return;
  }
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

    $conf = parse_ini_file("palma.ini");
    //~ print_r($conf);

    if (!$conf) {
        trace("Error, parsing of palma.ini failed");
    } else {
        // Entries in group 'display'.
        if (array_key_exists('id', $conf)) {
            define('CONFIG_DISPLAY', $conf['id']);
        }
        if (array_key_exists('ssh', $conf)) {
            define('CONFIG_SSH', $conf['ssh']);
        }

        // Entries in group 'general'.
        if (array_key_exists('password', $conf)) {
            define('CONFIG_PASSWORD', $conf['password']);
        }
        if (array_key_exists('pin', $conf)) {
            define('CONFIG_PIN', $conf['pin']);
        }
        if (array_key_exists('stationname', $conf)) {
            define('CONFIG_STATIONNAME', $conf['stationname']);
        }
        if (array_key_exists('theme', $conf)) {
            define('CONFIG_THEME', $conf['theme']);
        }

        // Entries in group 'path'.
        if (array_key_exists('control_file', $conf)) {
            define('CONFIG_CONTROL_FILE', $conf['control_file']);
        }
        if (array_key_exists('policy', $conf)) {
            define('CONFIG_POLICY', $conf['policy']);
        }
        if (array_key_exists('start_url', $conf)) {
            define('CONFIG_START_URL', $conf['start_url']);
        }
        if (array_key_exists('upload_dir', $conf)) {
            define('CONFIG_UPLOAD_DIR', $conf['upload_dir']);
        }
        if (array_key_exists('institution_url', $conf)) {
            define('CONFIG_INSTITUTION_URL', $conf['institution_url']);
        }

        // Entries in group 'monitoring'.
        if (array_key_exists('monitor_url', $conf)) {
            define('CONFIG_MONITOR_URL', $conf['monitor_url']);
        }
    }

    // Set default values for constants missing in the configuration file.
    if (!defined('CONFIG_CONTROL_FILE')) {
        // By default we use control.php.
        define('CONFIG_CONTROL_FILE', CONFIG_START_URL . 'control.php');
    }
    if (!defined('CONFIG_DISPLAY')) {
        // By default we use X display :0.
        define('CONFIG_DISPLAY', ':0');
    }
    if (!defined('CONFIG_PASSWORD')) {
        // Enable password authentisation by default.
        define('CONFIG_PASSWORD', true);
    }
    if (!defined('CONFIG_PIN')) {
        // Enable PIN authentisation by default.
        define('CONFIG_PIN', true);
    }
    // There is no default value for CONFIG_POLICY.
    // There is no default value for CONFIG_SSH.
    // There is no default value for CONFIG_START_URL.
    if (!defined('CONFIG_STATIONNAME')) {
        // Use the host name as the default station name.
        define('CONFIG_STATIONNAME', gethostname());
    }
    if (!defined('CONFIG_THEME')) {
        // The default theme is demo/simple.
        define('CONFIG_THEME', 'demo/simple');
    }
    if (!defined('CONFIG_UPLOAD_DIR')) {
        // The default theme is /var/www/html/uploads.
        define('CONFIG_UPLOAD_DIR', '/var/www/html/uploads');
    }
}
set_constants();
