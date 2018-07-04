<?php

// Copyright (C) 2014 Universitätsbibliothek Mannheim
// See file LICENSE for license details.

require_once("DBConnector.class.php");
require_once("globals.php");

class SSVNCDaemon
{

    // all during session connected VNC Clients
    private $VNC_CLIENTS;

    // count of all connected clients
    // private $_VNC_CLIENT_COUNT;

    // active client count
    private $CONNECTIONS = 0;

    // ignored VNC Clients
    // TODO: check if no more longer helpful, has to be cleaned
    private $IGNORE_LIST = array();

    public $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;

        // Start Vncviewer
        $handle = $this->startVNCViewer();

        // Read log in loop
        $this->readLog($handle);
    }

    protected function startVNCViewer()
    {
        // Startup SSVNC-Viewer in Multilisten-Mode
        $cmd = "export DISPLAY=" . CONFIG_DISPLAY .
        "; killall -q ssvncviewer; ssvncviewer -viewonly -multilisten 0 2>&1";
        $handle = popen($cmd, 'r');
        print("[Daemon]: vnc_viewer started");

        return $handle;
    }

    protected function readLog($handle)
    {
        print("\n[Daemon]: +++ readLog() +++");

        // local SSVNC-Client info
        $client = array(
                    "ip" => "",
                    "hostname" => "",
                    "active" => 1,
                    "exit" => 0
                    );

        // Read File continuously
        while (!feof($handle)) {
            $buffer = fgets($handle);
            print($buffer);

            if ($this->CONNECTIONS == 0) {
                //print("\n --- WAITING FOR NEW CONNECTIONS --- \n");
            }

            $ip = $this->parseIP($buffer);
            $hostname = $this->parseHostname($buffer);
            $exit = $this->parseExit($buffer);

            if ($ip!="") {
                $client["ip"] = $ip;
            }
            if ($hostname!="") {
                $client["hostname"] = $hostname;
            }
            if ($exit!=0) {
                $client["exit"] = $exit;
            }

            if (strstr($buffer, 'create_image') && $client["ip"] != "" &&
            $client["hostname"]!="" ) {
                // add client
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

            $halt=$this->CONNECTIONS;

            if ($halt == -1) {
                exit(0);
            }

            flush();
        }

        pclose($handle);
    }

    protected function parseIP($buffer)
    {
        $ip = "";
        $line = $buffer;
        if (strpos($line, "Reverse VNC connection from IP")) {
            $line=trim($line);
            $item=explode(":", $line);
            $ip=trim($item[1]);
            $ip=preg_replace('/\W\W\d{4}\/\d{2}\/\d{2} \d{2}/', '', $ip);
        }

        if ($ip!="") {
            print("\nFOUND IP: " . $ip . "\n");
        }

        return $ip;
    }

    protected function parseHostname($buffer)
    {
        $hostname = "";
        $line = $buffer;
        if (strpos($line, "Hostname")) {
            $line=trim($line);
            $item=explode(":", $line);
            $hostname=trim($item[1]);
            $hostname=preg_replace('/\.uni\-mannheim\.de/', '', $hostname);
        }

        if ($hostname!="") {
            print("\nFOUND HOSTNAME: " . $hostname . "\n");
        }

        return $hostname;
    }

    protected function parseExit($line)
    {
        $exit = 0;
        if (strpos($line, "VNC Viewer exiting")) {
            $exit = 1;
        }

        if ($exit!=0) {
            print("\nCLIENT HAS DISCONNECTED " . $exit . "\n");
        }

        return $exit;
    }

    protected function addClient($ip, $hostname)
    {
        $vncclient = array(
                        "ip" => $ip,
                        "hostname" => $hostname,
                        "active" => 1,
                        "exit" => 0
                        );
        if (count($this->VNC_CLIENTS) == 0) {
            $id=1;
        } else {
            $id=count($this->VNC_CLIENTS) + 1;
        }

        $this->VNC_CLIENTS[$id] = $vncclient;

        print("\nCLIENT OBJECT with ID " . $id . " CREATED : "
            . $vncclient["ip"] . " | " . $vncclient["hostname"] . " | "
            . $vncclient["active"] . " | " . $vncclient["exit"] . "\n");
        print($this->CONNECTIONS+1 . " CLIENT(S) CONNECTED ...");

        $this->sendVncWindowToNuc($id, $vncclient);

        $this->CONNECTIONS++;

        print("\n active connections: ".$this->CONNECTIONS+1);
        print("\n all saved clients: " . serialize($this->VNC_CLIENTS));
    }

    protected function sendVncWindowToNuc($id, $vncclient)
    {
        print("\n[Daemon]: +++sendVncWindowToNuc() +++ ");

        $vnc_id = $vncclient["hostname"] . "-" . $id;

        $db = new palma\DBConnector();

        // already existing in db?
        $clients_in_db = array();
        $client_info = $db->getVNCClientInfo();

        foreach ($client_info as $info) {
            $clients_in_db[] = $info["file"];
        }

        $ip = $vncclient["ip"];
        $name = $db->querySingle('SELECT user.name FROM address, user
                                  WHERE user.userid = (
                                    SELECT address.userid
                                    FROM address
                                    WHERE address.address ="' . $ip . '"
                                  )');

        // print("\n[Daemon]: USERNAME : " . $name);

        // $vncClientCount = $db->querySingle('SELECT count(id) FROM window WHERE handler = "vnc"');
        // $vncClientsAll = $db->query("SELECT user.name FROM address");

        // print("\n[Daemon]: clients in db = " . $vncClientCount);
        // print("\n[Daemon]: clients ignored = " . serialize($this->IGNORE_LIST));

        // if vnc_id not in database create window and send to nuc
        if (!(in_array($vnc_id, $clients_in_db))) {
            // print("\n[Daemon]: insert $vnc_id into db");

            $dt = new DateTime();
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
        array_push($this->IGNORE_LIST, $vnc_id);
    }

    protected function deleteInactiveVncWindow()
    {
        // print("\n[Daemon]: +++ TODO: deleteInactiveWindow() +++");

        $db = new palma\DBConnector();

        // window_ids in db
        $vnc_windows_in_db = array();
        $client_info = $db->getVNCClientInfo();
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

        // print("[Daemon]: clients in db = " . serialize($vnc_windows_in_db));
        // print("[Daemon]: client on screen = " . serialize($windows_on_screen));

        // window_ids that are in the db, but not on the screen (window already closed)
        $inactive_vnc_window_ids = array_diff($vnc_windows_in_db, $windows_on_screen);

        foreach ($inactive_vnc_window_ids as $inactive_win_id) {
            // define vnc-id
            $inactive_vnc_id = $db->querySingle("SELECT file FROM window WHERE win_id='".$inactive_win_id."'");

            // delete from database (send to control.php)
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => CONFIG_CONTROL_FILE .
                    '?window=vncwin&delete=VNC&vncid=' . $inactive_win_id,
                CURLOPT_USERAGENT => 'PalMA cURL Request'
                ));

            curl_exec($curl);
            curl_close($curl);

            // print("[Daemon]: inactive vnc_id = $inactive_vnc_id >> add to list: " .serialize($this->IGNORE_LIST));
        }
    }
}

$vnc = new SSVNCDaemon();
