<?php

require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
$kk_credentials = LoadServiceCredentialsOnce();
$kk_servername = $kk_credentials["sql_server_host"];
$kk_username   = $kk_credentials["sql_username"];
$kk_password   = $kk_credentials["sql_password"];
$kk_database   = $kk_credentials["sql_server_db_name"];
unset($kk_credentials);

// Create connection
$GLOBALS["conn"] = new mysqli($kk_servername, $kk_username, $kk_password, $kk_database);

// Check connection
if ($GLOBALS["conn"]->connect_error) {
  die("Connection failed: " . $GLOBALS["conn"]->connect_error);
}
//echo "Connected successfully";
unset($kk_username);
unset($kk_password);
unset($kk_database);
unset($kk_servername);
?>
