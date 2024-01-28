<?php

function InsertNewContent()
{
    global $conn; 
    $summary = "New Content";
    $stmt = $conn->prepare("INSERT INTO content (summary) values (?)");
    mysqli_stmt_bind_param($stmt, 's', $summary);
    mysqli_stmt_execute($stmt);
    $newId = mysqli_insert_id($conn);
    return  $newId;
}

function CanUpdateContent($contentData)
{
    if (IsLoggedIn())
    {
        $type = $contentData["edit-content-container-type"];
        $ids = explode("/", $contentData["edit-content-container-id"]);
        switch ($type) {
            case 'BLOG-POST':
                //$blog = GetBlogByLocator($ids[0]);
                $blogPostResp = GetBlogPostByLocators($ids[0],$ids[1]);
                if ($blogPostResp->Success)
                {
                    return IsWriterForBlogPost($blogPostResp->Data);
                }
                else
                {
                    return false;
                }

            case 'QUEST':
                $questResp = GetQuestByLocator($ids[0]);
                if ($questResp->Success)
                {
                    return CanEditQuest($questResp->Data);
                }
                else
                {
                    return false;
                }
                break;
            
            default:
            return false;
        }
    }

    return false;
}

function UpdateContentDataByID($contentData) {

    if (!CanUpdateContent($contentData))
    {
        return new APIResponse(false, "You do not have permissions to update this content", null);
    }

    global $conn;  // Assuming this is your mysqli connection

    $data = json_decode($contentData["edit-content-content-data"], true);

    foreach ($data as $contentItem) {
        try {
            // Ignore items with both deleted=true and inserted=true
            if (isset($contentItem['deleted']) && $contentItem['deleted'] && isset($contentItem['inserted']) && $contentItem['inserted']) {
                continue;
            }

            // Handle Deleted Items
            if (isset($contentItem['deleted']) && $contentItem['deleted']) {
                // Delete content_detail_data
                $stmt = $conn->prepare("DELETE FROM content_detail_data WHERE content_detail_id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $contentItem['content_detail_id']);
                mysqli_stmt_execute($stmt);

                // Delete content_detail
                $stmt = $conn->prepare("DELETE FROM content_detail WHERE Id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $contentItem['content_detail_id']);
                mysqli_stmt_execute($stmt);
            }

            // Handle Updated Items (only if not marked as inserted)
            // Handle Updated Items (only if not marked as inserted)
            elseif (isset($contentItem['updated']) && $contentItem['updated'] && (!isset($contentItem['inserted']) || !$contentItem['inserted'])) {
                // Update content_detail
                $stmt = $conn->prepare("UPDATE content_detail SET content_type_id = ?, `order` = ? WHERE Id = ?");
                mysqli_stmt_bind_param($stmt, 'iii', $contentItem['content_type'], $contentItem['element_order'], $contentItem['content_detail_id']);
                mysqli_stmt_execute($stmt);

                // Update, Insert, or Delete content_detail_data (loop through each data item)
                foreach ($contentItem['data_items'] as $dataItem) {
                    if (isset($dataItem['deleted']) && $dataItem['deleted']) {
                        // Delete the data_item if it has a content_detail_data_id
                        if (isset($dataItem['content_detail_data_id'])) {
                            $stmt = $conn->prepare("DELETE FROM content_detail_data WHERE Id = ?");
                            mysqli_stmt_bind_param($stmt, 'i', $dataItem['content_detail_data_id']);
                            mysqli_stmt_execute($stmt);
                        }
                    } elseif (isset($dataItem['content_detail_data_id'])) {
                        // It's an existing data item, so update
                        $stmt = $conn->prepare("UPDATE content_detail_data SET data = ?, data_order = ?, media_id = ? WHERE Id = ?");
                        mysqli_stmt_bind_param($stmt, 'siii', $dataItem['data'], $dataItem['data_order'], $dataItem['media_id'], $dataItem['content_detail_data_id']);
                        mysqli_stmt_execute($stmt);
                    } else {
                        // It's a new data item, so insert
                        $stmt = $conn->prepare("INSERT INTO content_detail_data (content_detail_id, data, data_order, media_id) VALUES (?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, 'isii', $contentItem['content_detail_id'], $dataItem['data'], $dataItem['data_order'], $dataItem['media_id']);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }


            // Handle Inserted Items
            elseif (isset($contentItem['inserted']) && $contentItem['inserted']) {
                // Insert into content_detail
                $stmt = $conn->prepare("INSERT INTO content_detail (content_id, content_type_id, `order`) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'iii', $contentItem['content_id'], $contentItem['content_type'], $contentItem['element_order']);
                mysqli_stmt_execute($stmt);
                $newContentDetailId = mysqli_insert_id($conn);

                // Insert into content_detail_data (loop for each data item)
                foreach ($contentItem['data_items'] as $dataItem) {
                    $stmt = $conn->prepare("INSERT INTO content_detail_data (content_detail_id, data, data_order, media_id) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, 'isii', $newContentDetailId, $dataItem['data'], $dataItem['data_order'], $dataItem['media_id']);
                    mysqli_stmt_execute($stmt);
                }
            }
        } catch (Exception $e) {
            return new APIResponse(false, $e->getMessage(), null);

        }
    }

    return new APIResponse(true, "Content updated successfully.", null);

}


function GetContentById($contentId)
{
    // Query to select content details and their corresponding data
    $sql = "SELECT * from content where Id = ?";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed."));
    }

    // Bind the content ID parameter
    mysqli_stmt_bind_param($stmt, "i", $contentId);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed."));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if a row was returned
    if (!$row) {
        return (new APIResponse(false, "Content not found."));
    }

    return (new APIResponse(true, "Content retrieved successfully.", $contentData));
}

function GetContentDataById($contentId, $container_type, $container_id) {
    // Query to select content details and their corresponding data
    $sql = "
        SELECT * from v_content_data_info
        WHERE 
            content_id = ?";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed."));
    }

    // Bind the content ID parameter
    mysqli_stmt_bind_param($stmt, "i", $contentId);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed."));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    
    // Collecting the data into an organized array
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $detailId = $row['content_detail_id'];
        if (!isset($data[$detailId])) {
            $data[$detailId] = [
                'content_id' => $row['content_id'],
                'content_detail_id' => $detailId,
                'content_type_name' => $row['content_type_name'],
                'element_order' => $row['element_order'],
                'content_type' => $row['content_type'],
                'data_items' => []
            ];
        }
        $data[$detailId]['data_items'][] = [
            'content_detail_data_id' => $row['content_detail_data_id'],
            'data' => $row['data'],
            'data_order' => $row['data_order'],
            'image_path' => $row['Image_Path'],
            'media_id' => $row['media_id']
        ];
    }

    // Convert the data array to a zero-indexed array
    $data = array_values($data);

    

    $contentData = array(
        'data' => $data,
        'container_type' => $container_type,
        'container_id' => $container_id,
        "Id" => $contentId
    );
    // Close the statement
    mysqli_stmt_close($stmt);


    return (new APIResponse(true, "Content retrieved successfully.", $contentData));
}

function GetContentTypes()
{
    $db = $GLOBALS['conn'];
    $query = "SELECT Id, type_name FROM content_type";
    
    $stmt = mysqli_prepare($db, $query);
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Fetch the content types into an associative array
    $contentTypes = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    mysqli_stmt_close($stmt);
    
    return (new APIResponse(true, "Content Types", $contentTypes));
}


function GetMediaDirectories()
{
    // Use global connection
    $db = $GLOBALS['conn'];

    // Retrieve events for the specified month and year
    $query = "SELECT * FROM v_media_directories";
    $stmt = mysqli_prepare($db, $query);

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetch the events into an associative array
    $dirs = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_stmt_close($stmt);

    
    return (new APIResponse(true, "Media Directories",  $dirs ));
}

function InsertMediaRecord($directory, $name, $desc, $extension) {
    // Get the global database connection and service key
    $db = $GLOBALS["conn"];
    $serviceKey = $GLOBALS["kkservice"];
    
    // Retrieve the author's ID from the session
    $author_id = $_SESSION["account"]["Id"];

    // Prepare the SQL statement
    $query = "INSERT INTO Media (ServiceKey, name, `desc`, author_id, Directory, extension) VALUES (?, ?, ?, ?, ?,?)";
    
    $stmt = mysqli_prepare($db, $query);
    
    if (!$stmt) {
        // Failed to prepare the statement
        error_log("Failed to prepare statement: " . mysqli_error($db));
        return false;
    }
    
    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'sssiss', $serviceKey, $name, $desc, $author_id, $directory, $extension);
    
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
    if (IsAdmin()) {
        // Admins can upload to any directory
        return true;
    }

    $allowedDirectoriesResponse = GetMediaDirectories();
    if (!$allowedDirectoriesResponse->Success) {
        return false;
    }

    $validDirs = [];
    foreach ($allowedDirectoriesResponse->Data as $dir) {
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
        return new APIResponse(false, 'Decoded image data is empty.', null);
    }

    // Check if directory is allowed
    if (!isAllowedDirectory($directory)) {
        return new APIResponse(false, 'Invalid directory.', null);
    }

    // Determine image MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($decodedImageData);

    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowedMimeTypes)) {
        return new APIResponse(false, 'Invalid image type.', $mime);
    }

    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    $fileExtension = $extensions[$mime];

    // Insert media record
    $mediaId = InsertMediaRecord($directory, $name, $desc, $fileExtension);
    if (!$mediaId) {
        return new APIResponse(false, 'Error saving media record: ' . mysqli_error($GLOBALS['conn']), null);
    }

    // Define the file path
    $rootPath = "/var/www/html"; // Root path of your web server
    $filePath = join(DIRECTORY_SEPARATOR, [$rootPath, 'assets', 'media', $directory, "{$mediaId}.{$fileExtension}"]);

    $directoryPath = dirname($filePath);

    // If directory doesn't exist and user is an admin, create the directory
    if (IsAdmin() && !is_dir($directoryPath)) {
        mkdir($directoryPath, 0777, true);
    }

    // Attempt to save the image
    if (file_put_contents($filePath, $decodedImageData)) {
        return new APIResponse(true, 'Image uploaded successfully.', ['mediaId' => $mediaId]);
    } else {
        return new APIResponse(false, 'Error saving the image.', null);
    }
}

