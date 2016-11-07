<?php
header('Content-Type: text/plain');


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

print_r($_FILES);

print_r($_POST);

exit();
  
if(isset($_POST["generate-launcher"])) {
 
}

if(count($argv) < 3) {
  exit("Insufficient arguments: <accounts.csv> <alarms.csv> <instances.csv> [enable clustering] [max instances] [launch-scanners.sh] [launch-alarms.sh] [dump-spawnpoints.sh]");
}

if(isset($argv[1]) && file_exists($argv[1])) {
  $file_accounts = trim($argv[1]);
} else {
  exit("Provide a file with accounts (syntax: username,password,banned)");
}

if(isset($argv[2]) && file_exists($argv[2])) {
  $file_alarms = trim($argv[2]);
} else {
  exit("Provide a file with webhook alarms (syntax: enabled,address,port,location,name)");
}

if(isset($argv[3]) && file_exists($argv[3])) {
  $file_instances = trim($argv[3]);
} else {
  exit("Provide a file with instances (syntax: enabled,modes,location,name,st,sd,num_workers,num_accs,webhook)");
}

if(isset($argv[4]) && is_numeric($argv[4])) {
  if($argv[4] != 0) {
    $cluster_spawpoints = true;
  } else {
    $cluster_spawpoints = false;
  }
} else {
  $cluster_spawpoints = true;
}

if(isset($argv[5]) && is_numeric($argv[5])) {
  $max_instances = $argv[5];
} else {
  $max_instances = 1000000;
}

if(isset($argv[6])) {
  $output_main = trim($argv[6]);
} else {
  $output_main = "launch-scanners.sh";
}

if(isset($argv[7])) {
  $output_alarms = trim($argv[7]);
} else {
  $output_alarms = "launch-alarms.sh";
}

if(isset($argv[8])) {
  $output_main = trim($argv[8]);
} else {
  $output_dump = "dump-spawnpoints.sh";
}

// initialization - sanitize
if (file_exists($path_spawnpoints) && is_dir($path_spawnpoints)) {
  rrmdir($path_spawnpoints);
  /*
  if(!rrmdir($path_spawnpoints."/")) {
    echo "Error: Unable to clean $path_spawnpoints\n";
  }
  */
}
mkdir($path_spawnpoints, 0777, true);

if (file_exists($log_dump)) {
  if(!unlink($log_dump)) {
    echo "Error: Unable to clean $log_dump\n";
  }
}
  

// open file output handles
$output_main_handle = fopen($output_main, "w+");
$output_alarms_handle = fopen($output_alarms, "w+");
$output_dump_handle = fopen($output_dump, "w+");

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

// write bash script header
fwrite($output_main_handle, $timer_func);
fwrite($output_main_handle, start_screen($screen_session));

// write bash script header
fwrite($output_alarms_handle, $timer_func);
fwrite($output_alarms_handle, start_screen($screen_session_alarms));

// write bash script header
fwrite($output_dump_handle, $timer_func);
fwrite($output_dump_handle, start_screen($screen_session_dump));

// read accounts
$read_handle = fopen($file_accounts, "r") or die("Unable to open file: $file_accounts");

//$contents = fread($read_handle, filesize($filename));
$accounts = array();
$first = true;

while (($line = fgets($read_handle)) !== false) {
  if($first) {
    $first = false;
    // skip header line
    continue;
  }

  // syntax: service,username,password,banned
  $account = explode(",", $line);
  
  $banned = trim($account[3]);
  
  if(!empty($banned) && $banned > 0) {
    //echo "Debug: Skipped banned account $account[0] : $account[1]\n";
    continue;
  }
  
  $accounts[] = $account;
}
$total_accounts = count($accounts);

// close read handle
fclose($read_handle);

// randomize the accounts used for each instance
shuffle($accounts);
$curr_account = 0;


// read alarms information
$read_handle = fopen($file_alarms, "r") or die("Unable to open file: $file_alarms");

$alarms = array();
$first = true;
$curr_alarm = 0;

while (($line = fgets($read_handle)) !== false) {
  if($first) {
    $first = false;
    // skip header line
    continue;
  }

  // syntax: enabled,address,port,location,name
  $alarm = explode(",", $line);
    
  $a_enabled = trim($alarm[0]);
  $a_address = trim($alarm[1]);
  $a_port = trim($alarm[2]);
  $a_location = trim($alarm[3]);
  $a_name = trim($alarm[4]);
  
  if($a_enabled != "1") {
    // TODO: display info 
    continue;
  }
  
  $curr_alarm++;
  
  $alarms[$a_name] = $alarm;
  
  $comment = "# $curr_alarm $a_name -host $a_address:$a_port -loc $a_location --------------------";
  
  $message = "echo \# $curr_alarm $a_name -host $a_address:$a_port -loc $a_location";
   
  // command to output
  $command = "screen -S \"$screen_session_alarms\" -x -X screen bash -c 'python $path_pokealarm/runwebhook.py -P $a_port -c alarms-$a_name.json";
  
  if(isset($a_location) && !empty($a_location)) {
      $command .= " -l \"$a_location\"";
  }
  
  $command .= "; exec bash'";
  
  $timer = "timer 2";
  
  fwrite($output_alarms_handle, $comment."\n");
  fwrite($output_alarms_handle, $command."\n");
  fwrite($output_alarms_handle, $message."\n\n");
  fwrite($output_alarms_handle, $timer."\n");
  
}
// close read handle
fclose($read_handle);

// close write handle
fclose($output_alarms_handle);

// read instances information
$read_handle = fopen($file_instances, "r") or die("Unable to open file: $file_instances");

$first = true;
$curr_instance = 0;

$error = false;

