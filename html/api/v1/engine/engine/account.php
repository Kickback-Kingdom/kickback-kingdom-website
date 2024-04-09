<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

function LoginToService($accountId, $serviceKey)
{
    if (session_status() == PHP_SESSION_ACTIVE)
    {
        // SQL statement with placeholders
        $sql = "REPLACE INTO account_sessions (SessionToken, ServiceKey, account_id, login_time) VALUES (UUID(),?, ?, utc_timestamp())";

        // Prepare the SQL statement
        $stmt = mysqli_prepare($GLOBALS["conn"], $sql);

        // Check if the statement was prepared successfully
        if ($stmt === false) {
            die(mysqli_error($GLOBALS["conn"]));
        }

        // Bind parameters to the placeholders
        mysqli_stmt_bind_param($stmt, "si", $serviceKey, $accountId);

        // Execute the statement
        $result = mysqli_stmt_execute($stmt);

        // Check the result of the query
        if ($result) {
            $affectedRows = mysqli_stmt_affected_rows($stmt);
            return ($affectedRows > 0);
        }
    }

    return false;
}

function IsLoggedIn() : bool
{
    if (!array_key_exists("sessionToken", $_SESSION)
    ||  !array_key_exists("serviceKey", $_SESSION)
    ||  !array_key_exists("account", $_SESSION) ) {
        return false;
    }

    return isset($_SESSION["sessionToken"], $_SESSION["serviceKey"], $_SESSION["account"]);
}

function IsQuestGiver()
{
    if (IsLoggedIn())
    {
        return $_SESSION["account"]["IsQuestGiver"] == 1;
    }
    else{
        return false;
    }
}

function IsArtist()
{
    if (IsLoggedIn())
    {
        return $_SESSION["account"]["IsArtist"] == 1;
    }
    else{
        return false;
    }
}


/*function IsProgressScribe()
{

    if (IsLoggedIn())
    {
        return $_SESSION["account"]["IsProgressScribe"] == 1;
    }
    else{
        return false;
    }
}*/

function IsDelegatingAccess()
{
    return isset($_SESSION['account_using_delegate_access']);
}

function IsAdmin()
{
    if (IsLoggedIn())
    {
        return $_SESSION["account"]["IsAdmin"] == 1;
    }
    else{
        return false;
    }
}

function IsMerchant()
{
    if (IsLoggedIn())
    {
        return $_SESSION["account"]["IsMerchant"] == 1;
    }
    else{
        return false;
    }
}

function IsAdventurer()
{
    return true;
}

function IsSteward() {
    return false;
}

function IsCraftsmen() {
    return false;
}

function IsMasterOrApprentice() {
    return false;
}

function Logout() : APIResponse
{
    if (!IsLoggedIn()) {
        return (new APIResponse(false, "Failed to logout because no one is logged in", null));
    }

    $conn = $GLOBALS["conn"];
    assert($conn instanceof mysqli);

    $sessionToken = $_SESSION["sessionToken"];
    $serviceKey = $_SESSION["serviceKey"];
    $query = "delete from account_sessions where SessionToken = '$sessionToken' and ServiceKey = '$serviceKey'";
    $result = $conn->query($query);
    if (false === $result) {
        return (new APIResponse(false, "Failed to log out with error: ".GetSQLError(), null));
    }

    $GLOBALS["account"] = null;
    $_SESSION["sessionToken"] = null;
    $_SESSION["account"] = null;
    return (new APIResponse(true, "Logged out successfully",null));
}

function Login($serviceKey,$email,$pwd) : APIResponse
{
    $conn = $GLOBALS["conn"];
    assert($conn instanceof mysqli);

    $serviceKey = mysqli_real_escape_string($conn, $serviceKey);
    $email = mysqli_real_escape_string($conn, $email);
    $pwd = mysqli_real_escape_string($conn, $pwd);

    $query = "SELECT account.Id, account.Password, service.Name as ServiceName FROM account inner join service on service.PublicKey = '$serviceKey' WHERE Email = '$email' and Banned = 0;";
    $result = $conn->query($query);
    if (false === $result) {
        return (new APIResponse(false, "Failed to log in with error: ".GetSQLError(), null));
    }

    $num_rows = $result->num_rows;
    if ($num_rows === 0) {
        return (new APIResponse(false, "Credentials are incorrect", null));
    }

    $row = $result->fetch_assoc();
    $serviceName = $row["ServiceName"];
    if (!password_verify($pwd, $row["Password"])) {
        return (new APIResponse(false, "Credentials are incorrect",null));
    }

    $accountId = $row["Id"];
    if (!LoginToService($accountId, $serviceKey)) {
        return (new APIResponse(false, "Failed to login", null));
    }

    $query = "SELECT * FROM account_sessions WHERE ServiceKey = '$serviceKey' and account_id = $accountId";
    $result = $conn->query($query);
    $num_rows = $result->num_rows;
    if ($num_rows === 0) {
        return (new APIResponse(false, "Failed to login", null));
    }

    $row = $result->fetch_assoc();

    $_SESSION["sessionToken"] = $row["SessionToken"];
    $_SESSION["serviceKey"] = $serviceKey;
    return GetLoginSession($serviceKey, $row["SessionToken"]);
}

