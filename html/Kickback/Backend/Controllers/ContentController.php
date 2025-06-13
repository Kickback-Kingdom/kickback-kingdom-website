<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vPageContent;
use Kickback\Services\Session;

class ContentController {

    
    public static function getContentDataById(vRecordId $contentId, string $container_type, string $container_id) : Response {
        
        $conn = Database::getConnection();
        
        // Query to select content details and their corresponding data
        $sql = "
            SELECT * from v_content_data_info
            WHERE 
                content_id = ?";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return (new Response(false, "SQL statement preparation failed."));
        }

        // Bind the content ID parameter
        mysqli_stmt_bind_param($stmt, "i", $contentId->crand);

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return (new Response(false, "Query execution failed."));
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

        /*$contentData = array(
            'data' => $data,
            'container_type' => $container_type,
            'container_id' => $container_id,
            "Id" => $contentId
        );*/

        $contentData = new vPageContent($contentId->ctime, $contentId->crand);
        $contentData->data = $data;
        $contentData->containerType = $container_type;
        $contentData->containerId = $container_id;

        // Close the statement
        mysqli_stmt_close($stmt);

        return (new Response(true, "Content retrieved successfully.", $contentData));
    }

    public static function getContentTypes() : Response {
        $conn = Database::getConnection();
        $query = "SELECT Id, type_name FROM content_type";

        $stmt = mysqli_prepare($conn, $query);

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch the content types into an associative array
        $contentTypes = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($stmt);

        return (new Response(true, "Content Types", $contentTypes));
    }

    public static function canUpdateContent($contentData) : bool {
        if (Session::isLoggedIn())
        {
            $type = $contentData["edit-content-container-type"];
            $ids = explode("/", $contentData["edit-content-container-id"]);
            switch ($type) {
                case 'BLOG-POST':
                    //$blog = GetBlogByLocator($ids[0]);
                    $blogPostResp = BlogPostController::getBlogPostByLocators($ids[0],$ids[1]);
                    if ($blogPostResp->success)
                    {
                        return $blogPostResp->data->isWriter();
                    }
                    else
                    {
                        return false;
                    }

                case 'QUEST':
                    $questResp = QuestController::getQuestByLocator($ids[0]);
                    if ($questResp->success)
                    {
                        return $questResp->data->canEdit();
                    }
                    else
                    {
                        return false;
                    }
                    break;
                case 'QUEST-LINE':

                    $questResp = QuestLineController::requestQuestLineResponseByLocator($ids[0]);
                    if ($questResp->success)
                    {
                        return $questResp->data->canEdit();
                    }
                    else
                    {
                        return false;
                    }
                    break;
                case 'LICH-CARD':

                    $lichCardResp = LichCardController::getLichCardByLocator($ids[0]);
                    if ($lichCardResp->success)
                    {
                        return $lichCardResp->data->canEdit();
                    }
                    else
                    {
                        return false;
                    }
                    break;

                        
                case 'LICH-SET':

                    $lichSetResp = LichCardController::getLichSetByLocator($ids[0]);
                    if ($lichSetResp->success)
                    {
                        return $lichSetResp->data->canEdit();
                    }
                    else
                    {
                        return false;
                    }
                    break;

                case 'TREASURE-HUNT':
                    $treasureHuntResp = TreasureHuntController::getEventByLocator($ids[0]);
                    if ($treasureHuntResp->success)
                    {
                        return $treasureHuntResp->data->canEdit();
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

        
    public static function updateContentDataByID($contentData) : Response {

        if (!self::canUpdateContent($contentData))
        {
            return new Response(false, "You do not have permissions to update this content", null);
        }

        $conn = Database::getConnection();
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
                return new Response(false, $e->getMessage(), null);

            }
        }

        return new Response(true, "Content updated successfully.", null);

    }

    
    public static function insertNewContent() : int
    {
        $conn = Database::getConnection();
        $summary = "New Content";
        $stmt = $conn->prepare("INSERT INTO content (summary) values (?)");
        mysqli_stmt_bind_param($stmt, 's', $summary);
        mysqli_stmt_execute($stmt);
        $newId = mysqli_insert_id($conn);
        return  $newId;
    }

    /*public static function getContentById($contentId)
    {
        // Query to select content details and their corresponding data
        $sql = "SELECT * from content where Id = ?";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
        if (!$stmt) {
            return (new Response(false, "SQL statement preparation failed."));
        }

        // Bind the content ID parameter
        mysqli_stmt_bind_param($stmt, "i", $contentId);

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return (new Response(false, "Query execution failed."));
        }

        // Fetch the results
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if a row was returned
        if (!$row) {
            return (new Response(false, "Content not found."));
        }

        return (new Response(true, "Content retrieved successfully.", $contentData));
    }*/
}

?>
