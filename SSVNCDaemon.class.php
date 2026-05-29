<?php

namespace palma;

// Copyright (C) 2014-2023 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

class SSVNCDaemon
{
  // all during session connected VNC Clients
  /** @var array<array<string>> */
  private array $VNC_CLIENTS = array();

  // count of all connected clients
  // private $_VNC_CLIENT_COUNT;

  // active client count
  private int $CONNECTIONS = 0;

  // ignored VNC Clients
  // TODO: check if no more longer helpful, has to be cleaned
  ///** @var array<string> */
  //private array $IGNORE_LIST = array();

  public DBConnector $db;

  public function __construct()
  {
    require_once 'DBConnector.class.php';
    $this->db = DBConnector::getInstance();

    // Start Vncviewer
    $handle = $this->start();

    // Read log in loop
    $this->readLog($handle);
  }

  protected function start(): mixed
  {
    // Startup ssvncviewer in multilisten mode
    require_once 'globals.php';
    $cmd = "export DISPLAY=" . CONFIG_DISPLAY .
           "; killall -q ssvncviewer; ssvncviewer -viewonly -multilisten 0 2>&1";
    $handle = popen($cmd, 'r');
    trace("SSVNCDaemon::start: vnc_viewer started");

    return $handle;
  }

  protected function readLog(mixed $handle): void
  {
    require_once 'globals.php';
    trace("SSVNCDaemon::readLog()");

    // local SSVNC client info
    $client = array(
      "ip" => "",
      "hostname" => "",
      "active" => 1,
      "exit" => 0
    );

    // Read File continuously
    while (!feof($handle)) {
      $buffer = fgets($handle);

      if ($this->CONNECTIONS == 0) {
        //debug("SSVNCDaemon::readLog: WAITING FOR NEW CONNECTIONS");
      }

      $ip = $this->parseIP($buffer);
      $hostname = $this->parseHostname($buffer);
      $exit = $this->parseExit($buffer);

      if ($ip != "") {
        $client["ip"] = $ip;
      }
      if ($hostname != "") {
        $client["hostname"] = $hostname;
      }
      if ($exit != 0) {
        $client["exit"] = $exit;
      }

      if (
          strstr($buffer, 'create_image') && $client["ip"] != "" &&
          $client["hostname"] != ""
      ) {
        // add client
        if ($client["hostname"] == "unknown") {
          $client["hostname"] = $client["ip"];
        }
        $this->addClient($client["ip"], $client["hostname"]);

        // reset local Client information after adding it
        $client["ip"] = "";
        $client["hostname"] = "";
      }

      if ($exit == 1) {
        // decrease active client count
        if ($this->CONNECTIONS > 0) {
          $this->CONNECTIONS--;
          $this->deleteInactiveVncWindow();
        }
      }

      $halt = $this->CONNECTIONS;

      if ($halt == -1) {
        exit(0);
      }

      flush();
    }

    pclose($handle);
  }

  protected function parseIP(string $buffer): string
  {
    require_once 'globals.php';
    $ip = "";
    $line = $buffer;
    if (strpos($line, "Reverse VNC connection from IP")) {
      $line = trim($line);
      $item = explode(":", $line);
      $ip = trim($item[1]);
      $ip = preg_replace('/\W\W\d{4}\/\d{2}\/\d{2} \d{2}/', '', $ip);
    }

    if ($ip != "") {
      debug("SSVNCDaemon::parseIP(): " . $ip);
    }

    return $ip;
  }

  protected function parseHostname(string $buffer): string
  {
    require_once 'globals.php';
    $hostname = "";
    $line = $buffer;
    if (strpos($line, "Hostname")) {
      $line = trim($line);
      $item = explode(":", $line);
      $hostname = trim($item[1]);
      $hostname = preg_replace('/\.uni\-mannheim\.de/', '', $hostname);
    }

    if ($hostname != "") {
      debug("SSVNCDaemon::parseHostname(): " . $hostname);
    }

    return $hostname;
  }

  protected function parseExit(string $line): int
  {
    require_once 'globals.php';
    $exit = 0;
    if (strpos($line, "VNC Viewer exiting")) {
      $exit = 1;
    }

    if ($exit != 0) {
      debug("SSVNCDaemon::parseExit: exit code " . $exit);
    }

    return $exit;
  }