function GetLoginSession($serviceKey, $sessionToken)
{
    try {
        // SQL statement with placeholders
        $sql = "SELECT account.*, service.Name as 'ServiceName', ? as SessionToken
        FROM v_account_info as account 
        LEFT JOIN service on service.PublicKey = ? 
        LEFT JOIN account_sessions on account_sessions.SessionToken = ? 
        and account_sessions.ServiceKey = service.PublicKey 
        and account_sessions.account_id = account.Id 
        WHERE account.Banned = 0 
        AND account_sessions.login_time >= (NOW() - INTERVAL 7 DAY) 
        AND service.PublicKey = ?";

        // Prepare the SQL statement
        $stmt = mysqli_prepare($GLOBALS["conn"], $sql);

        // Check if the statement was prepared successfully
        if ($stmt === false) {
            return (new APIResponse(false, mysqli_error($GLOBALS["conn"]), null));
        }

        // Bind parameters to the placeholders
        mysqli_stmt_bind_param($stmt, "ssss", $sessionToken, $serviceKey, $sessionToken, $serviceKey);

        // Execute the statement
        $result = mysqli_stmt_execute($stmt);

        // Check the result of the query
        if (!$result) {
            return (new APIResponse(false, mysqli_stmt_error($stmt), null));
        }

        // Bind result variables
        $res = mysqli_stmt_get_result($stmt);

        // Fetch the result
        if (mysqli_num_rows($res) === 0) {
            return (new APIResponse(false, "Session Token or Service Key are incorrect", null));
        } else {
            $row = mysqli_fetch_assoc($res);
            return (new APIResponse(true, "Welcome to " . $row["ServiceName"] . "! A Kickback Kingdom original.", $row));
        }
    } catch (Throwable $th) {
        return (new APIResponse(false, "Error. Check the data for more info.", $th));
    }
}

function GetAccountActivity($account_id)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_activity where account_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Couldn't find account activity for Id", null));
    }
    else
    {
        return (new APIResponse(true, "Account Activity",  $rows ));
    }
}

function GetAccountNotifications($account_id)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_notifications WHERE account_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $account_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Couldn't find notifications for Id", null));
    }
    else
    {
        return (new APIResponse(true, "Account notifications",  $rows ));
    }
}

function GetAllAccounts()
{
    // Prepare the SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_info ORDER BY level DESC, exp_current DESC");
    
    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the statement
    mysqli_stmt_close($stmt);
    
    if ($num_rows === 0) {
        return (new APIResponse(false, "Couldn't find accounts", null));
    } else {
        return (new APIResponse(true, "All accounts",  $rows ));
    }
}

function GetAccountById($id)
{
    // Prepare the SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_info WHERE Id = ?");

    // Bind the parameter to the placeholder in the SQL statement
    mysqli_stmt_bind_param($stmt, "i", $id); // "i" signifies that the parameter is an integer

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Store the result of the query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Couldn't find an account with that Id", null));
    }
    else
    {
        $row = mysqli_fetch_assoc($result);

        // Free the result & close the statement
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
    
        return (new APIResponse(true, $row["Username"]."'s information.",  $row ));
    }
}

function GetAccountByUsername($username)
{
    // Prepare SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_info WHERE Username = ?");

    // Bind the username parameters to the SQL statement
    mysqli_stmt_bind_param($stmt, "s", $username);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        // Free the statement
        mysqli_stmt_close($stmt);

        return (new APIResponse(false, "Couldn't find an account with that username", null));
    }
    else
    {
        $row = mysqli_fetch_assoc($result);

        // Free the statement
        mysqli_stmt_close($stmt);

        return (new APIResponse(true, $row["Username"]."'s information.",  $row ));
    }
}

