<?php
$file_id = $_GET["id"];

$PATH_TEMPLATES = "templates";
$PATH_TMP = "tmp";

$file = "$PATH_TMP/$file_id.zip";

if(file_exists($file)) {
   
  header('Content-Description: File Transfer');
  header('Content-Type:  application/zip');
  header('Content-Length: ' . filesize($file));
  header('Content-Disposition: attachment; filename="pogomap-launcher.zip"');
  header('Content-Transfer-Encoding: binary');
  header('Expires: 0');
  
  ob_clean();
  flush();
  
  readfile($file);
  
  ignore_user_abort(true);
  if (connection_aborted()) {
      unlink($file);
  }
  unlink($file);
  exit();
}

echo "File not found.";
?>