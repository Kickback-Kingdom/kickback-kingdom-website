<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Lobby;
use Kickback\Backend\Models\LobbyChallenge;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Backend\Views\vLobby;
use Kickback\Backend\Views\vLobbyChallenge;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vReviewStatus;

use Kickback\Common\Primitives\Str;

class LobbyController
{

    public static function host(Lobby $lobby, string $password) : Response
    {
        $insertLobbyResp = self::insert($lobby, $password);

        if ($insertLobbyResp->success)
        {
            $LobbyChallenge = new LobbyChallenge($lobby, PlayStyle::Ranked);

            $insertLobbyChallengeResp = LobbyChallengeController::insert($LobbyChallenge);

            return $insertLobbyChallengeResp;
        }
        else
        {
            return $insertLobbyResp;
        }
        
    }

    public static function getLobby(vRecordId $lobbyId): Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT l.ctime, l.crand, g.Id as game_crand, g.Name as game_name, 
                h.Id as host_id, h.username as host_username, l.name, 
                lm.ctime as lobby_challenge_ctime, lm.crand as lobby_challenge_crand, lm.style, lm.rules, icon.mediaPath as icon_path,
                CASE 
                    WHEN lca.account_id IS NOT NULL THEN 1 
                    ELSE 0 
                END as has_joined,
                CASE 
                    WHEN lcp.player_count IS NOT NULL THEN lcp.player_count 
                    ELSE 0 
                END as player_count,
                CASE 
                    WHEN lcp.players_ready IS NOT NULL THEN lcp.players_ready 
                    ELSE 0 
                END as players_ready, l.published, l.closed, lca.ready,
                lm.started
                FROM lobby l
                INNER JOIN game g ON l.game_id = g.Id
                INNER JOIN account h ON l.host_id = h.Id
                INNER JOIN lobby_challenge lm ON lm.ref_lobby_ctime = l.ctime AND lm.ref_lobby_crand = l.crand
                left JOIN lobby_challenge_account lca on lca.ref_challenge_ctime = lm.ctime AND lca.ref_challenge_crand = lm.crand and lca.account_id = ? AND lca.left = 0
                left join v_media icon on icon.Id = g.media_icon_id
                left join v_lobby_challenge_players lcp on lm.ctime = lcp.ref_challenge_ctime and lm.crand = lcp.ref_challenge_crand
                WHERE l.ctime = ? AND l.crand = ? and l.closed = 0";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }

        $accountCRand = -1;
        if (Session::isLoggedIn())
        {
            $accountCRand = Session::getCurrentAccount()->crand;
        }