function GetAccountByEmail($email)
{
    // Prepare SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_account_info WHERE Email = ?");

    mysqli_stmt_bind_param($stmt, "s", $email);

    // Execute the SQL statement
    mysqli_stmt_execute($stmt);

    // Get the result of the SQL query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        // Free the statement
        mysqli_stmt_close($stmt);

        return (new APIResponse(false, "Couldn't find an account with that email", null));
        
    }
    else
    {
        $row = mysqli_fetch_assoc($result);

        // Free the statement
        mysqli_stmt_close($stmt);
        
        return (new APIResponse(true, $row["Username"]."'s information.",  $row ));
    }
}

function GetBadgesByAccountId($id)
{
    // Prepare the SQL statement
    $sql = "SELECT * from v_account_badge_info where account_id = ?";

    // Initialize the prepared statement
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);

    if($stmt === false) {
        return (new APIResponse(false, "Failed to prepare the SQL statement."));
    }

    // Bind the parameter to the prepared statement
    mysqli_stmt_bind_param($stmt, "i", $id);

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Get the result
    $result = mysqli_stmt_get_result($stmt);

    // Fetch the data
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free result
    mysqli_free_result($result);

    // Close statement
    mysqli_stmt_close($stmt);
    
    return (new APIResponse(true, "Requested users badges.",  $rows ));
}

function GetSkillsByAccountId($id)
{
    
}

function GetAccountGameRanks($id)
{
    $id = mysqli_real_escape_string($GLOBALS["conn"], $id);
    $sql = "select * from v_game_elo_rank_info where account_id = $id limit 5";
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Quest Badges",  $rows ));
}

function GetWritOfPassageByAccountId($account_id)
{
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);
    $sql = "SELECT * FROM kickbackdb.v_account_writs_of_passage where account_id = $account_id";
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Couldn't find a valid writ of passage", null));
        
    }
    else{
        $row = mysqli_fetch_assoc($result);
    
        return (new APIResponse(true, "Writs of Passage",  $row ));
    }
}

function GetAccountExperience()
{
    
}

function GetAccountPrestige($accountId)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT * FROM v_prestige_info WHERE account_id_to = ?");
    mysqli_stmt_bind_param($stmt, "i", $accountId);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return (new APIResponse(true, "Account Prestige Reviews", $rows));
    } else {
        return (new APIResponse(false, "An error occurred.", []));
    }
}

function GetAccountPrestigeValue($prestigeReviews)
{
    $prestigeNet = 0;
    for ($i=0; $i < count($prestigeReviews); $i++) { 
        $review = $prestigeReviews[$i];

        if ($review['commend'] == 1)
        {
            $prestigeNet++;
        }
        else{
            $prestigeNet--;
        }
    }

    return $prestigeNet;
}

function GetPrestigeTokens($accountId)
{
    //v_account_prestige_tokens_full
    $accountId = mysqli_real_escape_string($GLOBALS["conn"], $accountId);
    $sql = "select * from v_account_prestige_tokens_full where account_Id = '$accountId'";

    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    if ($num_rows > 0)
    {
        $row = mysqli_fetch_assoc($result);
    
        if ($row != null)
            return (new APIResponse(true, "Account Prestige tokens",  $row ));
    }
    return (new APIResponse(false, "We couldn't find an account with that id", $accountId));
}

function GetMyChests($accountId)
{
    // Prepare the SQL statement
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT loot.Id, loot.rarity, CONCAT(b.Directory,'/',b.Id,'.',b.extension) as ItemImg FROM kickbackdb.v_loot_item as loot left join Media b on b.Id = loot.media_id_large where loot.account_id = ? and loot.opened = 0");

    // Bind the parameter to the placeholder in the SQL statement
    mysqli_stmt_bind_param($stmt, "i", $accountId); // "i" signifies that the parameter is an integer

    // Execute the prepared statement
    mysqli_stmt_execute($stmt);

    // Store the result of the query
    $result = mysqli_stmt_get_result($stmt);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Free the result & close the statement
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Account Chests",  $rows ));
}

function CloseChest($chestId, $accountId)
{

    $accountId = mysqli_real_escape_string($GLOBALS["conn"], $accountId);
    $chestId = mysqli_real_escape_string($GLOBALS["conn"], $chestId);
    $sql = "update loot set opened = 1 where Id = $chestId and account_id = $accountId;";//"delete from chest where account_id = $accountId and Id = $chestId";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {
        return (new APIResponse(true, "Chest closed successfully",null));
        } else {
        return (new APIResponse(false, "Failed to close chest with error: ".GetSQLError(), null));
        }
}

function GetAccountSkills($account_id)
{
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);
    $sql = "SELECT  1";

    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Account Skills",  $rows ));
}