function SearchForMedia($directory, $searchTerm, $page, $itemsPerPage)
{
    $db = $GLOBALS['conn'];

    // Add the wildcards to the searchTerm itself
    $searchTerm = "%" . $searchTerm . "%";

    $offset = ($page - 1) * $itemsPerPage;

    if (empty($directory)) {
        $countQuery = "SELECT COUNT(*) as total FROM v_media WHERE `name` LIKE ? OR `desc` LIKE ?";
        $stmtCount = mysqli_prepare($db, $countQuery);
        mysqli_stmt_bind_param($stmtCount, 'ss', $searchTerm, $searchTerm);

        $query = "SELECT * FROM v_media WHERE `name` LIKE ? OR `desc` LIKE ? LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, 'ssii', $searchTerm, $searchTerm, $itemsPerPage, $offset);
    } else {
        $countQuery = "SELECT COUNT(*) as total FROM v_media WHERE (`name` LIKE ? OR `desc` LIKE ?) AND Directory = ?";
        $stmtCount = mysqli_prepare($db, $countQuery);
        mysqli_stmt_bind_param($stmtCount, 'sss', $searchTerm, $searchTerm, $directory);

        $query = "SELECT * FROM v_media WHERE (`name` LIKE ? OR `desc` LIKE ?) AND Directory = ? LIMIT ? OFFSET ?";
        $stmt = mysqli_prepare($db, $query);
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

    return (new APIResponse(true, "Media Files", [
        'total' => $count,
        'mediaItems' => $mediaItems
    ]));
}

?>