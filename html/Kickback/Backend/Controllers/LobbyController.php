<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Lobby;
use Kickback\Backend\Models\LobbyMatch;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;

class LobbyController
{

    public static function host(Lobby $lobby, $password) : Response
    {
        $insertLobbyResp = Insert($lobby, $password);

        if ($insertLobbyResp->success)
        {
            $lobbyMatch = new LobbyMatch($lobby);

            $insertLobbyMatchResp = LobbyMatchController::Create($lobbyMatch);

            return $insertLobbyMatchResp;
        }
        else
        {
            return $insertLobbyResp;
        }
        
    }

    public static function getLobbies() : array {
        
        $conn = Database::getConnection();

        $sql = "SELECT l.ctime, l.crand, ";
    }

    private static function insert(Lobby $lobby, $password) : Response {

        $conn = Database::getConnection();

        if (!empty($password)) {
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
            $lobby->ctime, $lobby->crand, $lobby->host_id, $password_hash, $lobby->name, $lobby->game_id);

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
}
?>