        mysqli_stmt_bind_param($stmt, 'isi', $accountCRand, $lobbyId->ctime, $lobbyId->crand);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $lobby = self::row_to_vLobby($row);
            mysqli_stmt_close($stmt);
            return new Response(true, 'Lobby found', $lobby);
        } else {
            mysqli_stmt_close($stmt);
            return new Response(false, 'Lobby not found', null);
        }
    }

    public static function getLobbies() : Response
    {
        $conn = Database::getConnection();

        $sql = "SELECT l.ctime, l.crand, g.Id as game_crand, g.Name as game_name, h.Id as host_id, h.username as host_username, l.name, lm.ctime as lobby_challenge_ctime, lm.crand as lobby_challenge_crand, lm.style, lm.rules, icon.mediaPath as icon_path,
                CASE 
                    WHEN lca.account_id IS NOT NULL THEN 1 
                    ELSE 0 
                END as has_joined,
                CASE 
                    WHEN lcp.player_count IS NOT NULL THEN lcp.player_count 
                    ELSE 0 
                END as player_count,
                CASE 
                    WHEN lcp.players_ready IS NOT NULL THEN lcp.players_ready 
                    ELSE 0 
                END as players_ready, l.published, l.closed, lca.ready,
                lm.started
        from lobby l 
        inner join game g on l.game_id = g.Id 
        inner join account h on l.host_id = h.Id
        inner join lobby_challenge lm on lm.ref_lobby_ctime = l.ctime and lm.ref_lobby_crand = l.crand
        left JOIN lobby_challenge_account lca on lca.ref_challenge_ctime = lm.ctime AND lca.ref_challenge_crand = lm.crand and lca.account_id = ? AND lca.left = 0
        left join v_media icon on icon.Id = g.media_icon_id
        left join v_lobby_challenge_players lcp on lm.ctime = lcp.ref_challenge_ctime and lm.crand = lcp.ref_challenge_crand
        where (l.published = 1 or l.host_id = ?) and l.closed = 0";

        $accountCRand = -1;
        if (Session::isLoggedIn())
        {
            $accountCRand = Session::getCurrentAccount()->crand;
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $accountCRand,$accountCRand);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            return new Response(false, "Failed to fetch lobbies: " . $conn->error, []);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $lobbies = array_map([self::class, 'row_to_vLobby'], $rows);

        return new Response(true, "lobby feed", $lobbies);
    }

    public static function close(vRecordId $lobbyId) : Response {
        $conn = Database::getConnection();

        $sql = "UPDATE `lobby` SET `closed` = 1 WHERE `lobby`.`ctime` = ? AND `lobby`.`crand` = ?";

        $stmt = $conn->prepare($sql);

        
        $stmt->bind_param('si', $lobbyId->ctime, $lobbyId->crand);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'lobby successfully closed', null);
        } else {
            $stmt->close();
            return new Response(false, 'lobby failed to close', null);
        }
    }


    private static function insert(Lobby $lobby, string $password) : Response
    {
        $conn = Database::getConnection();

        if (!Str::empty($password)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $password_hash = null;
        }

        $sql = "INSERT INTO lobby (ctime, crand, host_id, `password`, `name`, `game_id`) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return new Response(false, 'Failed to prepare statement', null);
        }
        while (true) {

            mysqli_stmt_bind_param($stmt, 'siissi',
            $lobby->ctime, $lobby->crand, $lobby->hostId->crand, $password_hash, $lobby->name, $lobby->gameId->crand);

            mysqli_stmt_execute($stmt);

            if ($stmt->errno == 1062) {
                $lobby->crand = $lobby->GenerateCRand();
                continue; 
            } elseif ($stmt->errno) {
                $message = 'Error: ' . $stmt->error;
                mysqli_stmt_close($stmt);
                return new Response(false, $message, null);
            } else {
                break;
            }
        }

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            return new Response(true, 'Insert successful', $lobby);
        } else {
            mysqli_stmt_close($stmt);
            return new Response(false, 'Insert failed or no rows affected', null);
        }

    }

    public static function checkIfHost(vRecordId $lobbyId, vRecordId $accountId) : Response {
        
    }

    public static function publish(vRecordId $lobbyId) : Response {
        $conn = Database::getConnection();

        $sql = "UPDATE `lobby` SET `published` = 1 WHERE `lobby`.`ctime` = ? AND `lobby`.`crand` = ?";

        $stmt = $conn->prepare($sql);

        
        $stmt->bind_param('si', $lobbyId->ctime, $lobbyId->crand);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            return new Response(true, 'lobby successfully published', null);
        } else {
            $stmt->close();
            return new Response(false, 'lobby failed to publish', null);
        }
    }

    private static function row_to_vLobby($row) : vLobby {
        $lobby = new vLobby($row["ctime"], (int)$row["crand"]);
        $lobby->name = $row["name"];
        
        $game = new vGame('', (int)$row["game_crand"]);
        $game->name = $row["game_name"];
        $lobby->game = $game;
        
        if (array_key_exists("icon_path",$row) && $row["icon_path"] != null)
        {
            $icon = new vMedia();
            $icon->setMediaPath($row["icon_path"]);
            $lobby->game->icon = $icon;
        }

        $host = new vAccount('', (int)$row["host_id"]);
        $host->username = $row["host_username"];
        $lobby->host = $host;
        $lobby->reviewStatus = new vReviewStatus((bool)$row["published"], false, (bool)$row["closed"]);

        $LobbyChallenge = new vLobbyChallenge($row["lobby_challenge_ctime"],(int)$row["lobby_challenge_crand"]);
        $lobby->challenge = $LobbyChallenge;
        $lobby->challenge->style = PlayStyle::from((int)$row["style"]);
        $lobby->challenge->rules = (string)$row["rules"];
        $lobby->challenge->hasJoined = array_key_exists("has_joined", $row) ? (bool)$row["has_joined"] : false;
        $lobby->challenge->playerCount = (int)$row["player_count"];
        $lobby->challenge->players = [];
        $lobby->challenge->ready = (bool)$row["ready"];
        $lobby->challenge->playersReady = (int)$row["players_ready"];
        $lobby->challenge->allPlayersReady = ($lobby->challenge->playersReady == $lobby->challenge->playerCount);
        $lobby->challenge->started = (bool)$row["started"];
        return $lobby;
    }
}
?>
