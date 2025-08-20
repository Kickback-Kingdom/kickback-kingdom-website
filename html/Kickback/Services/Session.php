<?php

namespace Kickback\Services;

use mysqli;
use Kickback\Backend\Config\ServiceCredentials;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vSessionInformation;
use Kickback\Backend\Controllers\AccountController;
use Kickback\Backend\Controllers\NotificationController;
use Kickback\Backend\Views\vAccount;
use Kickback\Common\Version;

class Session {
    private static ?vAccount $currentAccount = null;
    public static function getCurrentAccount() : ?vAccount {
        if (self::$currentAccount === null) {
            self::$currentAccount = self::fetchCurrentAccount();
        }
        return self::$currentAccount;
    }

    /**
    * This method allows the caller to check if we're logged in, check if the
    * $currentAccount object is null, and then obtain the $currentAccount
    * object all in one expression.
    *
    * If this method returns `true`, then the current account is logged in, the
    * value in `$account` after this call will be non-null, and it will be
    * safe to use that value.
    *
    * If this method returns `false`, then there is no current account available,
    * and the value stored in `$account` after the call will be undefined.
    * (It will probably be null, but you shouldn't count on it: always use
    * the return value to determine if it is safe to proceed.)
    *
    * @phpstan-assert-if-true =vAccount $account
    */
    public static function readCurrentAccountInto(?vAccount &$account) : bool
    {
        if (is_null(self::$currentAccount)) {
            self::$currentAccount = self::fetchCurrentAccount();
        }

        if (is_null(self::$currentAccount)) {
            $account = null;
            return false;
        } else {
            $account = self::$currentAccount;
            return true;
        }
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION["sessionToken"], $_SESSION["serviceKey"], $_SESSION["vAccount"]);
    }

    public static function isDelegatingAccess(): bool {
        return isset($_SESSION['account_using_delegate_access']);
    }

    private static function fetchCurrentAccount(): ?vAccount {
        // Logic to fetch current account from session or database
        if (self::isLoggedIn())
        {
            $account = self::getSessionData('vAccount');
            if ( !is_null($account) && $account instanceof vAccount ) {
                return $account;
            } else {
                return null;
            }
        }
        return null;
    }

    private static function setCurrentAccount(vAccount $account) : void {
        self::setSessionData("vAccount", $account);
        self::$currentAccount = $account;
    }

    private static function clearCurrentAccount() : void {
        
        self::setSessionData("vAccount", null);
        self::$currentAccount = null;
    }

    public static function redirect(string $localPath) : never {
        $basePath = rtrim(Version::urlBetaPrefix(), '/');
        header("Location: ".$basePath."/".ltrim($localPath, '/'), true, 302);
        exit;
    }

    public static function loginToService(int $accountId, string $serviceKey) : bool
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
    
        $sessionToken = self::sessionDataString("sessionToken");
        $serviceKey = self::sessionDataString("serviceKey");
        $query = "delete from account_sessions where SessionToken = '$sessionToken' and ServiceKey = '$serviceKey'";
        $result = $conn->query($query);
        if (false === $result) {
            return (new Response(false, "Failed to log out with error: ".self::getSQLError(), null));
        }
    
        self::setSessionData("sessionToken",null);
        self::clearCurrentAccount();
        return (new Response(true, "Logged out successfully",null));
    }

    public static function login(string $serviceKey, string $email, string $pwd) : Response
    {
        $conn = Database::getConnection();

        $serviceKey = mysqli_real_escape_string($conn, $serviceKey);
        $email = mysqli_real_escape_string($conn, $email);
        $pwd = mysqli_real_escape_string($conn, $pwd);

        $query = "SELECT account.Id, account.Password, service.Name as ServiceName FROM account inner join service on service.PublicKey = '$serviceKey' WHERE Email = '$email' and Banned = 0;";
        $result = $conn->query($query);
        if (false === $result) {
            return (new Response(false, "Failed to log in with error: ".self::getSQLError(), null));
        }

        $num_rows = $result->num_rows;
        if ($num_rows === 0) {
            return (new Response(false, "Credentials are incorrect", null));
        }

        $row = $result->fetch_assoc();
        assert(!is_null($row));
        $serviceName = $row["ServiceName"];
        if (!password_verify($pwd, $row["Password"])) {
            return (new Response(false, "Credentials are incorrect",null));
        }

        $accountId = intval($row["Id"]);
        //assert(is_string($accountId));
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
        assert(!is_null($row));

        self::setSessionData("sessionToken",$row["SessionToken"]);
        self::setSessionData("serviceKey",$serviceKey);
        return AccountController::getAccountBySession($serviceKey, $row["SessionToken"]);
    }

    public static function getSessionInformation() : Response
    {
        $info = new vSessionInformation();

        $info->chestsJSON = "[]";
        $info->chests = [];
        $info->notifications = [];
        $info->notificationsJSON = "[]";
        $info->delayUpdateAfterChests = false;

        // This might seem odd, because we aren't retrieving session information
        // BUT we are still returning `true`, as if successfully retrieving
        // session information.
        //
        // The key difference is that "not having a session" is _actually_
        // a valid state: it's the state that everyone is in when they
        // casually visit the site and haven't logged in or created an account.
        //
        // So the `true` return in this instance is to indicate that we are
        // still in a valid state. We don't have session info, but
        // at the same time, nothing has gone wrong. It's fine.
        if (!self::isLoggedIn()) {
            return new Response(true, "Not logged in", $info);
        }

        $account = self::getCurrentAccount();
        if (is_null($account)) {
            return new Response(false, "Logged in, but there is no account for the current session", $info);
        }

        $accountResp = AccountController::getAccountById($account);
        if (!$accountResp->success) {
            return new Response(false, $accountResp->message, $info);
        }

        self::setCurrentAccount($account);
        $chestsResp = AccountController::getAccountChests($account);
        // @phpstan-ignore assign.propertyType
        $info->chests = $chestsResp->data;

        $info->notifications = NotificationController::queryNotificationsByAccount($account);

        $chestsJSON = json_encode($info->chests);
        $notisJSON  = json_encode($info->notifications);

        if ( $chestsJSON === false ) { $chestsJSON = 'Error PHP-side: Could not JSON encode chests.'; }
        if ( $notisJSON  === false ) { $notisJSON  = 'Error PHP-side: Could not JSON encode notifications.'; }

        $info->chestsJSON = $chestsJSON;
        $info->notificationsJSON = $notisJSON;

        return new Response(true, "Session information",$info);
    }

    public static function isAdmin() : bool
    {
        if (self::isLoggedIn() && !is_null(self::getCurrentAccount())) {
            return self::getCurrentAccount()->isAdmin;
        }

        return false;
    }

    public static function isMagisterOfTheAdventurersGuild() : bool
    {
        if (self::isAdmin())
        {
            return true;
        }

        if (self::readCurrentAccountInto($account))
        {
            return $account->isMagisterOfAdventurers;
        }

        return false;
    }

    public static function isSteward() : bool
    {
        if (self::isAdmin())
        {
            return true;
        }

        if (self::readCurrentAccountInto($account))
        {
            return $account->isSteward;
        }

        return false;
    }

    public static function isMerchant() : bool
    {
        if (self::isAdmin())
        {
            return true;
        }

        if (self::readCurrentAccountInto($account))
        {
            return $account->isMerchant;
        }

        return false;
    }

    public static function isEventOrganizer() : bool
    {
        if (self::isAdmin())
        {
            return true;
        }
        
        return self::isSteward();
    }

    public static function isServantOfTheLich() : bool
    {
        if (self::isAdmin())
        {
            return true;
        }

        if (self::readCurrentAccountInto($account))
        {
            return $account->isServantOfTheLich;
        }

        return false;
    }

    public static function isQuestGiver() : bool
    {
        if (self::readCurrentAccountInto($account))
        {
            return $account->isQuestGiver;
        }

        return false;
    }

    /**
    * Ensure the current user is logged in and has a Discord account linked.
    *
    * @return vAccount The current account if the check passes.
    */
    public static function requireDiscordLinked() : vAccount
    {
        if (!self::readCurrentAccountInto($account)) {
            (new Response(false, 'User not logged in', null))->Exit();
        }

        if (empty($account->discordUserId)) {
            (new Response(false, 'No Discord account linked', null))->Exit();
        }

        return $account;
    }

    /**
     * Ensure the current user is logged in and has a Steam account linked.
     * This checks the `steamUserId` field on the account and exits with a
     * failure response if it's missing.
     *
     * @return vAccount The current account if the check passes.
     */
    public static function requireSteamLinked() : vAccount
    {
        if (!self::readCurrentAccountInto($account)) {
            (new Response(false, 'User not logged in', null))->Exit();
        }

        if (empty($account->steamUserId)) {
            (new Response(false, 'No Steam account linked', null))->Exit();
        }

        return $account;
    }

    public static function setSessionData(string $key, mixed $value) : void {
        $_SESSION[$key] = $value;
    }

    public static function sessionDataInt(string $key) : ?int {
        $data = self::getSessionData($key);
        return is_int($data) ? $data : null;
    }

    public static function sessionDataString(string $key) : ?string {
        $data = self::getSessionData($key);
        return is_string($data) ? $data : null;
    }

    public static function getSessionData(string $key) : mixed {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function removeSessionData(string $key) : void {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function getCurrentSessionId() : ?string {
        self::ensureSessionStarted();
        $id = \session_id();
        if ( $id === false ) {
            return null;
        } else {
            return $id;
        }
    }

    public static function ensureSessionStarted() : void
    {
        if (\session_status() !== PHP_SESSION_ACTIVE) {
            \session_start();
        }
    }

    private static function getSQLError() : string
    {
        return $GLOBALS["conn"]->error;
    }
}
?>