function PrepareAccountPasswordResetCode($account_id)
{
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);
    $code = random_int(1000000, 9999999);
    $sql = "update account set pass_reset = $code where Id = $account_id;";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {
        return (new APIResponse(true, "Generated Password Reset Code",$code));
        } else {
        return (new APIResponse(false, "Failed to generate Password Reset Code! ".GetSQLError(), null));
        }
}

function UpdateAccountPassword($account_id, $pass_reset, $newPassword)
{
    
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);
    $newPassword = mysqli_real_escape_string($GLOBALS["conn"], $newPassword);
    $pass_reset = mysqli_real_escape_string($GLOBALS["conn"], $pass_reset);

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "update account set Password = '$passwordHash' where Id = $account_id and pass_reset = $pass_reset;";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {
        return (new APIResponse(true, "Password changed successfully", null));
        } else {
        return (new APIResponse(false, "Failed to change password! ".GetSQLError(), null));
        }
}

function GetUnusedAccountRaffleTicket($account_id)
{
    //SELECT Id FROM kickbackdb.loot where loot_type = 2 and account_id = 1 and durability > 0 and opened = 1 LIMIT 1
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);

    
    $sql = "SELECT loot_id FROM kickbackdb.v_raffle_tickets where raffle_id is null and account_id = $account_id LIMIT 1;";

    $result = mysqli_query($GLOBALS["conn"],$sql);
    
    
    $row = mysqli_fetch_row($result);
    $loot_id = $row[0] ?? -1;
    return (new APIResponse(true, "Unused raffle ticket id",  $loot_id ));
}

function GetTotalUnusedRaffleTickets($account_id)
{
    //SELECT count(*) FROM kickbackdb.v_raffle_tickets where raffle_id is null and account_id = 1
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);

    
    $sql = "SELECT count(*) FROM kickbackdb.v_raffle_tickets where raffle_id is null and account_id = $account_id;";

    $result = mysqli_query($GLOBALS["conn"],$sql);
    
    
    $row = mysqli_fetch_row($result);
    $unused = $row[0] ?? 0;
    return (new APIResponse(true, "Unused raffle tickets",  $unused ));
}

function GetAccountInventory($account_id)
{
    
    //SELECT count(*) FROM kickbackdb.v_raffle_tickets where raffle_id is null and account_id = 1
    $account_id = mysqli_real_escape_string($GLOBALS["conn"], $account_id);

    
    $sql = "SELECT * FROM kickbackdb.v_account_inventory_desc where account_id = $account_id;";

    $result = mysqli_query($GLOBALS["conn"],$sql);
    
    
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    return (new APIResponse(true, "Account Inventory",  $rows ));
}

