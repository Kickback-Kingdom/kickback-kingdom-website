<?php

$resp = require(__DIR__."/../engine/lich/get-deck.php");

echo JSON_ENCODE($resp->data);

?>