<?php

$session = require ("verifySession.php");

if (!$session->Success)
{
    //$GLOBALS['account'] = null;
    header("Location: /login.php");
}
else{
    
    //$GLOBALS['account'] = $session->Data;
}

return $session;
?>