function RegisterAccount($firstName, $lastName, $password, $confirm_password, $username, $email, $i_agree, $passage_quest, $passage_id)
{
    $firstName = mysqli_real_escape_string($GLOBALS["conn"], $firstName);
    $lastName = mysqli_real_escape_string($GLOBALS["conn"], $lastName);
    $password = mysqli_real_escape_string($GLOBALS["conn"], $password);
    $confirm_password = mysqli_real_escape_string($GLOBALS["conn"], $confirm_password);
    $password = mysqli_real_escape_string($GLOBALS["conn"], $password);
    $username = mysqli_real_escape_string($GLOBALS["conn"], $username);
    $passage_quest = mysqli_real_escape_string($GLOBALS["conn"], $passage_quest);
    //$passage_id = mysqli_real_escape_string($GLOBALS["conn"], $passage_id);
    $email = mysqli_real_escape_string($GLOBALS["conn"], $email);
    if (!$i_agree)
    {
        return (new APIResponse(false, "Please agree to the terms of service", null));
    }

    if ($password != $confirm_password)
    {
        return (new APIResponse(false, "Password does not match.", null));
    }

    if (strlen($password) < 8)
    {   
        return (new APIResponse(false, "Password is too short.", null));
    }

    if (strlen($firstName) < 2)
    {   
        return (new APIResponse(false, "First Name is too short.", null));
    }

    if (strlen($lastName) < 2)
    {
        return (new APIResponse(false, "Last Name is too short.", null));
    }

    if (strlen($username) < 5)
    {   
        return (new APIResponse(false, "Username is too short.", null));
    }
    
    if (strlen($username) > 15)
    {   
        return (new APIResponse(false, "Username is too long.", null));
    }

    if (strlen($email) < 5)
    {
        return (new APIResponse(false, "Email is too short.", null));
    }

    /*$refUserResp = GetAccountByUsername($refUsername);

    if (!$refUserResp->Success)
    {
        return (new APIResponse(false, "Please use a valid Referrer's Username", null));
    }*/

    $kk_crypt_key_quest_id = \Kickback\Config\ServiceCredentials::get("crypt_key_quest_id");
    $crypt = new IDCrypt($kk_crypt_key_quest_id);
    if (ContainsData($passage_id,"passage_id")->Success)
    {

        $writ_item_id = $crypt->decrypt($passage_id);
    }
    else
    {
        if (ContainsData($passage_quest,"passage_quest")->Success)
        {
            
            $writ_quest_id = $crypt->decrypt($passage_quest);
            $questResp = GetQuestById($writ_quest_id);
            if ($questResp->Success)
            {
                $quest = $questResp->Data;
                $writResp = GetWritOfPassageByAccountId($quest['host_id']);
                if ($writResp->Success)
                {
                    $writ = $writResp->Data;
                    $writ_item_id = $writ['next_item_id'];
                }
                else
                {
                    return (new APIResponse(false, "The host of the quest you are joining ran out of Writs of Passage. Please contact ".$quest['host_name'].'.', null));
                }
            }
            else
            {
                return (new APIResponse(false, "We couldn't find the quest associated with your Writ of Passage.",$passage_quest."|".$writ_quest_id));
            }
        }
        else
        {
            return (new APIResponse(false, "Please provide a Writ of Passage.", null));
        }
    }
    
    if (!ContainsData($writ_item_id,"writ_item_id")->Success)
    {
        return (new APIResponse(false, "The host of the quest you are joining ran out of Writs of Passage. Please contact ".$quest['host_name'].'.', null));
    }


    $userResp = GetAccountByUsername($username);

    
    if ($userResp->Success)
    {
        return (new APIResponse(false, "Your desired username already exists.", null));
    }

    $emailResp = GetAccountByEmail($email);

    if ($emailResp->Success)
    {
        return (new APIResponse(false, "Account already exists. Please register with a different email.", null));
    }


    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO account (Email, Password, FirstName, LastName, Username, passage_id) VALUES ('$email', '$passwordHash', '$firstName', '$lastName','$username','$writ_item_id')";
    $result = mysqli_query($GLOBALS["conn"],$sql);
    if ($result === TRUE) {

        $kk_service_key = \Kickback\Config\ServiceCredentials::get("kk_service_key");
        $loginResp = Login($kk_service_key,$email,$password);
        $login = $loginResp->Data;

        /*$timestamp = strtotime("2023-06-01");
        
        // Compare the timestamp to the current time
        if ($timestamp < time()) {
            echo "The end time has passed.";

            //GiveBadge();
        } else {
            echo "The end time has not passed.";
            

            //GiveBadge($login["Id"],1);
            //GiveRaffleTicket($login["Id"]);
        }*/

        GiveWritOfPassage($login["Id"]);

        DiscordWebHook(GetNewcomerIntroduction($username));

        return (new APIResponse(true, "Account created successfully",$login));
    } 
    else 
    {
        return (new APIResponse(false, "Failed to register account with error: ".GetSQLError(), null));
    }
}

function GetAccountProfilePicture($account)
{
    if (isset($account["avatar_media"]))
    {
        return $account["avatar_media"];
    }
    else
    {
        if (isset($account["AccountId"]))
            $accountId = $account["AccountId"];
        else
            $accountId = $account["Id"];
        return "profiles/young-".GetAccountDefaultProfilePicture($accountId).".jpg";
    }
}

function GetPrestigeProfilePicture($prestige)
{
    if ($prestige["account_from_avatar_media"] == null)
    {
        return "profiles/young-".GetAccountDefaultProfilePicture($prestige["account_id_from"]).".jpg";
    }
    else
    {
        return $prestige["account_from_avatar_media"];
    }
}

function GetAccountDefaultProfilePicture($accountId)
{
    $total = 34;
    $hash = md5($accountId);
    $hash_number = hexdec(substr($hash, 0, 8));
    $random_number = $hash_number % $total + 1;
    $image_id = $random_number;
    return $image_id;
}

