<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Services\Database;
use Kickback\Models\Response;

class MediaController {
    
    public static function getMediaDirectories()
    {
        // Use global connection
        $conn = Database::getConnection();

        // Retrieve events for the specified month and year
        $query = "SELECT * FROM v_media_directories";
        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the events into an associative array
        $dirs = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($stmt);

        
        return (new Response(true, "Media Directories",  $dirs ));
    }

}


?>