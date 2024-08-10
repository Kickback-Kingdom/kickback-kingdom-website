<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../..")) . "/Kickback/init.php");
$conn = Kickback\Services\Database::getConnection();
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
unset($conn);
?>