function GetAccountDefaultQuote($accountId) {
    $adventurerQuotes = [
        "By the grace of the gods, I shall venture forth and carve my own legend!",
        "With sword in hand and courage in my heart, I'll conquer any challenge that comes my way.",
        "Though my steps may falter, I'll keep moving forward. For every journey begins with a single stride.",
        "In the face of danger, I'll prove my mettle and emerge victorious. A hero in the making!",
        "From humble beginnings, I'll rise above and become a knight worthy of tales.",
        "The unknown beckons, and I answer its call. Adventure awaits!",
        "A true warrior is forged through adversity. I'll overcome any trial and grow stronger.",
        "In this vast realm of mysteries, I'll uncover secrets and unravel the threads of destiny.",
        "Through battles won and battles lost, I'll forge my legacy and leave my mark upon this world.",
        "With each quest I undertake, I'll learn and grow, becoming a master of my own fate.",
        "Armed with determination, I set out to make my mark upon this ancient land.",
        "Every challenge is an opportunity for me to prove my worth and become a legendary hero.",
        "In this world of magic and monsters, I am the spark that will ignite the flame of hope.",
        "With a humble heart and a steadfast spirit, I'll chart a path of adventure and glory.",
        "May my sword strike true and my heart remain steadfast as I venture into the great unknown.",
        "I may be a novice now, but with every quest, I'll become a seasoned warrior.",
        "From these humble beginnings, I'll rise to become a champion of the realm.",
        "With unwavering courage, I'll face the darkness and bring forth the light of victory.",
        "In this realm of legends, I'll create my own story and etch my name into the annals of time.",
        "With each sunrise, a new adventure awaits, and I'll seize it with open arms and a determined spirit."
    ];
    $adventurerQuotes = [
        "Carve your own legendary tale!",
        "Conquer all challenges you face!",
        "Step forward and embrace the unknown!",
        "Prove yourself and become a hero!",
        "Rise above, become a true knight!",
        "Adventure awaits beyond the horizon!",
        "Overcome trials, forge your strength!",
        "Uncover secrets, unravel destinies!",
        "Forge a legacy, leave your mark!",
        "Learn, grow, master your destiny!",
        "Make your mark, etch your story!",
        "Seize opportunities, become a legend!",
        "Ignite hope, conquer with magic!",
        "Chart your path, glory awaits you!",
        "Venture fearlessly into the unknown!",
        "Become a seasoned, mighty warrior!",
        "Rise to the top, claim your victory!",
        "Face darkness, emerge triumphant!",
        "Create your story, become a legend!",
        "Embrace new adventures, find your destiny!"
    ];
    $quoteCount = count($adventurerQuotes);
    $selectedQuoteIndex = $accountId % $quoteCount;
    $selectedQuote = $adventurerQuotes[$selectedQuoteIndex];

    return $selectedQuote;
}

function UsePrestigeToken($fromAccountId, $toAccountId, $commend, $desc)
{
    if ($fromAccountId == null)
    {
        return (new APIResponse(false, "Failed to use prestige token because you provided a null fromAccountId.", $fromAccountId));
    }
    if ($toAccountId == null)
    {
        return (new APIResponse(false, "Failed to use prestige token because you provided a null toAccountId.", $toAccountId));
    }
    if ($fromAccountId == $toAccountId)
    {
        
        return (new APIResponse(false, "You cannot leave a review on yourself.", $toAccountId));
    }
    if ($commend === null)
    {
        return (new APIResponse(false, "Failed to use prestige token because you provided a null rating.", $commend));
    }
    if ($desc == null)
    {
        return (new APIResponse(false, "Failed to use prestige token because you provided a null review.", $desc));
    }
    //get unused prestige token
    $prestigeTokenResp = GetPrestigeTokens($fromAccountId);
    $prestigeTokenInfo = $prestigeTokenResp->Data;
    //print_r($prestigeTokenResp);
    //print_r($prestigeTokenInfo);
    if ($prestigeTokenInfo["remaining"] > 0)
    {
        $lootId = $prestigeTokenInfo["next_token"];
        $fromAccountId = mysqli_real_escape_string($GLOBALS["conn"], $fromAccountId);
        $toAccountId = mysqli_real_escape_string($GLOBALS["conn"], $toAccountId);
        $commend = mysqli_real_escape_string($GLOBALS["conn"], $commend);
        $lootId = mysqli_real_escape_string($GLOBALS["conn"], $lootId);
        $desc = mysqli_real_escape_string($GLOBALS["conn"], $desc);
        $sql = "INSERT INTO prestige (account_id_from, account_id_to, commend, loot_id, `Desc`) VALUES ('$fromAccountId', '$toAccountId', '$commend', '$lootId', '$desc')";
        $result = mysqli_query($GLOBALS["conn"],$sql);
        if ($result === TRUE) {

            return (new APIResponse(true, "Successfully used a prestige token",null));
        } 
        else 
        {
            return (new APIResponse(false, "Failed to leave review with error: ".GetSQLError(), $prestigeTokenResp));
        }
    }
    else{
        return (new APIResponse(false, "Failed to use prestige token because you have none.", $prestigeTokenResp));
    }

}

