<?php
require(__DIR__."/../../engine/engine.php");

OnlyGET();

if (isset($_GET["id"])) {


    $id = $_GET["id"];

    return GetItemInformation($id);

}
else{
    return new Kickback\Models\Response(false, "No item id provided", null);
}

?>