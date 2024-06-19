<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Account;
use Kickback\Views\vAccount;
use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Controllers\LootController;

use Kickback\Views\vRaffle;

class AccountController
{
    public static function getAccountById(vRecordId $recordId) : Response {

        $conn = Database::getConnection();
        // Prepare the SQL statement
        $stmt = mysqli_prepare($conn, "SELECT * FROM v_account_info WHERE Id = ?");
        if ($stmt === false) {
            return new Response(false, "failed to prepare sql statement", null);
        }

        // Bind the parameter to the placeholder in the SQL statement
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand);

        // Execute the prepared statement
        mysqli_stmt_execute($stmt);

        // Store the result of the query
        $result = mysqli_stmt_get_result($stmt);

        if ($result === false) {
            return new Response(false, "Failed to get query result", null);
        }

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

    public static function getAccountInventory(vRecordId $recordId) : Response {
        
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

    public static function getAccountByUsername(string $username) : Response {

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
    
    public static function getAccountByEmail(string $email) : Response {

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
    
    public static function getAccountByRaffleWinner(vRaffle $raffle): Response {
        $conn = Database::getConnection();
        //$sql = "SELECT * FROM kickbackdb.v_raffle_winners WHERE raffle_id = ?";
        $sql = "SELECT 
        `account`.*
        FROM
        (((`raffle`
        LEFT JOIN `raffle_submissions` ON (`raffle`.`winner_submission_id` = `raffle_submissions`.`Id`))
        LEFT JOIN `v_loot_item` `loot` ON (`raffle_submissions`.`loot_id` = `loot`.`Id`))
        LEFT JOIN v_account_info as `account` ON (`loot`.`account_id` = `account`.`Id`))
        WHERE raffle.Id = ? and account.Id is not null";

        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, null);
        }

        // Bind the parameter
        $stmt->bind_param('i', $raffle->crand);

        // Execute the statement
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, null);
        }

        // Get the result
        $result = $stmt->get_result();
        if ($result === false) {
            return new Response(false, "Failed to get result: " . $stmt->error, null);
        }

        // Fetch the row
        $row = $result->fetch_assoc();

        // Free the result and close the statement
        $result->free();
        $stmt->close();

