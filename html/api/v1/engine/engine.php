<?php

session_start();

require_once("dbconnect.php");
require_once("functions.php");
require_once("obj.php");

Kickback\Utilities\FormToken::generateFormToken();

?>
