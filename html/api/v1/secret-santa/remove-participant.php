<?php
$resp = require(__DIR__ . '/../engine/secret-santa/remove-participant.php');
header('Content-Type: application/json');
echo json_encode($resp);
?>
