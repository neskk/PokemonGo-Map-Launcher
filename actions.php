<?php
header('Content-Type: text/plain');

$path_pogomap = "./PokemonGo-Map";
$path_pokealarm = "./PokeAlarm";
$path_ssclustering = $path_pogomap."/Tools/Spawnpoint-Clustering";
$path_spawnpoints = "./spawnpoints";
$path_accounts = "./accounts";

$screen_scanners = "scanners";
$screen_alarms = "alarms";
$screen_dump_sp = "dump-sp";

$mysql_host = "localhost";
$mysql_database = "pogomap";
$mysql_username = "pogomap";
$mysql_password = "";


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

// bash timer function
$timer_func = '#!/bin/bash
function timer() {
  echo "Pausing for $1 seconds..."
  i=0
  while [ $i -lt $1 ]
  do
    echo "$i / $1 sec"
    let "i+=1"
    sleep 1
  done
}
';

function start_screen($session_name) {
  $output = '
if screen -list | grep -q "'.$session_name.'"; then
  echo Error: a screen session \"'.$session_name.'\" is already open!
  sleep 0.5
  echo Please terminate session \"'.$session_name.'\"  before launching.
  sleep 3
  exit
fi

screen -S "'.$session_name.'" -m -d
sleep 1
';
  return $output;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	print_r($_POST);
	echo "<br>\n";
  print_r($_FILES);
	
	if(isset($_POST["path-pogomap"])) {
		$path_pogomap = trim($_POST["path-pogomap"]);
	}
	if(isset($_POST["path-pokealarm"])) {
		$path_pokealarm = trim($_POST["path-pokealarm"]);
	}
	if(isset($_POST["path-spclustering"])) {
		$path_spclustering = trim($_POST["path-spclustering"]);
	}
	if(isset($_POST["path-spawnpoints"])) {
		$path_spawnpoints = trim($_POST["path-spawnpoints"]);
	}
	if(isset($_POST["path-accounts"])) {
		$path_accounts = trim($_POST["path-accounts"]);
	}
	if(isset($_POST["screen-scanners"])) {
		$screen_scanners = trim($_POST["screen-scanners"]);
	}
	if(isset($_POST["screen-alarms"])) {
		$screen_alarms = trim($_POST["screen-alarms"]);
	}
	if(isset($_POST["screen-dump-sp"])) {
		$screen_dump_sp = trim($_POST["screen-dump-sp"]);
	}
	if(isset($_POST["mysql-host"])) {
		$mysql_host = trim($_POST["mysql-host"]);
	}
	if(isset($_POST["mysql-database"])) {
		$mysql_database = trim($_POST["mysql-database"]);
	}
	if(isset($_POST["mysql-username"])) {
		$mysql_username = trim($_POST["mysql-username"]);
	}
	if(isset($_POST["mysql-password"])) {
		$mysql_password = trim($_POST["mysql-password"]);
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
	
	if(isset($_POST["output-scanners"])) {
		$output_scanners = trim($_POST["output-scanners"]);
	} else {
		$output_scanners = "launch-scanners.sh";
	}
	
	if(isset($_POST["output-alarms"])) {
		$output_alarms = trim($_POST["output-alarms"]);
	} else {
		$output_alarms = "launch-alarms.sh";
	}
	
	if(isset($_POST["output-dump-sp"])) {
		$output_dump_sp = trim($_POST["output-dump-sp"]);
	} else {
		$output_dump_sp = "dump-spawnpoints.sh";
	}
  
  if($_FILES['file-accounts']['size'] > 100000){
    die("File '".$_FILES["file-accounts"]["name"]."' exceeds maximum upload size.");
  }
  if($_FILES['file-alarms']['size'] > 100000){
    die("File '".$_FILES["file-alarms"]["name"]."' exceeds maximum upload size.");
  }
  if($_FILES['file-instances']['size'] > 100000){
    die("File '".$_FILES["file-instances"]["name"]."' exceeds maximum upload size.");
  }
	
	// read accounts
  if ($_FILES["file-accounts"]["error"] == UPLOAD_ERR_OK) {
		$file_accounts = $_FILES["file-accounts"]["tmp_name"];
		$filename_accounts = $_FILES["file-accounts"]["name"];
				
		$read_handle = fopen($file_accounts, "r") or die("Unable to open file: $filename_accounts");
		
		$accounts = array();
		$first = true;
		
		while (($line = trim(fgets($read_handle))) !== false) {
		  if($first) {
        $first = false;
        
        if(strcasecmp("service,username,password,banned", $line) == 0) {
          // skip header line
          continue;
        } else {
          exit("Data in $filename_accounts must have column headings.");
        }
		  }
		
		  // syntax: service,username,password,banned
		  $account = explode(",", $line);
		  
		  $banned = trim($account[3]);
		  
		  if(!empty($banned) && $banned > 0) {
        //echo "Debug: Skipped banned account '$account[1] : $account[2]'\n";
        continue;
		  }
		  
		  $accounts[] = $account;
		}
    
		$total_accounts = count($accounts);
		
		// close read handle
		fclose($read_handle);

	} else {
	  exit("Provide a file with accounts (syntax: service,username,password,banned)");
	}
	
	if ($_FILES["file-alarms"]["error"] == UPLOAD_ERR_OK) {
		$file_alarms = $_FILES["file-alarms"]["tmp_name"];
		$filename_alarms = $_FILES["file-alarms"]["name"];
				
		$read_handle = fopen($file_alarms, "r") or die("Unable to open file: $filename_alarms");
		
		$alarms = array();
		$first = true;
		
		while (($line = trim(fgets($read_handle))) !== false) {
		  if($first) {
        $first = false;
        
        if(strcasecmp("enabled,address,port,location,name", $line) == 0) {
          // skip header line
          continue;
        } else {
          exit("Data in $filename_alarms must have column headings.");
        }
		  }
		  
			// syntax: enabled,address,port,location,name
			$alarm = explode(",", $line);
		
			$enabled = trim($alarm[0]);
			$name = trim($alarm[4]);
			
			if(!empty($enabled) && $enabled != "1") {
				//echo "Debug: Skipped disabled alarm '$alarm[4] : $alarm[3]'\n";
				continue;
			}
			 
			$alarms[$name] = $alarm;
		}
		$total_alarms = count($alarms);
		
		// close read handle
		fclose($read_handle);

	} else {
	  exit("Provide a file with alarms (syntax: enabled,address,port,location,name)");
	}
	
	if ($_FILES["file-instances"]["error"] == UPLOAD_ERR_OK) {
		$file_instances = $_FILES["file-instances"]["tmp_name"];
		$filename_instances = $_FILES["file-instances"]["name"];
				
		$read_handle = fopen($file_instances, "r") or die("Unable to open file: $filename_instances");
		
		$instances = array();
		$first = true;
		
		while (($line = trim(fgets($read_handle))) !== false) {
		  if($first) {
        $first = false;
        
        if(strcasecmp("enabled,modes,location,name,st,sd,num_workers,num_accs,webhook", $line) == 0) {
          // skip header line
          continue;
        } else {
          exit("Data in $filename_instances must have column headings.");
        }
		  }
		  
			// syntax: enabled,modes,location,name,st,sd,num_workers,num_accs,webhook
			$instance = explode(",", $line);
		
			$enabled = trim($instance[0]);
			
			if(!empty($enabled) && $enabled != "1") {
				//echo "Debug: Skipped disabled instance '$instance[3] : $instance[2]'\n";
				continue;
			}
			 
			$instances[] = $instance;
		}
		$total_instances = count($instances);
		
		// close read handle
		fclose($read_handle);
	} else {
		exit("Provide a file with instances (syntax: enabled,modes,location,name,st,sd,num_workers,num_accs,webhook)");
	}

	// ##########################################################################
	// cleanup
	
	if (file_exists($path_spawnpoints) && is_dir($path_spawnpoints)) {
	  rrmdir($path_spawnpoints);
	  /*
	  if(!rrmdir($path_spawnpoints."/")) {
		echo "Error: Unable to clean $path_spawnpoints\n";
	  }
	  */
	}
	mkdir($path_spawnpoints, 0777, true);
	
	if (file_exists($path_accounts) && is_dir($path_accounts)) {
	  rrmdir($path_accounts);
	  /*
	  if(!rrmdir($path_spawnpoints."/")) {
		echo "Error: Unable to clean $path_spawnpoints\n";
	  }
	  */
	}
	
	mkdir($path_accounts, 0777, true);
	
	/*
	if (file_exists($log_dump)) {
	  if(!unlink($log_dump)) {
		echo "Error: Unable to clean $log_dump\n";
	  }
	}
	*/

	// ##########################################################################
	// alarms
	$output_alarms_handle = fopen($output_alarms, "w+");
	
	// write bash script header
	fwrite($output_alarms_handle, $timer_func);
	fwrite($output_alarms_handle, start_screen($screen_alarms));
	
	
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
		
		fwrite($output_alarms_handle, $comment."\n");
		fwrite($output_alarms_handle, $command."\n");
		fwrite($output_alarms_handle, $message."\n\n");
		
		// Don't include timer on last alarm
		if($curr_alarm < $total_alarms) {
			fwrite($output_alarms_handle, "timer 2\n\n");
		}
	}
	
	// close write handle
	fclose($output_alarms_handle);
	
	// ##########################################################################
	// scanners
	
	// randomize the accounts used for each instance
	shuffle($accounts);
	$curr_account = 0;

  // open output file handles
	$output_scanners_handle = fopen($output_scanners, "w+");
  $output_dump_sp_handle = fopen($output_dump_sp, "w+");
	
	// write bash script header
	fwrite($output_scanners_handle, $timer_func);
	fwrite($output_scanners_handle, start_screen($screen_scanners));

	fwrite($output_dump_sp_handle, $timer_func);
	fwrite($output_dump_sp_handle, start_screen($screen_dump_sp));

	$curr_instance = 0;
	$error = false;
	
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
  
		// write output script
		$comment = "# $curr_instance $name | $modes | st: $st | sd: $sd | w: $num_workers | accs: $num_accs --------------------";
		
		$message = "echo \# $curr_instance $name $modes -st $st -sd $sd -w $num_workers -accs $num_accs";
  
  	// ##########################################################################
		// dump spawnpoints
		
		if($sp_clustering && preg_match("/-ss/i", $modes)) {
			
			// pick first account available and let it be reused later
			$dump_user = trim($accounts[$curr_account][1]);
			$dump_pass = trim($accounts[$curr_account][2]);
			
			$output_spawns = "$path_spawnpoints/spawns-$curr_instance.json";
			$output_compressed = "$path_spawnpoints/compressed-$curr_instance.json";
			
			$command_clustering = "python $path_ssclustering/cluster.py $output_spawns -os $output_compressed -r 70 -t 180";
			  
			$command_dump = "screen -S \"$screen_dump_sp\" -x -X screen bash -c 'timeout -sHUP 60s python $path_pogomap/runserver.py -P 5010 -l \"$location\" -st $st -u $dump_user -p $dump_pass -ss $output_spawns --dump-spawnpoints; $command_clustering >> $log_dump'";
			
			fwrite($output_dump_sp_handle, $comment."\n");
			fwrite($output_dump_sp_handle, $command_dump."\n\n");
			fwrite($output_dump_sp_handle, $message."\n\n");
			
			if($curr_instance < $total_instances) {
				fwrite($output_dump_sp_handle, "timer 5\n\n");
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
			
			// TODO: parameterize from $_POST
			if($num_accs >= $num_workers*2) {
			  // -asi: seconds for accounts to search before switching to a new account. 0 to disable.
			  $asi = 8 * 60 * 60;
			  // -ari: Seconds for accounts to rest when they fail or are switched out. 0 to disable.
			  $ari = 4 * 60 * 60;
			  $command .= " -asi $asi -ari $ari";
			}
		}
		
		if($curr_account+$num_accs < $total_accounts) {
			echo "Insufficient accounts, script stopped at instance #$curr_instance: $name\n";
			break;
		}
    
    if($accounts_to_file) {
      $output_accounts = "$path_accounts/accounts-$curr_instance.csv";
      
      $output_accounts_handle = fopen($output_accounts, "w+");
      
		  // select accounts for this instance
      for($i=0; $i<$num_accs; $i++) {
        
        $service = trim($accounts[$curr_account][0]);
        $user = trim($accounts[$curr_account][1]);
        $pass = trim($accounts[$curr_account][2]);
        
        fwrite($output_accounts_handle, "$service,$user,$pass\n");
       
        $curr_account++;
      }
      
      fclose($output_accounts);
      
      $command .= " -ac $output_accounts";
      
      
    } else {
      // select accounts for this instance
      for($i=0; $i<$num_accs; $i++) {
        
        $user = trim($accounts[$curr_account][1]);
        $pass = trim($accounts[$curr_account][2]);
  
        
        $command .= " -u $user -p $pass";
        $curr_account++;
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
		

		fwrite($output_scanners_handle, $comment."\n");
		fwrite($output_scanners_handle, $command."\n");
		fwrite($output_scanners_handle, $message."\n\n");
    
    if($curr_instance < $total_instances) {
        
      // X*#workers+1 seconds of sleep between each instance launched
      $sleeptime = (4 * $num_workers) + 1;
      
      fwrite($output_scanners_handle, "timer $sleeptime\n");
    }
		

		if($curr_instance >= $max_instances) {
      echo "Worker cutoff reached: $max_instances\n";
      break;
    }
  }
	
  // finalize dump-spawnpoints script
  $dump_message = "echo Compressing spawnpoints...";
  fwrite($output_dump_sp_handle, $dump_message."\n");
  fwrite($output_dump_sp_handle, "timer 60"."\n");
  fwrite($output_dump_sp_handle, "screen -X -S \"$screen_dump_sp\" quit"."\n");
		
  // close file handles
  fclose($output_scanners_handle);
  fclose($output_dump_sp_handle);

}
?>