while (($line = fgets($read_handle)) !== false) {

  if($first) {
    $first = false;
    // skip header line
    continue;
  }

  // syntax: enabled,modes,location,name,st,sd,num_workers,num_accs,webhook
  $settings = explode(",", $line);
  
  $w_enabled = trim($settings[0]);
  $w_modes = trim($settings[1]);
  $w_location = trim($settings[2]);
  $w_name = trim($settings[3]);
  $w_st = trim($settings[4]);
  $w_sd = trim($settings[5]);
  $w_num_workers = trim($settings[6]);
  $w_num_accs = trim($settings[7]);
  $w_webhook = trim($settings[8]);
  
  if($w_enabled != "1") {
    continue;
  }
  
  // increment worker number so it matches screen's window number
  $curr_instance++;
  
  // write output script
  $comment = "# $curr_instance $w_name | $w_modes | st: $w_st | sd: $w_sd | w: $w_num_workers | accs: $w_num_accs --------------------";
  
  $message = "echo \# $curr_instance $w_name $w_modes -st $w_st -sd $w_sd -w $w_num_workers -accs $w_num_accs";
  
  // DUMP SPAWNPOINTS
  if($cluster_spawpoints && preg_match("/-ss/i", $w_modes)) {
    // Pick first account available and let it be reused later
    $dump_user = trim($accounts[$curr_account][1]);
    $dump_pass = trim($accounts[$curr_account][2]);
    
    $output_spawns = "$path_spawnpoints/spawns-$curr_instance.json";
    $output_compressed = "$path_spawnpoints/compressed-$curr_instance.json";
    
    $command_clustering = "python $path_ssclustering/cluster.py $output_spawns -os $output_compressed -r 70 -t 180";
      
    $command_dump = "screen -S \"$screen_session_dump\" -x -X screen bash -c 'timeout -sHUP 60s python $path_pogomap/runserver.py -P 5010 -l \"$w_location\" -st $w_st -u $dump_user -p $dump_pass -ss $output_spawns --dump-spawnpoints; $command_clustering >> $log_dump'";
    
    fwrite($output_dump_handle, $comment."\n");
    fwrite($output_dump_handle, $command_dump."\n\n");
    //fwrite($output_dump_handle, $command_clustering."\n\n");
    fwrite($output_dump_handle, $message."\n\n");
    fwrite($output_dump_handle, "timer 5\n\n");
    
    // append compressed spawnpoints file to -ss flag
    $w_modes = preg_replace('/-ss/i', "-ss $output_compressed", $w_modes);
  }
  
  // command to output
  $command = "screen -S \"$screen_session\" -x -X screen bash -c 'python $path_pogomap/runserver.py $w_modes -sn \"$w_name\" -l \"$w_location\"";
  
  if(!preg_match("/-os/i", $w_modes)) {
    $command .= " -st $w_st -sd $w_sd --disable-clean";
  }
  
  // number of workers (only useful if num workers < num accs)
  if(is_numeric($w_num_workers) && $w_num_workers > 0 && $w_num_workers < $w_num_accs) {
    $command .= " -w $w_num_workers";
    
    if($w_num_accs >= $w_num_workers*2) {
      // -asi: seconds for accounts to search before switching to a new account. 0 to disable.
      $asi = 8 * 60 * 60;
      // -ari: Seconds for accounts to rest when they fail or are switched out. 0 to disable.
      $ari = 4 * 60 * 60;
      $command .= " -asi $asi -ari $ari";
    }
  }
  
  // select accounts for this instance
  for($i=0; $i<$w_num_accs; $i++) {
    if($curr_account >= $total_accounts) {
      $error = true;
      break;
    }
    
    $a_user = trim($accounts[$curr_account][1]);
    $a_pass = trim($accounts[$curr_account][2]);
    
    $command .= " -u $a_user -p $a_pass";
    $curr_account++;
  }
  
  /*
  if(isset($w_webhook) && !empty($w_webhook)) {
    $w_webhooks = explode(" ", $w_webhook);
    
    $command .= " -wh";
    
    foreach($w_webhooks as $webhook) {
      $command .= " http://$webhook";
    }
  }
  */
  if(isset($w_webhook) && !empty($w_webhook)) {
    $w_webhooks = explode(" ", $w_webhook);
    
    $command .= " -wh";
    
    foreach($w_webhooks as $webhook) {
      $webhook = trim($webhook);
      
      if(array_key_exists($webhook, $alarms)) {
        $alarm_wh_address = $alarms[$webhook][1];
        $alarm_wh_port = $alarms[$webhook][2];
        
        $command .= " http://$alarm_wh_address:$alarm_wh_port";
      }
    }
  }
  $command .= "; exec bash'";


  // X*#workers+1 seconds of sleep between each instance launched
  $sleeptime = (4 * $w_num_workers)+1;
  $timer = "timer $sleeptime";
  
  fwrite($output_main_handle, $comment."\n");
  fwrite($output_main_handle, $command."\n");
  fwrite($output_main_handle, $message."\n\n");
  fwrite($output_main_handle, $timer."\n");
  
  if($error) {
    echo "Insufficient accounts, script didn't finish for all locations\n";
    break;
  }
  
  if($curr_instance >= $max_instances) {
    echo "Worker cutoff reached: $max_instances\n";
    break;
  }
}

$dump_message = "echo Compressing spawnpoints...";
fwrite($output_dump_handle, $dump_message."\n");
fwrite($output_dump_handle, "timer 60"."\n");
fwrite($output_dump_handle, "screen -X -S \"$screen_session_dump\" quit"."\n");

// close file handles
fclose($read_handle);
fclose($output_main_handle);
fclose($output_dump_handle);
?>
