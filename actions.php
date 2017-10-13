<?php
//header('Content-Type: text/plain');

$PATH_SCRIPTS = "scripts";
$PATH_TEMPLATES = "templates";
$PATH_TMP = "tmp";

$MYSQL_DROP_SCRIPT_NAME = "drop-database.sh";
$MYSQL_DUMP_SCRIPT_NAME = "backup-database.sh";

$log_dump = "dump-spawnpoints.log";
$access_logs = "pogomap-access.log";

function rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
}

function template_header($screen_session, $message) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/header.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $screen_session, $message);
}

function template_header_server($message) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/header_server.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $message);
}

function template_mysql_dump($user, $pass, $db, $host) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/mysql_database_dump.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $user, $pass, $db, $host);
}

function template_mysql_drop($user, $pass, $db, $host) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/mysql_database_drop.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $user, $pass, $db, $host);
}

function template_restart_server($screen_session, $message, $command) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/restart_server.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $screen_session, $message, $command);
}

function template_update_script($base_path, $path_accounts, $path_pogomap) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/update.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $base_path, $path_accounts, $path_pogomap);
}

function template_update_proxies($base_path, $proxies_url, $output_file) {
  global $PATH_TEMPLATES;

  $filename = "$PATH_TEMPLATES/update-proxies.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);

  return sprintf($format, $base_path, $proxies_url, $output_file);
}

$response = [ "message" => "", "file" => ""];

