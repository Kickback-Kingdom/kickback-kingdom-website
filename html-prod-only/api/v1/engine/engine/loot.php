<?php

function GivePrestigeToken($account_id)
{
    return GiveLoot($account_id, 3);
}

function GiveBadge($account_id,  $item_id)
{
    return GiveLoot($account_id, $item_id);
}

function GiveRaffleTicket($account_id)
{
    return GiveLoot($account_id, 4);
}

function GiveWritOfPassage($account_id)
{
    return GiveLoot($account_id, 14);
}

function GiveMerchantGuildShare($account_id, $date) {
    return GiveLoot($account_id, 16, $date);
}

function GiveLoot($account_id, $item_id, $dateObtained = null)
{
    // Establishing connection
    $conn = $GLOBALS["conn"];
    
    // Escaping input values
    $account_id = mysqli_real_escape_string($conn, $account_id);
    $item_id = mysqli_real_escape_string($conn, $item_id);
    
    // Checking if dateObtained is null
    if ($dateObtained === null) {
        $dateObtained = date('Y-m-d H:i:s');  // Set to current date and time
    } else {
        $dateObtained = mysqli_real_escape_string($conn, $dateObtained);
    }
    
    // SQL query
    $sql = "insert into loot (item_id, opened, account_id, dateObtained) values ($item_id, 0, $account_id, '$dateObtained')";
    
    // Executing query
    $result = mysqli_query($conn, $sql);
    
    if ($result === TRUE) {
        return (new APIResponse(true, "Successfully gave loot to account", null));
    } else {
        return (new APIResponse(false, "Failed to award account with error: ".GetSQLError(), null));
    }
}

function ConvertIntoItemInformation($item)
{
    $itemInfo = [];

    $itemInfo["Id"] = -1;
    if (isset($item["Id"]))
    {
        $itemInfo["Id"] = $item["Id"];
    }
    else if (isset($item["item_id"]))
    {
        $itemInfo["Id"] = $item["item_id"];
    }

    $itemInfo["type"] = $item["type"];
    $itemInfo["rarity"] = $item["rarity"];
    $itemInfo["desc"] = $item["desc"];
    $itemInfo["name"] = $item["name"];
    $itemInfo["artist"] = $item["artist"];
    $itemInfo["nominator"] = $item["nominator"];
    $itemInfo["equipable"] = $item["equipable"];
    $itemInfo["equipment_slot"] = $item["equipment_slot"];
    $itemInfo["next_loot_id"] = $item["next_loot_id"];
    $itemInfo["date_created"] = date_format(date_create($item["DateCreated"]),"M j, Y");
    
    $itemInfo["image"] = null;
    if (isset($item["large_image"]))
    {
        $itemInfo["image"] = $item["large_image"];
    }
    else if (isset($item["BigImgPath"]))
    {
        $itemInfo["image"] = $item["BigImgPath"];
    }

    return $itemInfo;
}

function GetItemInformation($item_id) {
     // Prepare SQL statement
     $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM item WHERE Id = ?");

     mysqli_stmt_bind_param($stmt, "i", $item_id);
 
     // Execute the SQL statement
     mysqli_stmt_execute($stmt);
 
     // Get the result of the SQL query
     $result = mysqli_stmt_get_result($stmt);
 
     $num_rows = mysqli_num_rows($result);
     if ($num_rows === 0)
     {
         // Free the statement
         mysqli_stmt_close($stmt);
 
         return (new APIResponse(false, "Couldn't find an item with that Id", null));
         
     }
     else
     {
         $row = mysqli_fetch_assoc($result);
 
         // Free the statement
         mysqli_stmt_close($stmt);
         
         return (new APIResponse(true, "Item information.",  ConvertIntoItemInformation($row) ));
     }
}
?>