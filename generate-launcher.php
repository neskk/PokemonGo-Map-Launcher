<?php
header('Content-Type: text/plain');

$file_accounts = "accounts.csv";
$file_instances = "instances.csv";
$path_runserver = "./PokemonGo-Map";

$output_main = "launch-pogotuga.sh";

$screen_session = "pogotuga";

$mysql_host = "localhost";
$mysql_database = "pogomap";
$mysql_username = "pogomap";
$mysql_password = "password";

$write_handle = fopen($output_main, "w+");

// bash script header
$header = '#!/bin/bash
echo PokémonGo-Map Instance launcher by neskk
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

if screen -list | grep -q "'.$screen_session.'"; then
    echo Error: a screen session \"'.$screen_session.'\" is already open!
    sleep 0.5
    echo Please terminate session \"'.$screen_session.'\"  before launching.
    sleep 3
    exit
fi

screen -S "'.$screen_session.'" -m -d
sleep 1

';

// write bash script header
fwrite($write_handle, $header);

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

  // syntax: username,password
  $accounts[] = explode(",", $line);
}
$total_accounts = count($accounts);

// close read handle
fclose($read_handle);

// randomize the accounts used for each instance
shuffle($accounts);
$curr_account = 0;

if(isset($argv[1]) && is_numeric($argv[1])) {
  $max_instances = $argv[1];
} else {
  $max_instances = 1000000;
}

// read worker information
$read_handle = fopen($file_instances, "r") or die("Unable to open file: $file_instances");

$first = true;
$curr_worker = 0;

$error = false;

while (($line = fgets($read_handle)) !== false) {

  if($first) {
    $first = false;
    // skip header line
    continue;
  }

  // syntax: enabled,name,modes,location,st,sd,num_accs,num_workers,webhook
  $settings = explode(",", $line);
  
  $w_enabled = trim($settings[0]);
  $w_name = trim($settings[1]);
  $w_modes = trim($settings[2]);
  $w_location = trim($settings[3]);
  $w_st = trim($settings[4]);
  $w_sd = trim($settings[5]);
  $w_num_accs = trim($settings[6]);
  $w_num_workers = trim($settings[7]);
  $w_webhook = trim($settings[8]);
  
  if($w_enabled != "1") {
    continue;
  }
  
  // increment worker number so it matches screen's window number
  $curr_worker++;
  
  // write output script
  $comment = "# $curr_worker $w_name | $w_modes | st: $w_st | sd: $w_sd | w: $w_num_workers | accs: $w_num_accs --------------------";
  
  // command to output
  $command = "screen -S \"$screen_session\" -x -X screen bash -c 'python $path_runserver/runserver.py $w_modes -sn \"$w_name\" -l \"$w_location\"";
  
  if(!preg_match("/-os/i", $w_modes)) {
    $command .= " -st $w_st -sd $w_sd";
  }
  
  if(is_numeric($w_num_workers) && $w_num_workers > 0) {
    $command .= " -w $w_num_workers";
  }
  
  for($i=0; $i<$w_num_accs; $i++) {
    if($curr_account >= $total_accounts) {
      $error = true;
      break;
    }
    
    $a_user = trim($accounts[$curr_account][0]);
    $a_pass = trim($accounts[$curr_account][1]);
    
    $command .= " -u $a_user -p $a_pass";
    $curr_account++;
  }
  
  if(isset($w_webhook) && !empty($w_webhook)) {
    $command .= " -wh http://$w_webhook";
  }
  
  $command .= "; exec bash'";

  $message = "echo \# $curr_worker $w_name $w_modes -st $w_st -sd $w_sd -w $w_num_workers -accs $w_num_accs";
  
  // X seconds of sleep per worker that will be launched, e.g. 3 workers = 15 secs sleep
  $sleeptime = 5 * $w_num_workers;
  $timer = "timer $sleeptime";
  
  fwrite($write_handle, $comment."\n");
  fwrite($write_handle, $command."\n");
  fwrite($write_handle, $message."\n\n");
  fwrite($write_handle, $timer."\n");
  

  if($error) {
    echo "Insufficient accounts, script didn't finish for all locations\n";
    break;
  }
  
  if($curr_worker >= $max_instances) {
    echo "Worker cutoff reached: $max_instances\n";
    break;
  }
}

// close file handles
fclose($read_handle);
fclose($write_handle);
?>