function GetAccountTitle($account) {
    $level = $account["level"];
    $prestige = $account["prestige"];
    // Define the list of titles for evil and good prestige
    $evil_prestige_titles = [
        "Barbaric",
        "Trolling",
        "Savage",
        "Drunken",
        "Ruthless",
        "Cruel",
        "Vicious",
        "Wicked",
        "Nefarious",
        "Corrupt",
        "Diabolical",
        "Tyrannical",
        "Evil"
    ];

    $good_prestige_titles = [
        "Unrecognized",
        "Recognized",
        "Kind",
        "Respected",
        "Benevolent",
        "Honorable",
        "Virtuous",
        "Noble",
        "Distinguished",
        "Esteemed",
        "Renowned",
        "Wise",
        "Glorious",
        "Just",
        "Magnificent",
        "Gracious",
        "Compassionate",
        "Eminent",
        "Altruistic",
        "Heroic",
        "Prestigious",
        "Illustrious",
        "Exemplary",
        "Saintly",
        "Legendary"
    ];

    // Define the list of titles for levels
    $level_titles = [
        "Noob",
        "Adventurer",
        "Squire",
        "Knight",
        "Elder",
        "Hero",
        "Baron",
        "Viscount",
        "Count",
        "Marquis",
        "Duke",
        "Prince",
        "King",
        "Emperor",
        "Legend",
        "Archon",
        "Overlord",
        "Immortal",
        "Omnipotent",
        "Eternal",
        "Infinite",
        "Titan",
        "Deity",
        "Demigod",
        "God"
    ];

    // Clamp the level and prestige values
    $level = max(0, min($level, 50));
    $prestige = max(-count($evil_prestige_titles), min($prestige, count($good_prestige_titles)));

    // Determine the prestige title based on whether the prestige is negative or non-negative
    if ($prestige < 0) {
        $prestige_title = $evil_prestige_titles[abs($prestige) - 1];
    } else {
        $prestige_title = $good_prestige_titles[$prestige];
    }

    $level_title = $level_titles[intdiv($level, 2)];

    return $prestige_title . " " . $level_title;
}

function SearchForAccount(string $searchTerm, int $page, int $itemsPerPage) : APIResponse
{
    $conn = $GLOBALS['conn'];
    assert($conn instanceof mysqli);

    // Add the wildcards to the searchTerm itself and convert to lowercase
    $searchTerm = "%" . strtolower($searchTerm) . "%";

    $offset = ($page - 1) * $itemsPerPage;

    // Prepare the count statement
    $countQuery = "SELECT COUNT(*) as total FROM v_account_info WHERE (LOWER(username) LIKE ? OR LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ? OR LOWER(email) LIKE ?)  AND Banned = 0";
    $stmtCount = $conn->prepare($countQuery);
    if (false === $stmtCount) {
        error_log($conn->error);
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
    }

    $success = $stmtCount->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    if (false === $success) {
        error_log($stmtCount->error);
        $stmtCount->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
    }

    // Execute the count statement
    $success = $stmtCount->execute();
    if (false === $success) {
        error_log($stmtCount->error);
        $stmtCount->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when executing SQL query. (mysqli_stmt_execute)"]);
    }

    $resultCount = $stmtCount->get_result();
    if (false === $resultCount) {
        error_log($stmtCount->error);
        $stmtCount->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when retrieving SQL query results. (mysqli_stmt_get_result)"]);
    }

    $countRow = $resultCount->fetch_assoc();
    if (!isset($countRow)) {
        error_log($stmtCount->error);
        $stmtCount->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when fetching next row from SQL query results. (mysqli_fetch_assoc)"]);
    }

    $count = $countRow["total"];
    $stmtCount->close();

    // Prepare the main search statement
    $query = "SELECT *,
        (
            (CASE WHEN LOWER(username) LIKE ? THEN 4 ELSE 0 END) +
            (CASE WHEN LOWER(firstname) LIKE ? THEN 3 ELSE 0 END) +
            (CASE WHEN LOWER(lastname) LIKE ? THEN 2 ELSE 0 END) +
            (CASE WHEN LOWER(email) LIKE ? THEN 1 ELSE 0 END)
        ) AS relevancy_score
        FROM v_account_info
        WHERE (LOWER(username) LIKE ? OR LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ? OR LOWER(email) LIKE ?) AND Banned = 0
        ORDER BY relevancy_score DESC, level DESC, exp_current DESC, Username
        LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    if (false === $stmt) {
        error_log($conn->error);
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
    }

    $success = $stmt->bind_param('ssssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $itemsPerPage, $offset);
    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
    }

    // Execute the main search statement
    $success = $stmt->execute();
    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when executing SQL query. (mysqli_stmt_execute)"]);
    }

    $result = $stmt->get_result();
    if (false === $result) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Couldn't find account due to error(s).",
            ["Error in SearchForAccount(...) when retrieving SQL query results. (mysqli_stmt_get_result)"]);
    }

    $accountItems = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $newAccountItems = [];
    foreach($accountItems as $account) {
        // Remove unwanted fields
        unset($account['pass_reset']);
        unset($account['relevancy_score']);

        $badgesResp = GetBadgesByAccountId($account['Id']);
        $account['badge_display'] = $badgesResp->Data;

        $playerRankResp = GetAccountGameRanks($account['Id']);
        $account['game_ranks'] = $playerRankResp->Data;
        $account['IsRanked1'] = in_array(1, array_column($playerRankResp->Data, 'rank'));
        $account['title'] = GetAccountTitle($account);
        $account['avatar_media'] = GetAccountProfilePicture($account);
        $newAccountItems[] = $account;
    }


    return (new APIResponse(true, "Accounts", [
        'total' => $count,
        'accountItems' => $newAccountItems
    ]));
}