        if ($row) {
            return new Response(true, "Raffle Ticket Winner", self::row_to_vAccount($row, true));
        } else {
            return new Response(true, "No winner found for the raffle", null);
        }
    }

    public static function getAccountChests(vRecordId $recordId) : Response {
        $conn = Database::getConnection();
        // Prepare the SQL statement
        $stmt = mysqli_prepare($conn, "SELECT loot.Id, loot.rarity, CONCAT(b.Directory,'/',b.Id,'.',b.extension) as ItemImg FROM kickbackdb.v_loot_item as loot left join Media b on b.Id = loot.media_id_large where loot.account_id = ? and loot.opened = 0");

        // Bind the parameter to the placeholder in the SQL statement
        mysqli_stmt_bind_param($stmt, "i", $recordId->crand); // "i" signifies that the parameter is an integer

        // Execute the prepared statement
        mysqli_stmt_execute($stmt);

        // Store the result of the query
        $result = mysqli_stmt_get_result($stmt);

        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free the result & close the statement
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);

        return (new Response(true, "Account Chests",  $rows ));
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
    
    public static function searchForAccount(string $searchTerm, int $page, int $itemsPerPage) : Response {
        $conn = Database::getConnection();
        //assert($conn instanceof mysqli);

        // Add the wildcards to the searchTerm itself and convert to lowercase
        $searchTerm = "%" . strtolower($searchTerm) . "%";

        $offset = ($page - 1) * $itemsPerPage;

        // Prepare the count statement
        $countQuery = "SELECT COUNT(*) as total FROM v_account_info WHERE (LOWER(username) LIKE ? OR LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ? OR LOWER(email) LIKE ?)  AND Banned = 0";
        $stmtCount = $conn->prepare($countQuery);
        if (false === $stmtCount) {
            error_log($conn->error);
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
        }

        $success = $stmtCount->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        if (false === $success) {
            error_log($stmtCount->error);
            $stmtCount->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
        }

        // Execute the count statement
        $success = $stmtCount->execute();
        if (false === $success) {
            error_log($stmtCount->error);
            $stmtCount->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when executing SQL query. (mysqli_stmt_execute)"]);
        }

        $resultCount = $stmtCount->get_result();
        if (false === $resultCount) {
            error_log($stmtCount->error);
            $stmtCount->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when retrieving SQL query results. (mysqli_stmt_get_result)"]);
        }

        $countRow = $resultCount->fetch_assoc();
        if (!isset($countRow)) {
            error_log($stmtCount->error);
            $stmtCount->close();
            return new Response(false,
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
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
        }

        $success = $stmt->bind_param('ssssssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $itemsPerPage, $offset);
        if (false === $success) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
        }

        // Execute the main search statement
        $success = $stmt->execute();
        if (false === $success) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when executing SQL query. (mysqli_stmt_execute)"]);
        }

        $result = $stmt->get_result();
        if (false === $result) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when retrieving SQL query results. (mysqli_stmt_get_result)"]);
        }

        $accountItems = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $newAccountItems = [];
        foreach($accountItems as $accountRow) {
            // Remove unwanted fields

            $account = self::row_to_vAccount($accountRow, true);

            $newAccountItems[] = $account;
        }


        return (new Response(true, "Accounts", [
            'total' => $count,
            'accountItems' => $newAccountItems
        ]));
    }

    private static function getAccountTitle(vAccount $account) : string {
        $level = $account->level;
        $prestige = $account->prestige;
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

    private static function getAccountGameRanks(vRecordId $account) : Response {
        $conn = Database::getConnection();
        
        // Prepare the SQL statement
        $stmt = $conn->prepare("SELECT * FROM v_game_elo_rank_info WHERE account_id = ? LIMIT 5");
        
        // Bind the parameters
        $stmt->bind_param("i", $account->crand);
        
        // Execute the statement
        $stmt->execute();
        
        // Get the result
        $result = $stmt->get_result();
        
        // Fetch all rows
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        
        // Close the statement
        $stmt->close();
        
        return new Response(true, "Quest Badges", $rows);
    }
    

    public static function row_to_vAccount(array $row, bool $populateChildData = false) : vAccount {
        
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
            $avatar->setMediaPath($row['avatar_media']);
            $account->avatar = $avatar;
        }

        if ($row['player_card_border_media'] != null)
        {
            $playerCardBorder = new vMedia();
            $playerCardBorder->setMediaPath($row['player_card_border_media']);
            $account->playerCardBorder = $playerCardBorder;
        }

        if ($row['banner_media'] != null)
        {
            $banner = new vMedia();
            $banner->setMediaPath($row['banner_media']);
            $account->banner = $banner;
        }

        if ($row['background_media'] != null)
        {
            $background = new vMedia();
            $background->setMediaPath($row['background_media']);
            $account->background = $background;
        }

        if ($row['charm_media'] != null)
        {
            $charm = new vMedia();
            $charm->setMediaPath($row['charm_media']);
            $account->charm = $charm;
        }

        if ($row['companion_media'] != null)
        {
            $companion = new vMedia();
            $companion->setMediaPath($row['companion_media']);
            $account->companion = $companion;
        }

        $account->title = self::getAccountTitle($account);


        if ($populateChildData) {
            $badgesResp = LootController::getBadgesByAccount($account);
            $account->badge_display = $badgesResp->data;

            $playerRankResp = self::getAccountGameRanks($account);
            $account->game_ranks = $playerRankResp->data;
            $account->isGoldCardHolder = in_array(1, array_column($playerRankResp->data, 'rank'));
            
        }

        return $account;
    }

    private static function insert(Account $account) : Response {

        return new Response(false, 'AccountController::insert not implemented');

    }
}
?>