  protected function addClient(string $ip, string $hostname): void
  {
    require_once 'globals.php';
    $vncclient = array(
      "ip" => $ip,
      "hostname" => $hostname,
      "active" => 1,
      "exit" => 0
    );
    if (count($this->VNC_CLIENTS) == 0) {
      $id = 1;
    } else {
      $id = count($this->VNC_CLIENTS) + 1;
    }

    $this->VNC_CLIENTS[$id] = $vncclient;

    debug("SSVNCDaemon::addClient: CLIENT OBJECT with ID " . $id . " CREATED : "
        . $vncclient["ip"] . " | " . $vncclient["hostname"] . " | "
        . $vncclient["active"] . " | " . $vncclient["exit"]);
    debug("SSVNCDaemon::addClient: " . ($this->CONNECTIONS + 1) . " CLIENT(S) CONNECTED ...");

    $this->sendVncWindowToNuc($id, $vncclient);

    $this->CONNECTIONS++;
  }

  /**
   * @param array<string,string> $vncclient
   */
  protected function sendVncWindowToNuc(int $id, array $vncclient): void
  {
    require_once 'globals.php';
    debug("SSVNCDaemon::sendVncWindowToNuc()");

    $vnc_id = $vncclient["hostname"] . "-" . $id;

    // already existing in db?
    $clients_in_db = array();
    $client_info = $this->db->getVNCClientInfo();

    foreach ($client_info as $info) {
      $clients_in_db[] = $info["file"];
    }

    $ip = $vncclient["ip"];
    $name = $this->db->querySingle('SELECT user.name FROM address, user
                                     WHERE user.userid = (
                                     SELECT address.userid
                                     FROM address
                                     WHERE address.address ="' . $ip . '"
                                     )');

    // print("\n[Daemon]: USERNAME : " . $name);

    // $vncClientCount = $this->db->querySingle('SELECT count(id) FROM window WHERE handler = "vnc"');
    // $vncClientsAll = $this->db->query("SELECT user.name FROM address");

    // print("\n[Daemon]: clients in db = " . $vncClientCount);
    // print("\n[Daemon]: clients ignored = " . serialize($this->IGNORE_LIST));

    // if vnc_id not in database create window and send to nuc
    if (!(in_array($vnc_id, $clients_in_db))) {
      // print("\n[Daemon]: insert $vnc_id into db");

      $dt = new \DateTime();
      $date = $dt->format('Y-m-d H:i:s');

      $window = array(
        "id" => "",
        "win_id" => "",
        "name" => "",
        "state" => "",
        "file" => $name . "@" . $vnc_id,
        "handler" => "vnc",
        "userid" => $name,
        "date" => $date
      );

      $serializedWindow = serialize($window);

      require_once 'globals.php';

      $sw = urlencode($serializedWindow);
      // Get cURL resource
      $curl = curl_init();
      // Set some options - we are passing in a useragent too here
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => CONFIG_CONTROL_FILE . '?newVncWindow=' . $sw,
        CURLOPT_USERAGENT => 'PalMA cURL Request'
      ));
      // Send the request
      curl_exec($curl);
      // Close request to clear up some resources
      curl_close($curl);
    }

    // add unique id to ignore list after sending to nuc
    //array_push($this->IGNORE_LIST, $vnc_id);
  }

  protected function deleteInactiveVncWindow(): void
  {
    require_once 'globals.php';
    debug('SSVNCDaemon::deleteInactiveVncWindow()');

    // window_ids in db
    $vnc_windows_in_db = array();
    $client_info = $this->db->getVNCClientInfo();
    foreach ($client_info as $info) {
      $vnc_windows_in_db[] = $info["win_id"];
    }

    // window_ids on screen
    $windows_on_screen = array();
    $windows = explode("\n", shell_exec('wmctrl -l'));

    foreach ($windows as $w) {
      $field = explode(' ', $w);
      $id = $field[0];
      if ($id != '') {
        $windows_on_screen[] = $id;
      }
    }

    debug("  clients in db = " . serialize($vnc_windows_in_db));
    debug("  client on screen = " . serialize($windows_on_screen));

    // window_ids that are in the db, but not on the screen (window already closed)
    $inactive_vnc_window_ids = array_diff($vnc_windows_in_db, $windows_on_screen);

    foreach ($inactive_vnc_window_ids as $inactive_win_id) {
      // define vnc-id
      $inactive_vnc_id = $this->db->querySingle("SELECT file FROM window WHERE win_id='" . $inactive_win_id . "'");

      // delete from database (send to control.php)
      $curl = curl_init();

      require_once 'globals.php';
      curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => CONFIG_CONTROL_FILE .
                     '?window=vncwin&delete=VNC&vncid=' . $inactive_win_id,
        CURLOPT_USERAGENT => 'PalMA cURL Request'
      ));

      curl_exec($curl);
      curl_close($curl);

      // debug(" inactive vnc_id = $inactive_vnc_id >> add to list: " .serialize($this->IGNORE_LIST));
    }
  }
}
