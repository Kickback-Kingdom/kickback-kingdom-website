<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Account;
use Kickback\Views\vAccount;
use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Models\Response;
use Kickback\Services\Database;

class AccountController
{
    public static function getAccountById(vRecordId $recordId) : Response {

        $conn = Database::getConnection();
        // Prepare the SQL statement
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_account_info WHERE Id = ?");

        // Bind the parameter to the placeholder in the SQL statement
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);

        // Execute the prepared statement
        mysqli_stmt_execute($stmt);

        // Store the result of the query
        $result = mysqli_stmt_get_result($stmt);

        $num_rows = mysqli_num_rows($result);
        if ($num_rows === 0)
        {
            return (new Response(false, "Couldn't find an account with that Id", null));
        }
        else
        {
            $row = mysqli_fetch_assoc($result);
            $account = self::row_to_vAccount($row);
            // Free the result & close the statement
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
        
            return (new Response(true, $account->username."'s information.",  $account ));
        }
    }

    public static function getAccountNotifications(vRecordId $recordId) : Response {
        
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_notifications WHERE account_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);
        mysqli_stmt_execute($stmt);
    
        $result = mysqli_stmt_get_result($stmt);
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $num_rows = mysqli_num_rows($result);
        if ($num_rows === 0)
        {
            return (new Response(true, "Couldn't find notifications for Id", []));
        }
        else
        {
            return (new Response(true, "Account notifications",  $rows ));
        }
    }

    public static function getAccountInventory(vRecordId $recordId) : Response
    {
        $conn = Database::getConnection();
        
        $sql = "SELECT * FROM kickbackdb.v_account_inventory_desc WHERE account_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt === false) {
            return new Response(false, mysqli_error($conn), null);
        }
        
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);
        mysqli_stmt_execute($stmt);
        
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result === false) {
            return new Response(false, mysqli_stmt_error($stmt), null);
        }
        
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        return new Response(true, "Account Inventory", $rows);
    }

    public static function getAccountByUsername(string $username) : Response
    {
        $conn = Database::getConnection();
        // Prepare SQL statement
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_account_info WHERE Username = ?");

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

            return (new Response(false, "Couldn't find an account with that username", null));
        }
        else
        {
            $row = mysqli_fetch_assoc($result);

            $account = self::row_to_vAccount($row);
            // Free the statement
            mysqli_stmt_close($stmt);

            return (new Response(true, $account->username."'s information.",  $account));
        }
    }
    
    public static function getAccountByEmail(string $email)
    {
        $conn = Database::getConnection();
        // Prepare SQL statement
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_account_info WHERE Email = ?");

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

            return (new Response(false, "Couldn't find an account with that email", null));
            
        }
        else
        {
            $row = mysqli_fetch_assoc($result);
            $account = self::row_to_vAccount($row);

            // Free the statement
            mysqli_stmt_close($stmt);
            
            return (new Response(true, $account->username."'s information.",  $account));
        }
    }

    public static function getAccountChests(vRecordId $recordId) : Response {
        
        return new Response(false, 'AccountController::getAccountChests not implemented');
    }

    public static function getAccountBySession(string $serviceKey, string $sessionToken) : Response {
        try {
            $conn = Database::getConnection();
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
            $stmt = mysqli_prepare($conn, $sql);

            // Check if the statement was prepared successfully
            if ($stmt === false) {
                return (new Response(false, mysqli_error($conn), null));
            }

            // Bind parameters to the placeholders
            mysqli_stmt_bind_param($stmt, "ssss", $sessionToken, $serviceKey, $sessionToken, $serviceKey);

            // Execute the statement
            $result = mysqli_stmt_execute($stmt);

            // Check the result of the query
            if (!$result) {
                return (new Response(false, mysqli_stmt_error($stmt), null));
            }

            // Bind result variables
            $res = mysqli_stmt_get_result($stmt);

            // Fetch the result
            if (mysqli_num_rows($res) === 0) {
                return (new Response(false, "Session Token or Service Key are incorrect", null));
            } else {
                $row = mysqli_fetch_assoc($res);
                $account = self::row_to_vAccount($row);
                return (new Response(true, "Welcome to " . $row["ServiceName"] . "! A Kickback Kingdom original.", $account));
            }
        } catch (Throwable $th) {
            return (new Response(false, "Error. Check the data for more info.", $th));
        }
    }
    
    private static function row_to_vAccount($row) : vAccount {
        $account = new vAccount('', $row["Id"]);

        // Assign string and integer properties
        $account->username = $row["Username"];
        $account->firstName = $row["FirstName"];
        $account->lastName = $row["LastName"];
        $account->isBanned = (bool) $row["Banned"];
        $account->email = $row["email"];
        $account->exp = (int) $row["exp"];
        $account->level = (int) $row["level"];
        $account->expNeeded = (int) $row["exp_needed"];
        $account->expStarted = (int) $row["exp_started"];
        $account->prestige = (int) $row["prestige"];
        $account->badges = (int) $row["badges"];
        $account->expCurrent = (int) $row["exp_current"];
        $account->expGoal = (int) $row["exp_goal"];

        // Assign boolean properties
        $account->isAdmin = (bool) $row["IsAdmin"];
        $account->isMerchant = (bool) $row["IsMerchant"];
        $account->isAdventurer = true;
        $account->isSteward = false;
        $account->isCraftsmen = false;
        $account->isMasterOrApprentice = (bool) $row["IsMaster"] || (bool) $row["IsApprentice"];
        $account->isArtist = (bool) $row["IsArtist"];
        $account->isQuestGiver = (bool) $row["IsQuestGiver"];

        // Assign vMedia properties if they exist
        if ($row['avatar_media'] != null)
        {
            $avatar = new vMedia();
            $avatar->mediaPath = $row['avatar_media'];
            $account->avatar = $avatar;
        }

        if ($row['player_card_border_media'] != null)
        {
            $playerCardBorder = new vMedia();
            $playerCardBorder->mediaPath = $row['player_card_border_media'];
            $account->playerCardBorder = $playerCardBorder;
        }

        if ($row['banner_media'] != null)
        {
            $banner = new vMedia();
            $banner->mediaPath = $row['banner_media'];
            $account->banner = $banner;
        }

        if ($row['background_media'] != null)
        {
            $background = new vMedia();
            $background->mediaPath = $row['background_media'];
            $account->background = $background;
        }

        if ($row['charm_media'] != null)
        {
            $charm = new vMedia();
            $charm->mediaPath = $row['charm_media'];
            $account->charm = $charm;
        }

        if ($row['companion_media'] != null)
        {
            $companion = new vMedia();
            $companion->mediaPath = $row['companion_media'];
            $account->companion = $companion;
        }

        return $account;
    }

    private static function insert(Account $account) : Response {

        return new Response(false, 'AccountController::insert not implemented');

    }
}
?>
