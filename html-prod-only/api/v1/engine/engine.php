<?php

session_start();

$GLOBALS["kkservice"]="***REMOVED***";

require_once("dbconnect.php");
require_once("functions.php");
require_once("obj.php");

GenerateFormToken();

?>