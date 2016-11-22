<?php
//header('Content-Type: text/plain');

$PATH_TEMPLATES = "templates";
$PATH_TMP = "tmp";

$MYSQL_RESET_SCRIPT_NAME = "reset_database.sh";

$log_dump = "dump-spawnpoints.log";

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

function template_mysql_reset($user, $pass, $db, $host) {
  global $PATH_TEMPLATES;
  
  $filename = "$PATH_TEMPLATES/mysql_database_reset.txt";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);
  
  return sprintf($format, $user, $pass, $db, $host);
}

$response = [ "message" => "", "file" => ""];

function quit($message) {
  $response = [ "message" => $message, "file" => "" ];
  
  exit(json_encode($response));
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
  
  if(isset($_POST["path-base"]) && !empty($_POST["path-base"])) {
		$path_base = trim($_POST["path-base"]);
	} else {
    $path_base = "~";
  }

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
    $path_ssclustering = "PokemonGo-Map/Tools/Spawnpoint-Clustering";
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
    $path_proxies = "proxies";
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
	
	if(isset($_POST["max-instances"]) && is_numeric($_POST["max-instances"]) && $_POST["max-instances"] > 1) {
		$max_instances = $_POST["max-instances"];
	} else {
		$max_instances = 1000000;
	}
  
  if(isset($_POST["time-delay-worker"]) && is_numeric($_POST["time-delay-worker"]) && $_POST["time-delay-worker"] > 0) {
		$time_delay_worker = $_POST["time-delay-worker"];
	} else {
		$time_delay_worker = 1;
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
		$log_filename = "pokemongo-map.log";
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
	
	if(isset($_POST["output-dump-sp"]) && !empty($_POST["output-dump-sp"])) {
		$output_dump_sp = trim($_POST["output-dump-sp"]);
	} else {
		$output_dump_sp = "dump-spawnpoints.sh";
	}
  
  if(isset($_POST["output-pogo-captcha"]) && !empty($_POST["output-pogo-captcha"])) {
		$output_pogo_captcha = trim($_POST["output-pogo-captcha"]);
	} else {
		$output_pogo_captcha = "pogo-captcha.txt";
	}
  
  // ##########################################################################
  // read accounts
  $file_accounts = $_FILES["file-accounts"]["tmp_name"];
	$filename_accounts = $_FILES["file-accounts"]["name"];
  
  if($_FILES["file-accounts"]["error"] != UPLOAD_ERR_OK || $_FILES["file-accounts"]["size"] == 0) {
    quit("Accounts file upload error - code ".$_FILES["file-accounts"]["error"]);
  }
  if($_FILES["file-accounts"]["size"] > 100000) {
    quit("File '$filename_accounts' exceeds maximum upload size.");
  }
 
  $read_handle = fopen($file_accounts, "r") or quit("Unable to open file: $file_accounts");
 
  $accounts = array();
  $first = true;
  
  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;

      if(strcasecmp("service,username,password,banned", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of accounts CSV file must be column headings: service,username,password,banned");
      }
    }
    
    // syntax: service,username,password,banned
    $account = explode(",", $line);
    
    $banned = trim($account[3]);
    
    if(!empty($banned) && $banned > 0) {
      //echo "Debug: Skipped banned account '$account[1] : $account[2]'<br>";
      continue;
    }
    
    $accounts[] = $account;
  }
  
  $total_accounts = count($accounts);
  
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
      //echo "Debug: Skipped disabled alarm '$alarm[4] : $alarm[3]'<br>";
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
  
  $total_scanners = 0;
  $total_servers = 0;
  
  while (($line = fgets($read_handle)) !== false) {
    if($first) {
      $first = false;
      
      if(strcasecmp("enabled,modes,location,name,st,sd,workers,accounts,webhook", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of instances CSV file must be column headings: enabled,modes,location,name,st,sd,workers,accounts,webhook");
      }
    }
    
    // syntax: enabled,modes,location,name,st,sd,workers,accounts,webhook
    $instance = explode(",", $line);
    
    $enabled = trim($instance[0]);
  
    if($enabled != "1") {
      //echo "Debug: Skipped disabled instance '$instance[3] : $instance[2]'<br>";
      continue;
    }
    
    if(preg_match("/-os/i", $instance[1])) {
      $total_servers++;
    } else {
      $total_scanners++;
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
      
    if($_FILES["file-proxies"]["error"] != UPLOAD_ERR_OK || $_FILES["file-proxies"]["size"] == 0) {
      quit("Proxy list file upload error - code ".$_FILES["file-proxies"]["error"]);
    }
    if($_FILES["file-proxies"]["size"] > 100000) {
      quit("File '$filename_proxies' exceeds maximum upload size.");
    }
   
    $read_handle = fopen($file_proxies, "r") or quit("Unable to open file: $file_proxies");
   
    
    $first = true;
    
    while (($line = fgets($read_handle)) !== false) {
      if($first) {
        $first = false;
  
        if(strcasecmp("ip:port", trim($line)) == 0) {
          // skip header line
          continue;
        } else {
          quit("First line of proxy list file must be: ip:port");
        }
      }
      
      // syntax: ip:port     
      $proxies[] = trim($line);
    }
    
    // close read handle
    fclose($read_handle);
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
  
  $zip->addEmptyDir($path_spawnpoints);
  $zip->addEmptyDir($path_accounts);
  
	// ##########################################################################
	// alarms
  
  $content_alarms = template_header($screen_alarms);
  
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
		
		$comment = "# $curr_alarm $name -host $address:$port -loc $location -c $config --------------------";
		
		$message = "echo \# $curr_alarm $name -host $address:$port -loc $location -c $config";
		
		// command to output
		$command = "screen -S \"$screen_alarms\" -x -X screen bash -c 'python $path_base/$path_pokealarm/runwebhook.py -P $port -c $config -k $api_key";
		
		if(isset($location) && !empty($location)) {
		  $command .= " -l \"$location\"";
		}
		
		$command .= "; exec bash'";

    $content_alarms .= $comment."\n";
		$content_alarms .= $command."\n";
    $content_alarms .= $message."\n";
		
		// Don't include timer on last alarm
		if($curr_alarm < $total_alarms) {
      $content_alarms .= "timer 2\n\n";
		}
	}
	  
  // output alarms script
  $zip->addFromString($output_alarms, $content_alarms);
	
	// ##########################################################################
	// instances
  
  $curr_account = 0;
  
	// randomize the account list
  if($shuffle_accounts) {
	  shuffle($accounts);
  }
	
  $curr_proxy = 0;
  $proxies_per_instance = 0;

  // randomize the proxy list
  if($enable_proxies) {
    
    if($shuffle_proxies) {
      shuffle($proxies);
    }
    
    // distribute proxies
    if($total_scanners > 0) {
      $proxies_per_instance = floor($total_proxies / $total_scanners);
      
      // threshold
      if(!$proxies_to_file && $proxies_per_instance > 10) {
        $proxies_per_instance = 10;
      }
    }
  } 
  
  $content_servers = template_header($screen_servers);
  $content_scanners = template_header($screen_scanners);
  $content_dump_sp = template_header($screen_dump_sp);
  $content_pogo_captcha = "";

	$curr_server = 0;
	$curr_scanner = 0;
	$error = false;
  $dump_sp_required = false;
	
	foreach($instances as $instance) {
		$enabled = trim($instance[0]);
		$modes = trim($instance[1]);
		$location = trim($instance[2]);
		$name = trim($instance[3]);
		$st = trim($instance[4]);
		$sd = trim($instance[5]);
		$num_workers = trim($instance[6]);
		$num_accs = trim($instance[7]);
		$webhook = trim($instance[8]);
		
		// sanitize names
    $clean_name = str_replace(" ", "_", strtolower($name));
    
    // separate server instances
    if(preg_match("/-os/i", $modes)) {
      $curr_server++;

      $comment = "# $curr_server $name | $modes  --------------------";
      $message = "echo \# Server $curr_server: $name $modes";
      $command = "screen -S \"$screen_servers\" -x -X screen bash -c 'python $path_base/$path_pogomap/runserver.py $modes -sn \"0$curr_server - $name\" -l \"$location\"";
      
      if($log_messages) {
        $command .= " -v $log_filename";
      }
      
      $command .= "; exec bash'";
      
      $content_servers .= $comment."\n";
      $content_servers .= $command."\n";
      $content_servers .= $message."\n\n";
      
      if($curr_server < $total_servers) {
        $content_servers .= "timer 3\n";
			}
      
      continue;
		}

		// increment scanner number so it matches screen's window number
		$curr_scanner++;
    
    if($curr_account+$num_accs > $total_accounts) {
      $response["message"] .= "Insufficient accounts, script stopped at instance #$curr_scanner: $name\n";
			break;
		}
  
		// write output script
		$comment = "# $curr_scanner $name | $modes | st: $st | sd: $sd | w: $num_workers | accs: $num_accs --------------------";
		
		$message = "echo \# $curr_scanner $name $modes -st $st -sd $sd -w $num_workers -accs $num_accs";
  
		// spawnpoint clustering
		if($sp_clustering && preg_match("/-ss/i", $modes)) {
      
      $dump_sp_required = true;
			
			// pick first account available and let it be reused later
			$dump_user = trim($accounts[$curr_account][1]);
			$dump_pass = trim($accounts[$curr_account][2]);
			
			$output_spawns = "$path_base/$path_spawnpoints/spawns-$curr_scanner.json";
			$output_compressed = "$path_base/$path_spawnpoints/compressed-$curr_scanner.json";
			
			$command_clustering = "python $path_base/$path_ssclustering/cluster.py $output_spawns -os $output_compressed -r 70 -t 180";
			  
			$command_dump = "screen -S \"$screen_dump_sp\" -x -X screen bash -c 'timeout -sHUP 60s python $path_base/$path_pogomap/runserver.py -P 5010 -l \"$location\" -st $st -u $dump_user -p $dump_pass -ss $output_spawns --dump-spawnpoints; $command_clustering >> $log_dump'";
			 
      $content_dump_sp .= $comment."\n";
      $content_dump_sp .= $command_dump."\n\n";
      $content_dump_sp .= $message."\n\n";
			
			if($curr_scanner < $total_scanners) {
        $content_dump_sp .= "timer 5\n\n";
			}
			
			// append compressed spawnpoints file to -ss flag
			$modes = preg_replace('/-ss/i', "-ss $output_compressed", $modes);
		}
  
		$command = "screen -S \"$screen_scanners\" -x -X screen bash -c 'python $path_base/$path_pogomap/runserver.py $modes -sn \"$curr_scanner - $name\" -l \"$location\"";
		
		// disable db cleanup cycle if instance is not "only-server"
		if(!preg_match("/-os/i", $modes)) {
			$command .= " -st $st -sd $sd --disable-clean";
		}
		
		// number of workers (only useful if num workers < num accs)
		if(is_numeric($num_workers) && $num_workers > 0 && $num_workers < $num_accs) {
			$command .= " -w $num_workers";
			
      
      // account rotation
			if($num_accs >= $num_workers*2) {
			  // -asi: seconds for accounts to search before switching to a new account. 0 to disable.
			  // -ari: Seconds for accounts to rest when they fail or are switched out. 0 to disable.
			  $command .= " -asi $account_search_interval -ari $account_rest_interval";
			}
		}
		
    if($num_accs != 0) {
      // output accounts for each instance in separate file
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
        $output_accounts = "$path_accounts/accounts-$curr_scanner-$clean_name.csv";
        $zip->addFromString($output_accounts, $content_accounts);
        
        // pogo captcha content
        $content_pogo_captcha .= "python pogo-captcha.py -ac $output_accounts -l \"".str_replace(" ", ",", $location)."\"\n";
        
        $command .= " -ac $path_base/$output_accounts";
        
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
    
		if(isset($webhook) && !empty($webhook)) {
      $webhooks = explode(" ", $webhook);
      
      $command .= " -wh";
      
      foreach($webhooks as $webhook) {
        $webhook = trim($webhook);
        
        if(array_key_exists($webhook, $alarms)) {
          $alarm_wh_address = $alarms[$webhook][2];
          $alarm_wh_port = $alarms[$webhook][3];
          
          $command .= " http://$alarm_wh_address:$alarm_wh_port";
        }
      }
		}
    
    if($enable_proxies && $proxies_per_instance > 0) {
      $command .= " -me 10 -pxt 1 -pxr 3600 -pxo round";
      
      if($proxies_to_file) {
        $content_proxies = "";
        
        for($i=0; $i < $proxies_per_instance; $i++) {
          $ip_port = $proxies[$curr_proxy];
          
          $content_proxies .= "socks5://$ip_port\n";
          $curr_proxy++;
        }
        
        $output_proxies = "$path_proxies/proxies-$curr_scanner-$clean_name.txt";
        $zip->addFromString($output_proxies, $content_proxies);
        
        $command .= " -pxf $path_base/$output_proxies";
        
      } else {
        for($i=0; $i < $proxies_per_instance; $i++) {
          $ip_port = $proxies[$curr_proxy];
          
          $command .= "-px socks5://$ip_port";
          $curr_proxy++;
        }
      }
    }
    
		if($log_messages) {
      $command .= " -v $log_filename";
    }
    
		$command .= "; exec bash'";
    
    $content_scanners .= $comment."\n";
    $content_scanners .= $command."\n";
    $content_scanners .= $message."\n\n";
    
    if($curr_scanner < $total_instances) {
      // X*#workers+1 seconds of sleep between each instance launched
      $sleeptime = ($time_delay_worker * $num_workers) + 1;
      $content_scanners .= "timer $sleeptime\n";
    }
		

		if($curr_scanner >= $max_instances) {
      $response["message"] .= "Warning: instance cutoff reached at $max_instances\n";
      break;
    }
  }
  
  // finalize dump-spawnpoints script
  $content_dump_sp .= "echo Compressing spawnpoints...\n";
  $content_dump_sp .= "timer 60\n";
  $content_dump_sp .= "screen -X -S \"$screen_dump_sp\" quit\n";
  
  // output servers script
  if($curr_server > 0) {
    $zip->addFromString($output_servers, $content_servers);
    
    $shutdown_servers = "screen -X -S \"$screen_servers\" quit\n";
    $shutdown_servers .= "echo Screen session \"$screen_servers\" terminated.\n";
    $zip->addFromString("shutdown-servers.sh", $shutdown_servers);
  }
  
  // output scanners script
  if($curr_scanner > 0) {
    $zip->addFromString($output_scanners, $content_scanners);
    
    // output pogo captcha
    if($accounts_to_file) {
      $zip->addFromString($output_pogo_captcha, $content_pogo_captcha);
    }
    
    $shutdown_scanners = "screen -X -S \"$screen_scanners\" quit\n";
    $shutdown_scanners .= "echo Screen session \"$screen_scanners\" terminated.\n";
    $zip->addFromString("shutdown-scanners.sh", $shutdown_scanners);
  }
  
  // output dump spawnpoints script
  if($dump_sp_required) {
    $zip->addFromString($output_dump_sp, $content_dump_sp);
  }

  $response["message"] .= "Launch script created: ".$zip->numFiles." files output.";
  $response["file"] = $tmp_id;
  
  // include mysql database reset script
  $script_content = template_mysql_reset($mysql_username, $mysql_password, $mysql_database, $mysql_host);

  $zip->addFromString($MYSQL_RESET_SCRIPT_NAME, $script_content);

  $zip->close();
  
  // send response
  exit(json_encode($response));

} else {
  
  print(template_mysql_reset("test", "pw", "pogomap", "localhost"));

  //echo phpinfo();
  
}
?>