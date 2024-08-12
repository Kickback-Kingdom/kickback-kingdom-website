<?php
require("../engine/engine.php");

OnlyGet();

$sql = 'SELECT Name, Description, PublicKey FROM service;';
$result = mysqli_query($GLOBALS["conn"],$sql);

$array = array();
while ($row = mysqli_fetch_assoc($result)) {
    array_push($array,$row);
}
return (new Kickback\Backend\Models\Response(true, "Here are a list of available services", $array ));

?>
