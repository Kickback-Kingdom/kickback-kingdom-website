<?php

$session = require ("verifySession.php");

if (!$session->Success)
{
    header("Location: /login.php");
}

return $session;
?>