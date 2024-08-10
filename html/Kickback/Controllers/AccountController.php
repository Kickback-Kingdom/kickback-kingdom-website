<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Common\Utility\IDCrypt;

use Kickback\Models\Account;
use Kickback\Views\vAccount;
use Kickback\Views\vRecordId;
use Kickback\Views\vMedia;
use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Controllers\LootController;
use Kickback\Backend\Config\ServiceCredentials;
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

    public static function getAccountInventory(vRecordId $recordId) : Response {
        
        return LootController::getLootByAccountId($recordId);
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
    
    public static function searchForAccount(string $searchTerm, int $page, int $itemsPerPage, array $filters = []) : Response {
        $conn = Database::getConnection();
        //assert($conn instanceof mysqli);

        // Add the wildcards to the searchTerm itself and convert to lowercase
        $searchTerm = "%" . strtolower($searchTerm) . "%";

        $offset = ($page - 1) * $itemsPerPage;

        $joinQuery = "";
        $questApplicantConditions = [];


        // Check for quest applicant or participant filters
        if (isset($filters['IsQuestApplicant'])) {
            $joinQuery = " INNER JOIN quest_applicants ON v_account_info.Id = quest_applicants.account_id";
            $questApplicantConditions[] = "quest_applicants.quest_id = " . (int)$filters['IsQuestApplicant'];
            unset($filters['IsQuestApplicant']);
        }
        if (isset($filters['IsQuestParticipant'])) {
            if (empty($joinQuery)) {
                $joinQuery = " INNER JOIN quest_applicants ON v_account_info.Id = quest_applicants.account_id";
            }
            $questApplicantConditions[] = "quest_applicants.quest_id = " . (int)$filters['IsQuestParticipant'];
            $questApplicantConditions[] = "quest_applicants.participated = 1";
            unset($filters['IsQuestParticipant']);
        }
        

        // Prepare the count statement
        $countQuery = "SELECT COUNT(*) as total FROM v_account_info" . $joinQuery . "  WHERE (LOWER(username) LIKE ? OR LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ? OR LOWER(email) LIKE ?)  AND Banned = 0";
        
        // Prepare the base of the main query
        $query = "SELECT *,
        (
            (CASE WHEN LOWER(username) LIKE ? THEN 4 ELSE 0 END) +
            (CASE WHEN LOWER(firstname) LIKE ? THEN 3 ELSE 0 END) +
            (CASE WHEN LOWER(lastname) LIKE ? THEN 2 ELSE 0 END) +
            (CASE WHEN LOWER(email) LIKE ? THEN 1 ELSE 0 END)
        ) AS relevancy_score
        FROM v_account_info" . $joinQuery . "
        WHERE (LOWER(username) LIKE ? OR LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ? OR LOWER(email) LIKE ?) AND Banned = 0";

        // Add additional filters
        $filterConditions = [];
        $filterParams = [];

        foreach ($filters as $field => $value) {
            $filterConditions[] = "LOWER($field) = ?";
            $filterParams[] = strtolower($value);
        }

        if (count($questApplicantConditions) > 0) {
            $filterConditions = array_merge($filterConditions, $questApplicantConditions);
        }

        if (count($filterConditions) > 0) {
            $filterConditionsString = implode(" AND ", $filterConditions);
            $countQuery .= " AND " . $filterConditionsString;
            $query .= " AND " . $filterConditionsString;
        }

        // Complete the main query
        $query .= " ORDER BY relevancy_score DESC, level DESC, exp_current DESC, Username
                    LIMIT ? OFFSET ?";
        
        
        
        $stmtCount = $conn->prepare($countQuery);
        if (false === $stmtCount) {
            error_log($conn->error);
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
        }
        $countParams = array_merge([$searchTerm, $searchTerm, $searchTerm, $searchTerm], $filterParams);
        //$success = $stmtCount->bind_param('ssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $success = $stmtCount->bind_param(str_repeat('s', count($countParams)), ...$countParams);
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

        $stmt = $conn->prepare($query);
        if (false === $stmt) {
            error_log($conn->error);
            return new Response(false,
                "Couldn't find account due to error(s).",
                ["Error in SearchForAccount(...) when preparing SQL query. (mysqli_prepare)"]);
        }


        $mainParams = array_merge([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm], $filterParams, [$itemsPerPage, $offset]);

        $success = $stmt->bind_param(str_repeat('s', count($mainParams) - 2) . 'ii', ...$mainParams);
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
    
    public static function prepareAccountPasswordResetCode(vRecordId $account_id) : Response {
        $conn = Database::getConnection();
        $code = random_int(1000000, 9999999);
        
        // Prepare the SQL statement
        $sql = "UPDATE account SET pass_reset = ? WHERE Id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'ii', $code, $account_id->crand);
            
            // Execute the statement
            $result = mysqli_stmt_execute($stmt);
            
            // Close the statement
            mysqli_stmt_close($stmt);
            
            if ($result) {
                return (new Response(true, "Generated Password Reset Code", $code));
            } else {
                return (new Response(false, "Failed to generate Password Reset Code! " . mysqli_error($conn), null));
            }
        } else {
            return (new Response(false, "Failed to prepare SQL statement! " . mysqli_error($conn), null));
        }
    }

    public static function getAccountByWritOfPassageLootId(vRecordId $loot_id) : Response {
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, 'SELECT ai.* FROM loot l 
        left join account a on a.passage_id = l.id
        left join v_account_info ai on ai.id = l.account_id
        where l.id = ? and l.item_id = 14 and a.id is null');
        
        // Bind the input parameter to the prepared statement
        mysqli_stmt_bind_param($stmt, 'i', $loot_id->crand);
    
        // Execute the SQL statement
        mysqli_stmt_execute($stmt);
    
        // Get the result of the SQL query
        $result = mysqli_stmt_get_result($stmt);
    
        // Check if any rows were returned
        if (mysqli_num_rows($result) > 0) {
            $ownerInfo = mysqli_fetch_assoc($result);
    
            // Free the statement
            mysqli_stmt_close($stmt);
    
            // Return the owner information if found
            return new Response(true, "Owner information found.", self::row_to_vAccount($ownerInfo));
        } else {
            // Free the statement
            mysqli_stmt_close($stmt);
    
            // If no owner is found, the Writ of Passage may not be assigned or doesn't exist
            return new Response(false, "No owner found for the Writ of Passage or it has not been assigned yet or has already been used.", null);
        }
    }
    
    public static function getUnusedAccountRaffleTicket(vRecordId $account_id) : Response {
        $conn = Database::getConnection();
    
        $sql = "SELECT loot_id FROM kickbackdb.v_raffle_tickets WHERE raffle_id IS NULL AND account_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
    
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }
    
        $stmt->bind_param("i", $account_id->crand);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $row = $result->fetch_row();
        $loot_id = $row[0] ?? -1;
        return new Response(true, "Unused raffle ticket id", $loot_id);
    }

    public static function getPrestigeTokens(vRecordId $accountId) : Response {
        $conn = Database::getConnection();
    
        $sql = "SELECT * FROM v_account_prestige_tokens_full WHERE account_Id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement");
        }
    
        $stmt->bind_param("i", $accountId->crand);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $num_rows = $result->num_rows;
        if ($num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row != null) {
                return new Response(true, "Account Prestige tokens", $row);
            }
        }
        
        return new Response(false, "We couldn't find an account with that id", $accountId);
    }
    
    public static function getWritOfPassageByAccountId(vRecordId $account_id) : Response {
        $conn = Database::getConnection();
    
        $sql = "SELECT * FROM kickbackdb.v_account_writs_of_passage WHERE account_id = ?";
        $stmt = $conn->prepare($sql);
    
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }
    
        $stmt->bind_param("i", $account_id->crand);
        $stmt->execute();
        $result = $stmt->get_result();
    
        $num_rows = $result->num_rows;
        if ($num_rows === 0) {
            return new Response(false, "Couldn't find a valid writ of passage", null);
        } else {
            $row = $result->fetch_assoc();
            return new Response(true, "Writs of Passage", $row);
        }
    }
    
    public static function updateAccountPassword(vRecordId $account_id, int $pass_reset, string $newPassword) : Response {
        $conn = Database::getConnection();
    
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
        $sql = "UPDATE account SET Password = ? WHERE Id = ? AND pass_reset = ?";
        $stmt = $conn->prepare($sql);
    
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement", null);
        }
    
        $stmt->bind_param("sii", $passwordHash, $account_id->crand, $pass_reset);
        $result = $stmt->execute();
    
        if ($result) {
            return new Response(true, "Password changed successfully", null);
        } else {
            return new Response(false, "Failed to change password! " . $stmt->error, null);
        }
    }
    
    public static function upsertAccountEquipment(array $equipmentData) : Response {
        if (!Session::isLoggedIn()) {
            return (new Response(false, "You must be logged in to update your inventory.", null));
        }

        if (Session::getCurrentAccount()->crand != $equipmentData['equipment-account-id']) {
            return (new Response(false, "You cannot update someone elses inventory", null));
        }
        $conn = Database::getConnection();
        //assert($conn instanceof mysqli);

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
            return new Response(false,
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
            return new Response(false,
                "Error prevented equipment from being saved",
                ["Error in UpsertAccountEquipment(...) binding SQL query parameters. (mysqli_stmt_bind_param)"]);
        }


        // Execute the prepared statement
        $success = $stmt->execute();
        if (false === $success) {
            error_log($stmt->error);
            $stmt->close();
            return new Response(false,
                "Error prevented equipment from being saved",
                ["Error in UpsertAccountEquipment(...) when executing SQL query. (mysqli_stmt_execute)"]);
        }

        $stmt->close();
        return (new Response(true, "Data upserted successfully", null));
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

    public static function RegisterAccount(string $firstName,string $lastName,string $password,string $confirm_password,string $username,string $email,bool $i_agree,string $passage_quest,string $passage_id) : Response {
        
        $conn = Database::getConnection();
        
        if (!$i_agree)
        {
            return (new Response(false, "Please agree to the terms of service", null));
        }
    
        if ($password != $confirm_password)
        {
            return (new Response(false, "Password does not match.", null));
        }
    
        if (strlen($password) < 8)
        {   
            return (new Response(false, "Password is too short.", null));
        }
    
        if (strlen($firstName) < 2)
        {   
            return (new Response(false, "First Name is too short.", null));
        }
    
        if (strlen($lastName) < 2)
        {
            return (new Response(false, "Last Name is too short.", null));
        }
    
        if (strlen($username) < 5)
        {   
            return (new Response(false, "Username is too short.", null));
        }
        
        if (strlen($username) > 15)
        {   
            return (new Response(false, "Username is too long.", null));
        }
    
        if (strlen($email) < 5)
        {
            return (new Response(false, "Email is too short.", null));
        }
    
    
        $kk_crypt_key_quest_id = ServiceCredentials::get("crypt_key_quest_id");
        $crypt = new IDCrypt($kk_crypt_key_quest_id);
        if (ContainsData($passage_id,"passage_id")->success)
        {
    
            $writ_item_id = $crypt->decrypt($passage_id);
        }
        else
        {
            if (ContainsData($passage_quest,"passage_quest")->success)
            {
                
                $writ_quest_id = $crypt->decrypt($passage_quest);
                $writ_quest_id = new vRecordId('', (int)$writ_quest_id);
                $questResp = QuestController::getQuestById($writ_quest_id);
                if ($questResp->success)
                {
                    $quest = $questResp->data;
                    $writResp = self::getWritOfPassageByAccountId($quest->host1);
                    if ($writResp->success)
                    {
                        $writ = $writResp->data;
                        $writ_item_id = $writ['next_item_id'];
                    }
                    else
                    {
                        return (new Response(false, "The host of the quest you are joining ran out of Writs of Passage. Please contact ".$quest->host1->username.'.', null));
                    }
                }
                else
                {
                    return (new Response(false, "We couldn't find the quest associated with your Writ of Passage.",$passage_quest."|".$writ_quest_id));
                }
            }
            else
            {
                return (new Response(false, "Please provide a Writ of Passage.", null));
            }
        }
        
        if (!ContainsData($writ_item_id,"writ_item_id")->success)
        {
            return (new Response(false, "The host of the quest you are joining ran out of Writs of Passage. Please contact ".$quest->host1->username.'.', null));
        }
    
    
        $userResp = self::getAccountByUsername($username);
    
        
        if ($userResp->success)
        {
            return (new Response(false, "Your desired username already exists.", null));
        }
    
        $emailResp = self::getAccountByEmail($email);
    
        if ($emailResp->success)
        {
            return (new Response(false, "Account already exists. Please register with a different email.", null));
        }
    
    
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);


        $conn->begin_transaction();

        try {
            // Use prepared statements to insert the new account
            $stmt = $conn->prepare("INSERT INTO account (Email, Password, FirstName, LastName, Username, passage_id) VALUES (?, ?, ?, ?, ?, ?)");
            if (false === $stmt) {
                throw new Exception("Failed to prepare SQL statement: " . $conn->error);
            }
    
            $stmt->bind_param('ssssss', $email, $passwordHash, $firstName, $lastName, $username, $writ_item_id);
    
            if (false === $stmt->execute()) {
                throw new Exception("Failed to execute SQL statement: " . $stmt->error);
            }
    
            $stmt->close();
    
            $kk_service_key = ServiceCredentials::get("kk_service_key");
            $loginResp = Session::Login($kk_service_key, $email, $password);
            if (!$loginResp->success) {
                throw new Exception("Failed to log in after registration.");
            }
    
            $login = $loginResp->data;
    
            // Additional actions within the transaction
            LootController::giveWritOfPassage($login);
            DiscordWebHook(FlavorTextController::getNewcomerIntroduction($username));
    
            // Commit transaction
            $conn->commit();
    
            return new Response(true, "Account created successfully", $login);
    
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log($e->getMessage());
            return new Response(false, "Failed to register account: " . $e->getMessage(), null);
        }
    }
}
?>
