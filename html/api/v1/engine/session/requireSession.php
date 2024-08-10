<?php

$session = require ("verifySession.php");

if (!$session->success)
{
    header("Location: /login.php");
}

return $session;
?>