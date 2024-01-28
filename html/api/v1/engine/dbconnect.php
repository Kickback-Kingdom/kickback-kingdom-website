<?php

require_once($_SERVER['DOCUMENT_ROOT']."/service-credentials-ini.php");
LoadServiceCredentials();

$kk_credentials =& $kickback_service_credentials;
$servername = $kk_credentials["sql_server_host"];
$username   = $kk_credentials["sql_username"];
$password   = $kk_credentials["sql_password"];
$database   = $kk_credentials["sql_server_db_name"];
unset($kk_credentials);

// Create connection
$GLOBALS["conn"] = new mysqli($servername, $username, $password, $database);

// Check connection
if ($GLOBALS["conn"]->connect_error) {
  die("Connection failed: " . $GLOBALS["conn"]->connect_error);
}
//echo "Connected successfully";
unset($username);
unset($password);
unset($database);
unset($servername);
?>