function quit($message) {
  $response = [ "message" => $message, "file" => "" ];

  exit(json_encode($response));
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {

  if(isset($_POST["path-pogomap"]) && !empty($_POST["path-pogomap"])) {
    $path_pogomap = trim($_POST["path-pogomap"]);
  } else {
    $path_pogomap = "PokemonGo-Map";
  }

  if(isset($_POST["path-pokealarm"]) && !empty($_POST["path-pogomap"])) {
    $path_pokealarm = trim($_POST["path-pokealarm"]);
  } else {
    $path_pokealarm = "PokeAlarm";
  }

  if(isset($_POST["path-spclustering"]) && !empty($_POST["path-pogomap"])) {
    $path_spclustering = trim($_POST["path-spclustering"]);
  } else {
    $path_spclustering = "PokemonGo-Map/Tools/Spawnpoint-Clustering";
  }

  if(isset($_POST["path-spawnpoints"]) && !empty($_POST["path-pogomap"])) {
    $path_spawnpoints = trim($_POST["path-spawnpoints"]);
  } else {
    $path_spawnpoints = "spawnpoints";
  }

  if(isset($_POST["path-accounts"]) && !empty($_POST["path-accounts"])) {
    $path_accounts = trim($_POST["path-accounts"]);
  } else {
    $path_accounts = "accounts";
  }

  if(isset($_POST["path-proxies"]) && !empty($_POST["path-proxies"])) {
    $path_proxies = trim($_POST["path-proxies"]);
  } else {
    $path_proxies = "";
  }

  if(isset($_POST["screen-servers"]) && !empty($_POST["screen-servers"])) {
    $screen_servers = trim($_POST["screen-servers"]);
  } else {
    $screen_servers = "servers";
  }

  if(isset($_POST["screen-scanners"]) && !empty($_POST["screen-scanners"])) {
    $screen_scanners = trim($_POST["screen-scanners"]);
  } else {
    $screen_scanners = "scanners";
  }

  if(isset($_POST["screen-alarms"]) && !empty($_POST["screen-alarms"])) {
    $screen_alarms = trim($_POST["screen-alarms"]);
  } else {
    $screen_alarms = "alarms";
  }

  if(isset($_POST["screen-dump-sp"]) && !empty($_POST["screen-dump-sp"])) {
    $screen_dump_sp = trim($_POST["screen-dump-sp"]);
  } else {
    $screen_dump_sp = "dump-sp";
  }

  if(isset($_POST["mysql-host"]) && !empty($_POST["mysql-host"])) {
    $mysql_host = trim($_POST["mysql-host"]);
  } else {
    $mysql_host = "localhost";
  }

  if(isset($_POST["mysql-database"]) && !empty($_POST["mysql-database"])) {
    $mysql_database = trim($_POST["mysql-database"]);
  } else {
    $mysql_database = "pogomap";
  }

  if(isset($_POST["mysql-username"]) && !empty($_POST["mysql-username"])) {
    $mysql_username = trim($_POST["mysql-username"]);
  } else {
    $mysql_username = "pogomap";
  }

  if(isset($_POST["mysql-password"]) && !empty($_POST["mysql-password"])) {
    $mysql_password = trim($_POST["mysql-password"]);
  } else {
    $mysql_password = "";
  }

  if(isset($_POST["workforce-ratio"]) && is_numeric($_POST["workforce-ratio"]) && $_POST["workforce-ratio"] > 1) {
    $workforce_ratio = $_POST["workforce-ratio"];
  } else {
    $workforce_ratio = 2;
  }

  if(isset($_POST["account-search-interval"]) && is_numeric($_POST["account-search-interval"]) && $_POST["account-search-interval"] >= 0) {
    $account_search_interval = $_POST["account-search-interval"];
  } else {
    $account_search_interval = 28800;
  }

  if(isset($_POST["account-rest-interval"]) && is_numeric($_POST["account-rest-interval"]) && $_POST["account-rest-interval"] >= 0) {
    $account_rest_interval = $_POST["account-rest-interval"];
  } else {
    $account_rest_interval = 7200;
  }

  if(isset($_POST["time-delay-worker"]) && is_numeric($_POST["time-delay-worker"]) && $_POST["time-delay-worker"] > 0) {
    $time_delay_worker = $_POST["time-delay-worker"];
  } else {
    $time_delay_worker = 6;
  }

  if(isset($_POST["shuffle-accounts"]) && $_POST["shuffle-accounts"] == "on") {
    $shuffle_accounts = true;
  } else {
    $shuffle_accounts = false;
  }

  if(isset($_POST["accounts-to-file"]) && $_POST["accounts-to-file"] == "on") {
    $accounts_to_file = true;
  } else {
    $accounts_to_file = false;
  }

  if(isset($_POST["sp-clustering"]) && $_POST["sp-clustering"] == "on") {
    $sp_clustering = true;
  } else {
    $sp_clustering = false;
  }

  if(isset($_POST["enable-proxies"]) && $_POST["enable-proxies"] == "on") {
    $enable_proxies = true;
  } else {
    $enable_proxies = false;
  }

  if(isset($_POST["shuffle-proxies"]) && $_POST["shuffle-proxies"] == "on") {
    $shuffle_proxies = true;
  } else {
    $shuffle_proxies = false;
  }

  if(isset($_POST["proxies-to-file"]) && $_POST["proxies-to-file"] == "on") {
    $proxies_to_file = true;
  } else {
    $proxies_to_file = false;
  }

  if(isset($_POST["log-messages"]) && $_POST["log-messages"] == "on") {
    $log_messages = true;
  } else {
    $log_messages = false;
  }

  if(isset($_POST["log-filename"]) && !empty($_POST["log-filename"])) {
    $log_filename = trim($_POST["log-filename"]);
  } else {
    $log_filename = "debug.log";
  }

  if(isset($_POST["output-servers"]) && !empty($_POST["output-servers"])) {
    $output_servers = trim($_POST["output-servers"]);
  } else {
    $output_servers = "launch-servers.sh";
  }

  if(isset($_POST["output-scanners"]) && !empty($_POST["output-scanners"])) {
    $output_scanners = trim($_POST["output-scanners"]);
  } else {
    $output_scanners = "launch-scanners.sh";
  }

  if(isset($_POST["output-alarms"]) && !empty($_POST["output-alarms"])) {
    $output_alarms = trim($_POST["output-alarms"]);
  } else {
    $output_alarms = "launch-alarms.sh";
  }

  if(isset($_POST["output-proxies"]) && !empty($_POST["output-proxies"])) {
    $output_proxies = trim($_POST["output-proxies"]);
  } else {
    $output_proxies = "socks5.txt";
  }

  if (!empty($path_proxies)) {
    $output_proxies = "$path_proxies/$output_proxies";
  }

  if(isset($_POST["output-dump-sp"]) && !empty($_POST["output-dump-sp"])) {
    $output_dump_sp = trim($_POST["output-dump-sp"]);
  } else {
    $output_dump_sp = "dump-spawnpoints.sh";
  }

  if(isset($_POST["url-proxies"]) && !empty($_POST["url-proxies"])) {
    $url_proxies = trim($_POST["url-proxies"]);
    $proxies_to_file = true;
  } else {
    $url_proxies = "";
  }

  // ##########################################################################
  // read servers
  $file_servers = $_FILES["file-servers"]["tmp_name"];
  $filename_servers = $_FILES["file-servers"]["name"];

  if($_FILES["file-servers"]["error"] != UPLOAD_ERR_OK || $_FILES["file-servers"]["size"] == 0) {
    quit("Servers file upload error - code ".$_FILES["file-servers"]["error"]);
  }
  if($_FILES["file-servers"]["size"] > 100000) {
    quit("File '$filename_servers' exceeds maximum upload size.");
  }

  $read_handle = fopen($file_servers, "r") or quit("Unable to open file: $file_servers");

  $servers = array();
  $first = true;

  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;

      if(strcasecmp("name,ip,path,database,alarms,kmail", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of servers CSV file must be column headings: name,ip,path,database,alarms,kmail");
      }
    }

    // syntax: name,ip,path,database,alarms,kmail
    $server = explode(",", $line);
    $name = trim($server[0]);

    $servers[$name] = $server;
  }

  // close read handle
  fclose($read_handle);

  // ##########################################################################
  // read accounts
  $file_accounts = $_FILES["file-accounts"]["tmp_name"];
  $filename_accounts = $_FILES["file-accounts"]["name"];

  if($_FILES["file-accounts"]["error"] != UPLOAD_ERR_OK || $_FILES["file-accounts"]["size"] == 0) {
    quit("Accounts file upload error - code ".$_FILES["file-accounts"]["error"]);
  }
  if($_FILES["file-accounts"]["size"] > 500000) {
    quit("File '$filename_accounts' exceeds maximum upload size.");
  }

  $read_handle = fopen($file_accounts, "r") or quit("Unable to open file: $file_accounts");

  $accounts = array();
  $accounts_hlvl = array();
  $first = true;

  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;

      if(strcasecmp("service,username,password,level,banned", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of accounts CSV file must be column headings: service,username,password,level,banned");
      }
    }

    // syntax: service,username,password,level,banned
    $account = explode(",", $line);

    $level = trim($account[3]);
    $banned = trim($account[4]);

    if(!empty($banned) && $banned > 0) {
      $response["message"] .= "Skipped banned account '$account[1] : $account[2]'\n";
      continue;
    }

    if($level >= 30) {
      $accounts_hlvl[] = $account;
    } else {
      $accounts[] = $account;
    }
  }

  $total_accounts = count($accounts);
  $total_accounts_hlvl = count($accounts_hlvl);

  // close read handle
  fclose($read_handle);


  // ##########################################################################
  // read alarms
  $file_alarms = $_FILES["file-alarms"]["tmp_name"];
  $filename_alarms = $_FILES["file-alarms"]["name"];

  if($_FILES["file-alarms"]["error"] != UPLOAD_ERR_OK || $_FILES["file-alarms"]["size"] == 0 ) {
    quit("Alarms file upload error - code ".$_FILES["file-alarms"]["error"]);
  }
  if($_FILES["file-alarms"]["size"] > 100000) {
    quit("File '$filename_alarms' exceeds maximum upload size.");
  }

  $read_handle = fopen($file_alarms, "r") or quit("Unable to open file: $file_alarms");

  $alarms = array();
  $first = true;

  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;

      if(strcasecmp("enabled,name,address,port,location,config,api", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of alarms CSV file must be column headings: enabled,name,address,port,location,config,api");
      }
    }

    // syntax: enabled,address,port,location,name
    $alarm = explode(",", $line);

    $enabled = trim($alarm[0]);
    $name = trim($alarm[1]);

    if($enabled != "1") {
      $response["message"] .= "Skipped disabled alarm '$alarm[2] : $alarm[3]'\n";
      continue;
    }

    $alarms[$name] = $alarm;
  }
  $total_alarms = count($alarms);

  // close read handle
  fclose($read_handle);

  // ##########################################################################
  // read instances
  $file_instances = $_FILES["file-instances"]["tmp_name"];
  $filename_instances = $_FILES["file-instances"]["name"];

  if($_FILES["file-instances"]["error"] != UPLOAD_ERR_OK || $_FILES["file-instances"]["size"] == 0 ) {
    quit("Instances file upload error - code ".$_FILES["file-instances"]["error"]);
  }
  if($_FILES["file-instances"]["size"] > 100000) {
    quit("File '$filename_instances' exceeds maximum upload size.");
  }

  $read_handle = fopen($file_instances, "r") or quit("Unable to open file: $file_instances");

  $instances = array();
  $first = true;

  $total_servers = array();
  $total_scanners = array();
  $cum_servers = 0;
  $cum_scanners = 0;

  foreach ($servers as $server) {
    $name = trim($server[0]);
    $total_servers[$name] = 0;
    $total_scanners[$name] = 0;
  }

  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;

      if(strcasecmp("enabled,server,webhook,modes,location,name,st,sd,workers,accounts,hlvl", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of instances CSV file must be column headings: enabled,server,webhook,modes,location,name,st,sd,workers,accounts,hlvl");
      }
    }

    // syntax: enabled,server,webhook,modes,location,name,st,sd,workers,accounts,hlvl
    $instance = explode(",", $line);

    $enabled = trim($instance[0]);
    $server = trim($instance[1]);
    $webhook = trim($instance[2]);

    if($enabled != "1") {
      $response["message"] .= "Skipped disabled instance '$instance[5] : $instance[4]'\n";
      continue;
    }

    if (!array_key_exists($server, $servers)) {
      quit("Instance server '$server' was not found in '$filename_servers'");
    }
    if (!empty($webhook) && !array_key_exists($webhook, $alarms)) {
      quit("Webhook server '$webhook' was not found in '$filename_alarms'");
    }

    if(preg_match("/-os/i", $instance[3])) {
      $total_servers[$server]++;
      $cum_servers++;
    } else {
      $total_scanners[$server]++;
      $cum_scanners++;
    }

    $instances[] = $instance;
  }
  $total_instances = count($instances);

  // close read handle
  fclose($read_handle);

  // ##########################################################################
  // read proxies
  $file_proxies = $_FILES["file-proxies"]["tmp_name"];
  $filename_proxies = $_FILES["file-proxies"]["name"];

  $proxies = array();

  if($enable_proxies) {
    if(empty($filename_proxies) || $filename_proxies == "") {
      if(empty($url_proxies) || $url_proxies == "") {
        quit("No proxy list file and no proxy list URL. Please supply one of these fields.");
      }
    } else {
      if($_FILES["file-proxies"]["error"] != UPLOAD_ERR_OK || $_FILES["file-proxies"]["size"] == 0) {
        quit("Proxy list file upload error - code ".$_FILES["file-proxies"]["error"]);
      }
      if($_FILES["file-proxies"]["size"] > 100000) {
        quit("File '$filename_proxies' exceeds maximum upload size.");
      }

      $read_handle = fopen($file_proxies, "r") or quit("Unable to open file: $file_proxies");


      $first = true;
      $is_csv = false;

      while (($line = fgets($read_handle)) !== false) {
        if($first) {
          $first = false;

          if(strcasecmp("enabled,ip:port", trim($line)) == 0) {
            $is_csv = true;
            // skip header line
            continue;
          }
        }
        if ($is_csv) {
          $proxy = explode(",", $line);
          $enabled = trim($proxy[0]);
          $proxy_ip_port = trim($proxy[1]);

          if($enabled != "1") {
            $response["message"] .= "Skipped disabled proxy '$proxy_ip_port'.\n";
            continue;
          }

          $proxies[] = $proxy_ip_port;
        } else {
          // syntax: ip:port
          $proxies[] = trim($line);
        }
      }

      // close read handle
      fclose($read_handle);
    }
  }

  $total_proxies = count($proxies);

  // ##########################################################################
  // create script folders

  $tmp_id = uniqid();

  $zip = new ZipArchive();

  $output_filename = "$PATH_TMP/$tmp_id.zip";

  if ($zip->open($output_filename, ZipArchive::CREATE) !== TRUE) {
    quit("Unable to create output zip archive: $output_filename\n");
  }

  $scanner_servers = 0;
  $content_upload_config = "#!/bin/bash\n\n";

  foreach($servers as $server) {
    $name = trim($server[0]);
    $ip = trim($server[1]);
    $path = trim($server[2]);
    $database = trim($server[3]);
    $alarms_enabled = trim($server[4]);
    $kmail = trim($server[5]);

    $content_upload_config .= "scp -r $name/* root@$ip:$path\n";

    if($total_scanners[$name] > 0) {
      $scanner_servers++;
    }

    $zip->addEmptyDir($name);
    /*
    $zip->addEmptyDir("$name/$path_spawnpoints");
    $zip->addEmptyDir("$name/$path_accounts");
    $zip->addEmptyDir("$name/$path_accounts-hlvl");
    */
    $update_script = template_update_script($path, $path_accounts, $path_pogomap);
    $zip->addFromString("$name/update.sh", $update_script);

    if (!empty($url_proxies) && $url_proxies != "") {
      $proxies_script = template_update_proxies($path, "$url_proxies/socks5.txt", $output_proxies);
      $zip->addFromString("$name/update-proxies.sh", $proxies_script);
    }

    $zip->addFile("$PATH_SCRIPTS/shuffle.py", "$name/$path_accounts/shuffle.py");
    $zip->addFile("$PATH_SCRIPTS/shuffle.py", "$name/$path_accounts-hlvl/shuffle.py");
    $zip->addFile("$PATH_SCRIPTS/remove_banned.py", "$name/$path_accounts/remove_banned.py");
  }

  $zip->addFromString("upload_config.sh", $content_upload_config);

  // ##########################################################################
  // alarms

  foreach($servers as $server) {
    $server_name = trim($server[0]);
    $server_path = trim($server[2]);
    $server_alarms = trim($server[4]);
    if ($server_alarms != 1)
      continue;

    $content_alarms = template_header($screen_alarms, "Launching PokeAlarm instances...");
    $curr_alarm = 0;

    foreach($alarms as $alarm) {
      $enabled = trim($alarm[0]);
      $name = trim($alarm[1]);
      $address = trim($alarm[2]);
      $port = trim($alarm[3]);
      $location = trim($alarm[4]);
      $config = trim($alarm[5]);
      $api_key = trim($alarm[6]);

      // increment alarm number so it matches screen's window number
      $curr_alarm++;

      $comment = "# $curr_alarm $name -host $address:$port -l $location -cf $config -k $api_key -a alarms.json -f filters.json --------------------";

      $message = "echo \# $curr_alarm $name -host $address:$port -cf $config";

      // command to output
      $command = "screen -S \"$screen_alarms\" -x -X screen bash -c 'python $server_path/$path_pokealarm/start_pokealarm.py; exec bash'";

      /*
      if(isset($location) && !empty($location)) {
        $command .= " -l \"$location\"";
      }
      $command .= "; exec bash'";
      */

      $content_alarms .= $comment."\n";
      $content_alarms .= $command."\n";
      $content_alarms .= $message."\n";

      // Don't include timer on last alarm
      if($curr_alarm < $total_alarms) {
        $content_alarms .= "timer 2\n\n";
      }
    }

    // output alarms script
    $zip->addFromString("$server_name/$output_alarms", $content_alarms);

    $shutdown_alarms = "#!/bin/bash\n\n";
    $shutdown_alarms .= "screen -X -S \"$screen_alarms\" quit\n";
    $shutdown_alarms .= "echo Screen session \"$screen_alarms\" terminated.\n";
    $zip->addFromString("$server_name/shutdown-alarms.sh", $shutdown_alarms);
  }

  // ##########################################################################
  // instances

  $curr_account = 0;
  $curr_account_hlvl = 0;

  // randomize the account list
  if($shuffle_accounts) {
    shuffle($accounts);
    shuffle($accounts_hlvl);
  }

  $curr_proxy = 0;
  $proxies_per_server = 0;

  // randomize the proxy list
  if($enable_proxies) {
      if($total_proxies > 0) {
        if($shuffle_proxies) {
          shuffle($proxies);
        }

        // distribute proxies
        if($cum_scanners > 0) {
          $proxies_per_server = floor($total_proxies / $scanner_servers);

          // threshold
          if(!$proxies_to_file && $proxies_per_server > 10) {
            $proxies_per_server = 10;
          }
        }
      } elseif(!empty($url_proxies) && $url_proxies != "") {
        $proxies_per_server = 1;
      }
    }

  $content_servers = array();
  $content_scanners = array();
  $content_dump_sp = array();
  $content_shutdown_servers = array();
  $content_pogo_captcha = array();

  $curr_server = array();
  $curr_scanner = array();

  foreach ($servers as $server) {
    $name = trim($server[0]);

    $content_servers[$name] = template_header_server("Launching PokemonGo-Map Server instances...");
    $content_scanners[$name] = template_header($screen_scanners, "Launching PokemonGo-Map Scanner instances...");
    $content_dump_sp[$name] = template_header($screen_dump_sp, "Dumping PokemonGo-Map spawnpoints...");
    $content_shutdown_servers[$name] = "#!/bin/bash\n";
    $content_pogo_captcha[$name] = "";

    $curr_server[$name] = 0;
    $curr_scanner[$name] = 0;
  }

  $error = false;
  $dump_sp_required = false;

  foreach($instances as $instance) {
    $enabled = trim($instance[0]);
    $server = trim($instance[1]);
    $webhook = trim($instance[2]);
    $modes = trim($instance[3]);
    $location = trim($instance[4]);
    $name = trim($instance[5]);
    $st = trim($instance[6]);
    $sd = trim($instance[7]);
    $num_workers = trim($instance[8]);
    $num_accs = trim($instance[9]);
    $num_accs_hlvl = trim($instance[10]);

    $server_ip =  trim($servers[$server][1]);
    $server_path =  trim($servers[$server][2]);

    $server_proxies = 0;

    if($total_scanners[$server] > 0)
      $server_proxies = $proxies_per_server;

    // sanitize names
    $clean_name = str_replace(" ", "_", strtolower($name));

    // separate server instances
    $is_server = preg_match("/-cf server/i", $modes);

    if(preg_match("/-cf/i", $modes)) {
      // append path to config filename in -cf
      $modes = preg_replace('/-cf\s+/i', "-cf $server_path/$path_pogomap/config/", $modes);
    }

    if(preg_match("/-scf/i", $modes)) {
      // append path to config filename in -scf
      $modes = preg_replace('/-scf\s+/i', "-scf $server_path/$path_pogomap/config/", $modes);
    }

    if($is_server) {
      $index_server = ++$curr_server[$server];

      $message = "Server $index_server - $name - $modes";
      $run_server = "python $server_path/$path_pogomap/runserver.py $modes -sn \"0$index_server - $name\" -l \"$location\"";

      if($index_server == 1 && $log_messages) {
        $run_server .= " -v $server_path/server-$log_filename";
      }
      // Access logs
      #$run_server .= " -al";

      // create restart script
      $script_content = template_restart_server("server$index_server", $message, $run_server);
      $zip->addFromString("$server/restart-server$index_server.sh", $script_content);

      $command = "./restart-server$index_server.sh";
      //$command = "screen -S \"$screen_servers\" -x -X screen bash -c '$run_server; exec bash'";

      //$content_servers[$server] .= "# $message"."\n";
      $content_servers[$server] .= $command."\n";
      //$content_servers[$server] .= "echo \# $message"."\n\n";

      if($index_server < $total_servers[$server]) {
        $content_servers[$server] .= "timer 3\n";
      }

      $content_shutdown_servers[$server] .= "screen -X -S \"server$index_server\" quit\n";
      $content_shutdown_servers[$server] .= "echo Screen session \"server$index_server\" terminated.\n";
      $content_shutdown_servers[$server] .= "sleep 1\n";

      continue;
    }

    // increment scanner number so it matches screen's window number
    $index_scanner = ++$curr_scanner[$server];

    if($curr_account + $num_accs > $total_accounts) {
      $response["message"] .= "Insufficient accounts, script stopped at instance #$index_scanner: $name\n";
      break;
    }

    if($curr_account_hlvl + $num_accs_hlvl > $total_accounts_hlvl) {
      $response["message"] .= "Insufficient high-level accounts, script stopped at instance #$index_scanner: $name\n";
      break;
    }

    // write output script
    $comment = "# $index_scanner $name | $modes | st: $st | sd: $sd | w: $num_workers | accs: $num_accs --------------------";

    $message = "echo \# $index_scanner $name $modes -st $st -sd $sd -w $num_workers -accs $num_accs";

    // spawnpoint clustering
    if($sp_clustering && preg_match("/-ss/i", $modes)) {

      $dump_sp_required = true;

      // pick first account available and let it be reused later
      $dump_user = trim($accounts[$curr_account][1]);
      $dump_pass = trim($accounts[$curr_account][2]);

      $output_spawns = "$server_path/$path_spawnpoints/spawns-$index_scanner.json";
      $output_compressed = "$server_path/$path_spawnpoints/compressed-$index_scanner.json";

      $command_clustering = "python $server_path/$path_spclustering/cluster.py $output_spawns -os $output_compressed -r 70 -t 180";

      $command_dump = "screen -S \"$screen_dump_sp\" -x -X screen bash -c 'timeout -sHUP 60s python $server_path/$path_pogomap/runserver.py -P 5010 -l \"$location\" -st $st -u $dump_user -p $dump_pass -ss $output_spawns --dump-spawnpoints; $command_clustering >> $log_dump'";

      $content_dump_sp[$server] .= $comment."\n";
      $content_dump_sp[$server] .= $command_dump."\n\n";
      $content_dump_sp[$server] .= $message."\n\n";

      if($index_scanner < $total_scanners[$server]) {
        $content_dump_sp[$server] .= "timer 5\n\n";
      }

      // append compressed spawnpoints file to -ss flag
      $modes = preg_replace('/-ss/i', "-ss $output_compressed", $modes);
    }

    $command = "screen -S \"$screen_scanners\" -x -X screen bash -c 'while true; do python $server_path/$path_pogomap/runserver.py $modes -sn \"$name - $index_scanner\" -l \"$location\"";


    // scanners
    if(!$is_server) {
      if ($st != '')
        $command .= " -st $st";

      if ($sd != '')
        $command .= " -sd $sd";

      // -ari: Seconds for accounts to rest when they fail or are switched out. 0 to disable.
      $command .= " -ari $account_rest_interval";

      if($index_scanner == 1 && $log_messages) {
        $command .= " -v $server_path/scanner-$log_filename";
      }

      // number of workers (only useful if num workers < num accs)
      if(is_numeric($num_workers) && $num_workers > 0 && $num_workers < $num_accs) {
        $command .= " -w $num_workers";

        // account rotation
        if($num_accs >= ($num_workers * $workforce_ratio)) {
          // -asi: seconds for accounts to search before switching to a new account. 0 to disable.
          $command .= " -asi $account_search_interval";
        }
      }

      if($num_accs_hlvl > 0) {
        // output high-level accounts for each instance in separate files
        $content_accounts_hlvl = "";

        // select accounts for this instance
        for($i=0; $i<$num_accs_hlvl; $i++) {

          $service = trim($accounts_hlvl[$curr_account_hlvl][0]);
          $user = trim($accounts_hlvl[$curr_account_hlvl][1]);
          $pass = trim($accounts_hlvl[$curr_account_hlvl][2]);

          $content_accounts_hlvl .= "$service,$user,$pass\n";
          $curr_account_hlvl++;
        }
        // output high-level accounts
        $output_accounts_hlvl = "$path_accounts-hlvl/accounts-hlvl-$index_scanner-$clean_name.csv";
        $zip->addFromString("$server/$output_accounts_hlvl", $content_accounts_hlvl);

        $command .= " -hlvl $server_path/$output_accounts_hlvl";
      }

      if($num_accs > 0) {
        // output accounts for each instance in separate files
        if($accounts_to_file) {

          $content_accounts = "";

          // select accounts for this instance
          for($i=0; $i<$num_accs; $i++) {

            $service = trim($accounts[$curr_account][0]);
            $user = trim($accounts[$curr_account][1]);
            $pass = trim($accounts[$curr_account][2]);

            $content_accounts .= "$service,$user,$pass\n";
            $curr_account++;
          }

          // output accounts
          $output_accounts = "$path_accounts/accounts-$index_scanner-$clean_name.csv";
          $zip->addFromString("$server/$output_accounts", $content_accounts);

          // pogo captcha content
          $content_pogo_captcha[$server] .= "python pogo-captcha.py -ac $output_accounts -l \"".str_replace(" ", ",", $location)."\"\n";

          $command .= " -ac $server_path/$output_accounts";

        } else {
          // select accounts for this instance
          for($i=0; $i<$num_accs; $i++) {

            $user = trim($accounts[$curr_account][1]);
            $pass = trim($accounts[$curr_account][2]);

            // append account to current command
            $command .= " -u $user -p $pass";
            $curr_account++;
          }
        }
      }

      if($enable_proxies && $server_proxies > 0) {
        $command .= " -pxt 1 -pxd full -pxr 3600 -pxo round";

        if($proxies_to_file) {
          $command .= " -pxf $server_path/$output_proxies";
        } else {
          for($i=0; $i < $server_proxies; $i++) {
            $ip_port = $proxies[$curr_proxy];

            $command .= "-px socks5://$ip_port";
            $curr_proxy++;
          }
        }
      }
    }

    if(!empty($webhook)) {
      $webhooks = explode(" ", $webhook);

      foreach($webhooks as $webhook) {
        $webhook = trim($webhook);

        if(array_key_exists($webhook, $alarms)) {
          $alarm_wh_address = $alarms[$webhook][2];
          $alarm_wh_port = $alarms[$webhook][3];

          $command .= " -wh http://$alarm_wh_address:$alarm_wh_port";
        }
      }
    }

    $command .= "; done; exec bash'";

    $content_scanners[$server] .= $comment."\n";
    $content_scanners[$server] .= $command."\n";
    $content_scanners[$server] .= $message."\n\n";

    if($index_scanner < $total_scanners[$server]) {
      // X*#workers+1 seconds of sleep between each instance launched
      $sleeptime = ($time_delay_worker * $num_workers) + 1;
      $content_scanners[$server] .= "timer $sleeptime\n";
    }

  }

  foreach ($servers as $server) {
    $name = trim($server[0]);
    $server_path = trim($server[2]);
    $database = trim($server[3]);

    // output proxy lists
    if($proxies_to_file && $total_scanners[$name] > 0 && $total_proxies > 0) {
      $content_proxies = "";
      for($i=0; $i < $proxies_per_server; $i++) {
        $ip_port = $proxies[$curr_proxy];

        if(!preg_match("/socks5/i", $ip_port)) {
          $content_proxies .= "socks5://$ip_port\n";
        } else {
          $content_proxies .= "$ip_port\n";
        }
        $curr_proxy++;
      }
      if (!empty($path_proxies)) {
        $filepath_proxies = "$name/$path_proxies/proxies.txt";
      } else {
        $filepath_proxies = "$name/proxies.txt";
      }
      $zip->addFromString($filepath_proxies, $content_proxies);
      $zip->addFromString("$name/$output_proxies", $content_proxies);
    }

    // output server scripts
    if($curr_server[$name] > 0) {
      $zip->addFromString("$name/$output_servers", $content_servers[$name]);
      /*
      $shutdown_servers = "screen -X -S \"$screen_servers\" quit\n";
      $shutdown_servers .= "echo Screen session \"$screen_servers\" terminated.\n";
      $zip->addFromString("$name/shutdown-servers.sh", $shutdown_servers);
      */
      $zip->addFromString("$name/shutdown-servers.sh", $content_shutdown_servers[$name]);
    }

    // output scanner scripts
    if($curr_scanner[$name] > 0) {
      $zip->addFromString("$name/$output_scanners", $content_scanners[$name]);

      // output pogo captcha
      if($accounts_to_file) {
        $zip->addFromString("$name/pogo-captcha.txt", $content_pogo_captcha[$name]);
      }

      $shutdown_scanners = "#!/bin/bash\n";
      $shutdown_scanners .= "screen -X -S \"$screen_scanners\" quit\n";
      $shutdown_scanners .= "echo Screen session \"$screen_scanners\" terminated.\n";
      $shutdown_scanners .= "if [ ! -z \"$1\" ]; then python $server_path/$path_accounts/shuffle.py && python $server_path/$path_accounts-hlvl/shuffle.py && echo \"Successfully shuffled accounts.\"; fi\n";
      $zip->addFromString("$name/shutdown-scanners.sh", $shutdown_scanners);
    }

    // output dump spawnpoints script
    if($dump_sp_required) {
      // finalize dump-spawnpoints script
      $content_dump_sp[$name] .= "echo Compressing spawnpoints...\n";
      $content_dump_sp[$name] .= "timer 60\n";
      $content_dump_sp[$name] .= "screen -X -S \"$screen_dump_sp\" quit\n";

      $zip->addFromString("$name/$output_dump_sp", $content_dump_sp[$name]);
    }

    if(!empty($database) && $database != 0) {
      // include mysql database dump script
      $script_content = template_mysql_dump($mysql_username, $mysql_password, $mysql_database, $mysql_host);
      $zip->addFromString("$name/$MYSQL_DUMP_SCRIPT_NAME", $script_content);

      // include mysql database drop script
      $script_content = template_mysql_drop($mysql_username, $mysql_password, $mysql_database, $mysql_host);
      $zip->addFromString("$name/$MYSQL_DROP_SCRIPT_NAME", $script_content);
    }

  }

  $response["message"] .= "Launch script created: ".$zip->numFiles." files output.";
  $response["file"] = $tmp_id;

  $zip->close();

  // send response
  exit(json_encode($response));

} else {

  print(template_mysql_drop("test", "pw", "pogomap", "localhost"));

  //echo phpinfo();

}
?>
