<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Session;
use Kickback\Common\Version;

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

    public static function InsertOrUpdateMediaRecord($conn, $directory, $name, $desc, $extension, $crand = -1) {
        // Get the global database connection and service key
        $kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");
        
        // Retrieve the author's ID from the session
        $author_id = Session::getCurrentAccount()->crand;
    
        // Check if we are updating an existing record
        if ($crand >= 0) {
            if (!Session::isSteward()) {
                return false;
            }
            // Update existing record
            $query = "UPDATE Media 
                      SET ServiceKey = ?, name = ?, `desc` = ?, author_id = ?, Directory = ?, extension = ?
                      WHERE Id = ?";
            $stmt = mysqli_prepare($conn, $query);
    
            if (!$stmt) {
                error_log("Failed to prepare update statement: " . mysqli_error($conn));
                return false;
            }
    
            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'sssissi', $kk_service_key, $name, $desc, $author_id, $directory, $extension, $crand);
    
            // Execute the update
            if (mysqli_stmt_execute($stmt)) {
                return $crand; // Return the updated record's ID
            } else {
                error_log("Failed to execute update statement: " . mysqli_stmt_error($stmt));
                return false;
            }
        } else {
            // Insert a new record
            $query = "INSERT INTO Media (ServiceKey, name, `desc`, author_id, Directory, extension) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
    
            if (!$stmt) {
                error_log("Failed to prepare insert statement: " . mysqli_error($conn));
                return false;
            }
    
            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'sssiss', $kk_service_key, $name, $desc, $author_id, $directory, $extension);
    
            // Execute the insert
            if (mysqli_stmt_execute($stmt)) {
                return mysqli_insert_id($conn); // Return the ID of the newly inserted record
            } else {
                error_log("Failed to execute insert statement: " . mysqli_stmt_error($stmt));
                return false;
            }
        }
    }

    
    public static function InsertMediaRecord($conn, $directory, $name, $desc, $extension, $crand = -1) {
        // Get the global database connection and service key
    
        $kk_service_key = \Kickback\Backend\Config\ServiceCredentials::get("kk_service_key");
    
        // Retrieve the author's ID from the session
        $author_id = Session::getCurrentAccount()->crand;
    
        // Prepare the SQL statement
        $query = "INSERT INTO Media (ServiceKey, name, `desc`, author_id, Directory, extension) VALUES (?, ?, ?, ?, ?,?)";
        
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            // Failed to prepare the statement
            error_log("Failed to prepare statement: " . mysqli_error($conn));
            return false;
        }
        
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'sssiss', $kk_service_key, $name, $desc, $author_id, $directory, $extension);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            // If the insert was successful, return the ID of the inserted record
            return mysqli_insert_id($conn);
        } else {
            // Log any error
            error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
            return false;
        }
    }
    
    public static function isAllowedDirectory($directory) {
        if (Session::isAdmin()) {
            // Admins can upload to any directory
            return true;
        }
    
        $allowedDirectoriesResponse = self::GetMediaDirectories();
        if (!$allowedDirectoriesResponse->success) {
            return false;
        }
    
        $validDirs = [];
        foreach ($allowedDirectoriesResponse->data as $dir) {
            $validDirs[] = $dir['Directory'];
        }
    
        return in_array($directory, $validDirs);
    }
    
    public static function UploadMediaImage($directory, $name, $desc, $imageBase64, $mediaCRAND = "") : Response {
        list($type, $data) = explode(';', $imageBase64);
        list(, $data) = explode(',', $data);
        $decodedImageData = base64_decode($data);
        $crand = -1;
        // Check if decoded data is empty
        if (empty($decodedImageData)) {
            return new Response(false, 'Decoded image data is empty.', null);
        }

        try {
            // Check if $mediaCRAND is set and not empty
            if (!empty($mediaCRAND)) {
                
                $crand = (int)$mediaCRAND;
                
                
                if (!ctype_digit($mediaCRAND) || $crand < 0) { 
                    throw new \Exception("Invalid mediaCRAND value: $mediaCRAND");
                }
            } else {
                
                $crand = -1;
            }

            if (!vMedia::isValidRecordId(new vRecordId('', $crand)))
            {
                $crand = -1;
            }
        } catch (\Exception $e) {
            
            $crand = -1; // Set a default value
        }
    
        // Check if directory is allowed
        if (!self::isAllowedDirectory($directory)) {
            return new Response(false, 'Invalid directory.', null);
        }
    
        // Determine image MIME type
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($decodedImageData);
    
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowedMimeTypes)) {
            return new Response(false, 'Invalid image type.', $mime);
        }
    
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif'
        ];
        $fileExtension = $extensions[$mime];
    
        // Start DB transaction
        $conn = Database::getConnection();
        mysqli_begin_transaction($conn);
    
        try {
            // Insert media record
            $api_response = self::UploadMediaImageTransaction($conn, $directory, $name, $desc, $fileExtension, $decodedImageData, $crand);
            // If everything went well, commit the transaction
            mysqli_commit($conn);
        return $api_response;
    
        } catch (\Exception $e) {
            // An error occurred, roll back the transaction
            mysqli_rollback($conn);
            return new Response(false, $e->getMessage(), null);
        }
    }
    
    public static function UploadMediaImageTransaction($conn, $directory, $name, $desc, $fileExtension, $decodedImageData, $crand = -1) {
        // Insert media record
        
        $mediaId = self::InsertOrUpdateMediaRecord($conn, $directory, $name, $desc, $fileExtension, $crand);
        if (!$mediaId) {
            throw new \Exception('Error saving media record: ' . mysqli_error($conn));
        }
    
        // Define the file path
        $rootPath = "/var/www/kickback-kingdom-prod/html"; // Root path of your web server
        if (Version::isLocalhost())
        {
            $rootPath = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
        }
        $localPath = join(DIRECTORY_SEPARATOR, ['assets', 'media', $directory, "{$mediaId}.{$fileExtension}"]);
        $filePath = join(DIRECTORY_SEPARATOR, [$rootPath, $localPath]);
    
        $directoryPath = dirname($filePath);
    
        // If directory doesn't exist and user is an admin, create the directory
        if (Session::isAdmin() && !is_dir($directoryPath)) {
            mkdir($directoryPath, 0777, true);
        }
    
        // Attempt to save the image
        $fileSaved = file_put_contents($filePath, $decodedImageData);
        $fileFound = file_exists($filePath);
        if (!$fileSaved || !$fileFound) {
            throw new \Exception('Error saving the image.');
        }
    
        return new Response(true, 'Image uploaded successfully.', ['mediaId' => $mediaId, 'url' => DIRECTORY_SEPARATOR.$localPath]);
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
