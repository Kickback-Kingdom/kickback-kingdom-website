<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Services\Database;
use Kickback\Models\Response;
use Kickback\Views\vMedia;
use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;

class MediaController {
    
    public static function getMediaDirectories() : Response {
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

    public static function searchForMedia($directory, $searchTerm, $page, $itemsPerPage) : Response {
        $conn = Database::getConnection();
    
        // Add the wildcards to the searchTerm itself
        $searchTerm = "%" . $searchTerm . "%";
    
        $offset = ($page - 1) * $itemsPerPage;
    
        if (empty($directory)) {
            $countQuery = "SELECT COUNT(*) as total FROM v_media WHERE `name` LIKE ? OR `desc` LIKE ?";
            $stmtCount = mysqli_prepare($conn, $countQuery);
            mysqli_stmt_bind_param($stmtCount, 'ss', $searchTerm, $searchTerm);
    
            $query = "SELECT * FROM v_media WHERE `name` LIKE ? OR `desc` LIKE ? LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'ssii', $searchTerm, $searchTerm, $itemsPerPage, $offset);
        } else {
            $countQuery = "SELECT COUNT(*) as total FROM v_media WHERE (`name` LIKE ? OR `desc` LIKE ?) AND Directory = ?";
            $stmtCount = mysqli_prepare($conn, $countQuery);
            mysqli_stmt_bind_param($stmtCount, 'sss', $searchTerm, $searchTerm, $directory);
    
            $query = "SELECT * FROM v_media WHERE (`name` LIKE ? OR `desc` LIKE ?) AND Directory = ? LIMIT ? OFFSET ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'sssii', $searchTerm, $searchTerm, $directory, $itemsPerPage, $offset);
        }
    
        // Execute the count statement
        mysqli_stmt_execute($stmtCount);
        $resultCount = mysqli_stmt_get_result($stmtCount);
        $count = mysqli_fetch_assoc($resultCount)["total"];
        mysqli_stmt_close($stmtCount);
    
        // Execute the main search statement
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $mediaItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
    
        $newsList = array_map([self::class, 'row_to_vMedia'], $mediaItems);

        return (new Response(true, "Media Files", [
            'total' => $count,
            'mediaItems' => $newsList
        ]));
    }

    function InsertMediaRecord($directory, $name, $desc, $extension) {
        // Get the global database connection and service key
        $db = $GLOBALS["conn"];
    
        $kk_service_key = \Kickback\Config\ServiceCredentials::get("kk_service_key");
    
        // Retrieve the author's ID from the session
        $author_id = Kickback\Services\Session::getCurrentAccount()->crand;
    
        // Prepare the SQL statement
        $query = "INSERT INTO Media (ServiceKey, name, `desc`, author_id, Directory, extension) VALUES (?, ?, ?, ?, ?,?)";
        
        $stmt = mysqli_prepare($db, $query);
        
        if (!$stmt) {
            // Failed to prepare the statement
            error_log("Failed to prepare statement: " . mysqli_error($db));
            return false;
        }
        
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'sssiss', $kk_service_key, $name, $desc, $author_id, $directory, $extension);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            // If the insert was successful, return the ID of the inserted record
            return mysqli_insert_id($db);
        } else {
            // Log any error
            error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
            return false;
        }
    }
    
    function isAllowedDirectory($directory) {
        if (Kickback\Services\Session::isAdmin()) {
            // Admins can upload to any directory
            return true;
        }
    
        $allowedDirectoriesResponse = GetMediaDirectories();
        if (!$allowedDirectoriesResponse->success) {
            return false;
        }
    
        $validDirs = [];
        foreach ($allowedDirectoriesResponse->data as $dir) {
            $validDirs[] = $dir['Directory'];
        }
    
        return in_array($directory, $validDirs);
    }
    
    function UploadMediaImage($directory, $name, $desc, $imageBase64) {
        list($type, $data) = explode(';', $imageBase64);
        list(, $data) = explode(',', $data);
        $decodedImageData = base64_decode($data);
        
        // Check if decoded data is empty
        if (empty($decodedImageData)) {
            return new Kickback\Models\Response(false, 'Decoded image data is empty.', null);
        }
    
        // Check if directory is allowed
        if (!isAllowedDirectory($directory)) {
            return new Kickback\Models\Response(false, 'Invalid directory.', null);
        }
    
        // Determine image MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($decodedImageData);
    
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowedMimeTypes)) {
            return new Kickback\Models\Response(false, 'Invalid image type.', $mime);
        }
    
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        $fileExtension = $extensions[$mime];
    
        // Start DB transaction
        $db = $GLOBALS["conn"];
        mysqli_begin_transaction($db);
    
        try {
            // Insert media record
            $api_response = UploadMediaImageTransaction($db, $directory, $name, $desc, $fileExtension, $decodedImageData);
            // If everything went well, commit the transaction
            mysqli_commit($db);
        return $api_response;
    
        } catch (Exception $e) {
            // An error occurred, roll back the transaction
            mysqli_rollback($db);
            return new Kickback\Models\Response(false, $e->getMessage(), null);
        }
    }
    
    function UploadMediaImageTransaction($db, $directory, $name, $desc, $fileExtension, $decodedImageData) {
        // Insert media record
        $mediaId = InsertMediaRecord($directory, $name, $desc, $fileExtension);
        if (!$mediaId) {
            throw new Exception('Error saving media record: ' . mysqli_error($db));
        }
    
        // Define the file path
        $rootPath = "/var/www/kickback-kingdom-prod/html"; // Root path of your web server
        $filePath = join(DIRECTORY_SEPARATOR, [$rootPath, 'assets', 'media', $directory, "{$mediaId}.{$fileExtension}"]);
    
        $directoryPath = dirname($filePath);
    
        // If directory doesn't exist and user is an admin, create the directory
        if (Kickback\Services\Session::isAdmin() && !is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    
        // Attempt to save the image
        $fileSaved = file_put_contents($filePath, $decodedImageData);
        $fileFound = file_exists($filePath);
        if (!$fileSaved || !$fileFound) {
            throw new Exception('Error saving the image.');
        }
    
        return new Kickback\Models\Response(true, 'Image uploaded successfully.', ['mediaId' => $mediaId]);
    }
    
    private static function row_to_vMedia(array $row) : vMedia {
        $media = new vMedia('', $row["Id"]);

        $media->name = $row["name"];
        $media->desc = $row["desc"];
        $media->author = new vAccount('', $row["author_id"]);
        $media->dateCreated = new vDateTime($row["DateCreated"]);
        $media->extension = $row["extension"];
        $media->directory = $row["directory"];
        $media->setMediaPath($row["mediaPath"]);

        return $media;
    }
}


?>