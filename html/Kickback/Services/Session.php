<?php

namespace Kickback\Services;

use mysqli;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Models\Response;
use Kickback\Views\vSessionInformation;
use Kickback\Controllers\AccountController;
use Kickback\Controllers\NotificationController;
use Kickback\Views\vAccount;

class Session {
    private static ?vAccount $currentAccount = null;
    public static function getCurrentAccount() : ?vAccount {
        if (self::$currentAccount === null) {
            self::$currentAccount = self::fetchCurrentAccount();
        }
        return self::$currentAccount;
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION["sessionToken"], $_SESSION["serviceKey"], $_SESSION["vAccount"]);
    }

    public static function isDelegatingAccess(): bool {
        return isset($_SESSION['account_using_delegate_access']);
    }

    private static function fetchCurrentAccount(): ?vAccount {
        // Logic to fetch current account from session or database
        if (self::isLoggedIn()) {
            return self::getSessionData('vAccount');
        }
        return null;
    }

    private static function setCurrentAccount(vAccount $account) {
        self::setSessionData("vAccount", $account);
        $currentAccount = $account;
    }

    private static function clearCurrentAccount() {
        
        self::setSessionData("vAccount", null);
        $currentAccount = null;
    }

    
    public static function loginToService($accountId, $serviceKey) : bool
    {
        $conn = Database::getConnection();
        if (session_status() == PHP_SESSION_ACTIVE)
        {
            // SQL statement with placeholders
            $sql = "REPLACE INTO account_sessions (SessionToken, ServiceKey, account_id, login_time) VALUES (UUID(),?, ?, utc_timestamp())";

            // Prepare the SQL statement
            $stmt = mysqli_prepare($conn, $sql);

            // Check if the statement was prepared successfully
            if ($stmt === false) {
                die(mysqli_error($conn));
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

    public static function logout() : Response
    {
        if (!self::isLoggedIn()) {
            return (new Response(false, "Failed to logout because no one is logged in", null));
        }
    
        $conn = Database::getConnection();
        assert($conn instanceof mysqli);
    
        $sessionToken = self::getSessionData("sessionToken");
        $serviceKey = self::getSessionData("serviceKey");
        $query = "delete from account_sessions where SessionToken = '$sessionToken' and ServiceKey = '$serviceKey'";
        $result = $conn->query($query);
        if (false === $result) {
            return (new Response(false, "Failed to log out with error: ".GetSQLError(), null));
        }
    
        self::setSessionData("sessionToken",null);
        self::clearCurrentAccount();
        return (new Response(true, "Logged out successfully",null));
    }
        
    public static function login($serviceKey,$email,$pwd) : Response
    {
        $conn = Database::getConnection();
        assert($conn instanceof mysqli);

        $serviceKey = mysqli_real_escape_string($conn, $serviceKey);
        $email = mysqli_real_escape_string($conn, $email);
        $pwd = mysqli_real_escape_string($conn, $pwd);

        $query = "SELECT account.Id, account.Password, service.Name as ServiceName FROM account inner join service on service.PublicKey = '$serviceKey' WHERE Email = '$email' and Banned = 0;";
        $result = $conn->query($query);
        if (false === $result) {
            return (new Response(false, "Failed to log in with error: ".GetSQLError(), null));
        }

        $num_rows = $result->num_rows;
        if ($num_rows === 0) {
            return (new Response(false, "Credentials are incorrect", null));
        }

        $row = $result->fetch_assoc();
        $serviceName = $row["ServiceName"];
        if (!password_verify($pwd, $row["Password"])) {
            return (new Response(false, "Credentials are incorrect",null));
        }

        $accountId = $row["Id"];
        if (!self::loginToService($accountId, $serviceKey)) {
            return (new Response(false, "Failed to login", null));
        }

        $query = "SELECT * FROM account_sessions WHERE ServiceKey = '$serviceKey' and account_id = $accountId";
        $result = $conn->query($query);
        $num_rows = $result->num_rows;
        if ($num_rows === 0) {
            return (new Response(false, "Failed to login", null));
        }

        $row = $result->fetch_assoc();

        self::setSessionData("sessionToken",$row["SessionToken"]);
        self::setSessionData("serviceKey",$serviceKey);
        return AccountController::getAccountBySession($serviceKey, $row["SessionToken"]);
    }

    public static function getSessionInformation() : Response {

        $info = new vSessionInformation();

        
        $info->chestsJSON = "[]";
        $info->chests = [];
        $info->notifications = [];
        $info->notificationsJSON = "[]";
        $info->delayUpdateAfterChests = false;
        if (self::isLoggedIn())
        {
            $account = self::getCurrentAccount();
            $accountResp = AccountController::getAccountById($account);
            if ($accountResp->success)
            {

                self::setCurrentAccount($account);
                $chestsResp = AccountController::getAccountChests($account);
                $info->chests = $chestsResp->data;
                
                $info->notifications = NotificationController::getNotificationsByAccount($account)->data;
    
                $info->chestsJSON = json_encode($info->chests);
                $info->notificationsJSON = json_encode($info->notifications);
            }
            else 
            {
                return new Response(false, $accountResp->message, $info);
            }
        }


        return new Response(true, "Session information",$info);
    }

    public static function isAdmin() : bool {
        if (self::isLoggedIn())
        {
            return self::getCurrentAccount()->isAdmin;
        }

        return false;
    }
    
    public static function isQuestGiver() : bool {
        if (self::isLoggedIn())
        {
            return self::getCurrentAccount()->isQuestGiver;
        }

        return false;
    }
    
    public static function setSessionData($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function getSessionData($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function removeSessionData($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function getCurrentSessionId() {
        self::ensureSessionStarted();
        return session_id() ?: null; // Use null coalescing operator to handle non-existent session IDs.
    }

    
    public static function ensureSessionStarted() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }
}
?>