function SubmitFeedbackAndCollectRewards($account_id, $quest_id, $host_rating, $quest_rating, $feedback) : APIResponse
{
    // Use the mysqli connection from the global scope
    $conn = $GLOBALS["conn"];
    assert($conn instanceof mysqli);
    
    // Prepare the SQL statement
    $stmt = $conn->prepare("CALL SubmitFeedbackAndCollectRewards(?, ?, ?, ?, ?)");
    if (false === $stmt) {
        error_log($conn->error);
        return new APIResponse(false,
            "Error occurred while collecting rewards",
            ["Error in SubmitFeedbackAndCollectRewards(...) when preparing SQL query. (mysqli_prepare)"]);
    }

    // Bind the parameters to the SQL statement
    $success = $stmt->bind_param('iiiss', $account_id, $quest_id, $host_rating, $quest_rating, $feedback);
    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Error occurred while collecting rewards",
            ["Error in SubmitFeedbackAndCollectRewards(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
    }

    // Execute the SQL statement
    $success = $stmt->execute();
    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Error occurred while collecting rewards",
            ["Error in SubmitFeedbackAndCollectRewards(...) when executing SQL query. (mysqli_stmt_execute)"]);
    }

    // Success
    $stmt->close();
    return (new APIResponse(true, "Feedback submitted and rewards converted successfully", null));
}

function UpsertAccountEquipment(array $equipmentData) : APIResponse
{
    if (!IsLoggedIn()) {
        return (new APIResponse(false, "You must be logged in to update your inventory.", null));
    }

    if ($_SESSION["account"]["Id"] != $equipmentData['equipment-account-id']) {
        return (new APIResponse(false, "You cannot update someone elses inventory", null));
    }

    $conn = $GLOBALS["conn"];
    assert($conn instanceof mysqli);

    // Assuming you have a $conn variable that represents your database connection
    $stmt = $conn->prepare(
        "INSERT INTO account_equipment
        (account_id, avatar_loot_id, player_card_border_loot_id, banner_loot_id, background_loot_id, charm_loot_id, companion_loot_id)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        avatar_loot_id = VALUES(avatar_loot_id),
        player_card_border_loot_id = VALUES(player_card_border_loot_id),
        banner_loot_id = VALUES(banner_loot_id),
        background_loot_id = VALUES(background_loot_id),
        charm_loot_id = VALUES(charm_loot_id),
        companion_loot_id = VALUES(companion_loot_id)");

    if (false === $stmt) {
        error_log($conn->error);
        return new APIResponse(false,
            "Error prevented equipment from being saved",
            ["Error in UpsertAccountEquipment(...) when preparing SQL query. (mysqli_prepare)"]);
    }

    // Bind the variables to the SQL statement
    $success = $stmt->bind_param(
        "iiiiiii",
        $equipmentData['equipment-account-id'],
        $equipmentData['equipment-avatar'],
        $equipmentData['equipment-pc-card'],
        $equipmentData['equipment-banner'],
        $equipmentData['equipment-background'],
        $equipmentData['equipment-charm'],
        $equipmentData['equipment-pet']);

    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Error prevented equipment from being saved",
            ["Error in UpsertAccountEquipment(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
    }


    // Execute the prepared statement
    $success = $stmt->execute();
    if (false === $success) {
        error_log($stmt->error);
        $stmt->close();
        return new APIResponse(false,
            "Error prevented equipment from being saved",
            ["Error in UpsertAccountEquipment(...) when executing SQL query. (mysqli_stmt_execute)"]);
    }

    $stmt->close();
    return (new APIResponse(true, "Data upserted successfully", null));
}

?>
