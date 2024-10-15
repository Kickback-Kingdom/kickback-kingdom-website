<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\LobbyMatch;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;


class LobbyMatchController
{

    public static function insert(LobbyMatch $lobbyMatch) : Response
    {
        $conn = Database::getConnection();
        
        $sql = "INSERT INTO lobby_match (ctime, crand, ref_lobby_ctime, ref_lobby_crand) 
        VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Handle error (e.g., log the error, return an API response)
            return new Response(false, 'Failed to prepare statement', null);
        }
        while (true) {  // Infinite loop, will break on successful insertion

            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'siiss',
            $lobbyMatch->ctime, $lobbyMatch->crand, $lobbyMatch->ref_lobby_ctime, $lobbyMatch->ref_lobby_crand);

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Check for errors using MySQL error code 1062 for duplicate entry
            if ($stmt->errno == 1062) {
                // If it's a duplicate key error, retry with a new `crand`
                $lobbyMatch->crand = $lobbyMatch->GenerateCRand();
                continue; 
            } elseif ($stmt->errno) {
                // If the error is not a duplicate key error, return the error message
                $message = 'Error: ' . $stmt->error;
                mysqli_stmt_close($stmt);
                return new Response(false, $message, null);
            } else {
                // If no error, exit the loop
                break;
            }
        }

        // Check if the insert was successful
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            return new Response(true, 'Insert successful', $lobbyMatch);  // No ID to return
        } else {
            mysqli_stmt_close($stmt);
            return new Response(false, 'Insert failed or no rows affected', null);
        }
    }
}
?>
