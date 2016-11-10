<?php
//header('Content-Type: text/plain');

$PATH_TEMPLATES = "templates";
$PATH_TMP = "tmp";

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
  
  $filename = "$PATH_TEMPLATES/header";
  $read_handle = fopen($filename, "r");
  $format = fread($read_handle, filesize($filename));
  fclose($read_handle);
  
  return sprintf($format, $screen_session, $message);
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
    $path_ssclustering = "PokemonGo-Map/Tools/Spawnpoint-Clustering";
  }
  
	if(isset($_POST["path-spawnpoints"]) && !empty($_POST["path-pogomap"])) {
		$path_spawnpoints = trim($_POST["path-spawnpoints"]);
	} else {
    $path_spawnpoints = "spawnpoints";
  }
  
	if(isset($_POST["path-accounts"]) && !empty($_POST["path-pogomap"])) {
		$path_accounts = trim($_POST["path-accounts"]);
	} else {
    $path_accounts = "accounts";
  }
  
	if(isset($_POST["screen-scanners"]) && !empty($_POST["path-pogomap"])) {
		$screen_scanners = trim($_POST["screen-scanners"]);
	} else {
    $screen_scanners = "scanners";
  }
  
	if(isset($_POST["screen-alarms"]) && !empty($_POST["path-pogomap"])) {
		$screen_alarms = trim($_POST["screen-alarms"]);
	} else {
    $screen_alarms = "alarms";
  }
  
	if(isset($_POST["screen-dump-sp"]) && !empty($_POST["path-pogomap"])) {
		$screen_dump_sp = trim($_POST["screen-dump-sp"]);
	} else {
    $screen_dump_sp = "dump-sp";
  }
  
	if(isset($_POST["mysql-host"]) && !empty($_POST["path-pogomap"])) {
		$mysql_host = trim($_POST["mysql-host"]);
	} else {
    $mysql_host = "localhost";
  }
  
	if(isset($_POST["mysql-database"]) && !empty($_POST["path-pogomap"])) {
		$mysql_database = trim($_POST["mysql-database"]);
	} else {
    $mysql_database = "pogomap";
  }
  
	if(isset($_POST["mysql-username"]) && !empty($_POST["path-pogomap"])) {
		$mysql_username = trim($_POST["mysql-username"]);
	} else {
    $mysql_username = "pogomap";
  }
  
	if(isset($_POST["mysql-password"]) && !empty($_POST["path-pogomap"])) {
		$mysql_password = trim($_POST["mysql-password"]);
	} else {
    $mysql_password = "";
  }
	
	if(isset($_POST["max-instances"]) && is_numeric($_POST["max-instances"]) && $_POST["max-instances"] > 1) {
		$max_instances = $_POST["max-instances"];
	} else {
		$max_instances = 1000000;
	}
	
	if(isset($_POST["sp-clustering"]) && $_POST["sp-clustering"] == "on") {
		$sp_clustering = true;
	} else {
		$sp_clustering = false;
	}
	
	if(isset($_POST["accounts-to-file"]) && $_POST["accounts-to-file"] == "on") {
		$accounts_to_file = true;
	} else {
		$accounts_to_file = false;
	}
	
	if(isset($_POST["output-scanners"]) && !empty($_POST["path-pogomap"])) {
		$output_scanners = trim($_POST["output-scanners"]);
	} else {
		$output_scanners = "launch-scanners.sh";
	}
	
	if(isset($_POST["output-alarms"]) && !empty($_POST["path-pogomap"])) {
		$output_alarms = trim($_POST["output-alarms"]);
	} else {
		$output_alarms = "launch-alarms.sh";
	}
	
	if(isset($_POST["output-dump-sp"]) && !empty($_POST["path-pogomap"])) {
		$output_dump_sp = trim($_POST["output-dump-sp"]);
	} else {
		$output_dump_sp = "dump-spawnpoints.sh";
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
      
      if(strcasecmp("enabled,address,port,location,name", trim($line)) == 0) {
        // skip header line
        continue;
      } else {
        quit("First line of alarms CSV file must be column headings: enabled,address,port,location,name");
      }
    }
    
    // syntax: enabled,address,port,location,name
    $alarm = explode(",", $line);
  
    $enabled = trim($alarm[0]);
    $name = trim($alarm[4]);

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
     
    $instances[] = $instance;
  }
  $total_instances = count($instances);
  
  // close read handle
  fclose($read_handle);
  
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
		$address = trim($alarm[1]);
		$port = trim($alarm[2]);
		$location = trim($alarm[3]);
		$name = trim($alarm[4]);
		
		// increment alarm number so it matches screen's window number
		$curr_alarm++;
		
		$comment = "# $curr_alarm $name -host $address:$port -loc $location --------------------";
		
		$message = "echo \# $curr_alarm $name -host $address:$port -loc $location";
		
		// command to output
		$command = "screen -S \"$screen_alarms\" -x -X screen bash -c 'python $path_pokealarm/runwebhook.py -P $port -c alarms-$name.json";
		
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
	// scanners

	// randomize the accounts used for each instance
	shuffle($accounts);
	$curr_account = 0;

  $content_scanners = template_header($screen_scanners);
  $content_dump_sp = template_header($screen_dump_sp);

	$curr_instance = 0;
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

		// increment instance number so it matches screen's window number
		$curr_instance++;
    
    if($curr_account+$num_accs > $total_accounts) {
      $response["message"] .= "Insufficient accounts, script stopped at instance #$curr_instance: $name\n";
			break;
		}
  
		// write output script
		$comment = "# $curr_instance $name | $modes | st: $st | sd: $sd | w: $num_workers | accs: $num_accs --------------------";
		
		$message = "echo \# $curr_instance $name $modes -st $st -sd $sd -w $num_workers -accs $num_accs";
  
		// spawnpoint clustering
		if($sp_clustering && preg_match("/-ss/i", $modes)) {
      
      $dump_sp_required = true;
			
			// pick first account available and let it be reused later
			$dump_user = trim($accounts[$curr_account][1]);
			$dump_pass = trim($accounts[$curr_account][2]);
			
			$output_spawns = "$path_spawnpoints/spawns-$curr_instance.json";
			$output_compressed = "$path_spawnpoints/compressed-$curr_instance.json";
			
			$command_clustering = "python $path_ssclustering/cluster.py $output_spawns -os $output_compressed -r 70 -t 180";
			  
			$command_dump = "screen -S \"$screen_dump_sp\" -x -X screen bash -c 'timeout -sHUP 60s python $path_pogomap/runserver.py -P 5010 -l \"$location\" -st $st -u $dump_user -p $dump_pass -ss $output_spawns --dump-spawnpoints; $command_clustering >> $log_dump'";
			 
      $content_dump_sp .= $comment."\n";
      $content_dump_sp .= $command_dump."\n\n";
      $content_dump_sp .= $message."\n\n";
			
			if($curr_instance < $total_instances) {
        $content_dump_sp .= "timer 5\n\n";
			}
			
			// append compressed spawnpoints file to -ss flag
			$modes = preg_replace('/-ss/i', "-ss $output_compressed", $modes);
		}
  
		$command = "screen -S \"$screen_scanners\" -x -X screen bash -c 'python $path_pogomap/runserver.py $modes -sn \"$name\" -l \"$location\"";
		
		// disable db cleanup cycle if instance is not "only-server"
		if(!preg_match("/-os/i", $modes)) {
			$command .= " -st $st -sd $sd --disable-clean";
		}
		
		// number of workers (only useful if num workers < num accs)
		if(is_numeric($num_workers) && $num_workers > 0 && $num_workers < $num_accs) {
			$command .= " -w $num_workers";
			
			// TODO: front-end to parameterize AccountSleepInterval / AccountRestInterval
			if($num_accs >= $num_workers*2) {
			  // -asi: seconds for accounts to search before switching to a new account. 0 to disable.
			  $asi = 8 * 60 * 60;
			  // -ari: Seconds for accounts to rest when they fail or are switched out. 0 to disable.
			  $ari = 4 * 60 * 60;
			  $command .= " -asi $asi -ari $ari";
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
        $output_accounts = "$path_accounts/accounts-$curr_instance.csv";
        $zip->addFromString($output_accounts, $content_accounts);
        
        $command .= " -ac $output_accounts";
        
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
          $alarm_wh_address = $alarms[$webhook][1];
          $alarm_wh_port = $alarms[$webhook][2];
          
          $command .= " http://$alarm_wh_address:$alarm_wh_port";
        }
      }
		}
		$command .= "; exec bash'";
    
    $content_scanners .= $comment."\n";
    $content_scanners .= $command."\n";
    $content_scanners .= $message."\n\n";
    
    if($curr_instance < $total_instances) {
      // TODO: front-end to adjust sleep times
      // X*#workers+1 seconds of sleep between each instance launched
      $sleeptime = (4 * $num_workers) + 1;
      $content_scanners .= "timer $sleeptime\n";
    }
		

		if($curr_instance >= $max_instances) {
      $response["message"] .= "Warning: instance cutoff reached at $max_instances\n";
      break;
    }
  }
  
  // finalize dump-spawnpoints script
  $content_dump_sp .= "echo Compressing spawnpoints...\n";
  $content_dump_sp .= "timer 60\n";
  $content_dump_sp .= "screen -X -S \"$screen_dump_sp\" quit\n";
  
  // output scanners script
  $zip->addFromString($output_scanners, $content_scanners);
  
  // output dump spawnpoints script
  if($dump_sp_required) {
    $zip->addFromString($output_dump_sp, $content_dump_sp);
  }

  $response["message"] .= "Launch script created: ".$zip->numFiles." files output.";
  $response["file"] = $tmp_id;

  $zip->close();
  
  // send response
  exit(json_encode($response));

} else {
  
  print(template_header("test", "testing version 1.0"));

  //echo phpinfo();
  
}
?>