<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");
use \Kickback\Config\ServiceCredentials;
$kk_servername = ServiceCredentials::get("sql_server_host");
$kk_username   = ServiceCredentials::get("sql_username");
$kk_password   = ServiceCredentials::get("sql_password");
$kk_database   = ServiceCredentials::get("sql_server_db_name");

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
