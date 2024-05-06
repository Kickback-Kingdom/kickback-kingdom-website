<?php

$GLOBALS['versionInfo'] = [
    "0.0.1" => "0-0-1-update-writ-of-passage"
];

// Automatically sets the latest version based on the array
$GLOBALS['versionNumbers'] = array_keys($GLOBALS['versionInfo']);
$GLOBALS['currentVersion'] = $GLOBALS['versionNumbers'][0];


?>
