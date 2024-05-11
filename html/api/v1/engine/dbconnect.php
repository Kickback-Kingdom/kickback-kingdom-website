<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../..")) . "/Kickback/init.php");

// Create connection
$GLOBALS["conn"] = Kickback\Services\Database::getConnection();

// Check connection
if ($GLOBALS["conn"]->connect_error) {
  die("Connection failed: " . $GLOBALS["conn"]->connect_error);
}
?>
