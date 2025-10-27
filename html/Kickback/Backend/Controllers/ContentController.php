<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vPageContent;
use Kickback\Services\Session;

/**
* @phpstan-import-type vPageContent_data_a from vPageContent
*
* @phpstan-type deleteContentDetailPOST_a array{
*         deleted                 : ?string,
*         content_detail_data_id  : string|int|null
*     }
*
* @phpstan-type insertContentDetailPOST_a array{
*         data               : ?string,
*         data_order         : string|int|null,
*         media_id           : string|int|null
*     }
*
* @phpstan-type updateContentDetailPOST_a array{
*         content_detail_data_id  : string|int|null,
*         data                    : ?string,
*         data_order              : string|int|null,
*         media_id                : string|int|null
*     }
*
* @phpstan-type editContentDetailPOST_a  deleteContentDetailPOST_a|insertContentDetailPOST_a|updateContentDetailPOST_a
*
* @phpstan-type deleteContentRequestPOST_a array{
*         deleted            : ?string,
*         inserted?          : ?string,
*         content_detail_id  : string|int|null
*     }
*
* (Note: the `inserted?` member above is invalid actually.
* But we must put it in the type signature so that we can check for it
* without triggering PHPStan errors.)
*
* @phpstan-type insertContentRequestPOST_a array{
*         inserted           : ?string,
*         content_id         : string|int|null,
*         content_type       : string|int|null,
*         element_order      : string|int|null,
*         data_items?        : ?array<insertContentDetailPOST_a>
*     }
*
* @phpstan-type updateContentRequestPOST_a array{
*         updated            : ?string,
*         inserted?          : ?string,
*         content_detail_id  : string|int|null,
*         content_type       : string|int|null,
*         element_order      : string|int|null,
*         data_items?        : ?array<editContentDetailPOST_a>
*     }
*
* @phpstan-type editContentRequestPOST_a  deleteContentRequestPOST_a|insertContentRequestPOST_a|updateContentRequestPOST_a
*
* @phpstan-type editContentRequestListPOST_a array<editContentRequestPOST_a>
*
*/
class ContentController
{
    /**
    * @phpstan-assert-if-true =vPageContent $contentData
    */
    public static function convertContentDataResponseInto(Response $content_data_response, ?vPageContent &$contentData) : bool
    {
        $resp = $content_data_response;
        if ( $resp->success ) {
            $contentData = $resp->data;
            return true;
        } else {
            $contentData = null;
            return false;
        }
    }

    public static function queryContentDataByIdAsResponse(vRecordId $contentId, string $container_type, string $container_id) : Response
    {
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
                    'content_id'        => intval($row['content_id']),
                    'content_detail_id' => intval($detailId),
                    'content_type_name' => $row['content_type_name'],
                    'element_order'     => intval($row['element_order']),
                    'content_type'      => intval($row['content_type']),
                    'data_items'        => []
                ];
            }
            $data[$detailId]['data_items'][] = [
                'content_detail_data_id' => intval($row['content_detail_data_id']),
                'data'       => $row['data'],
                'data_order' => intval($row['data_order']),
                'image_path' => $row['Image_Path'],
                'media_id'   => isset($row['media_id']) ? intval($row['media_id']) : null
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

    /**
    * @return array<array{ Id : string,  type_name : string }>
    */
    public static function queryContentTypes() : array
    {
        $resp = self::queryContentTypesAsResponse();
        if ($resp->success) {
            // @phpstan-ignore-next-line
            return $resp->data;
        } else {
            throw new \Exception($resp->message);
        }
    }

    public static function queryContentTypesAsResponse() : Response
    {
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

    public static function canUpdateContent(string $contentContainerId, string $contentContainerType) : bool
    {
        if (!Session::isLoggedIn()) {
            return false;
        }

        $ids = explode("/", $contentContainerId);
        switch ($contentContainerType)
        {
            case 'BLOG-POST':
                if ( BlogPostController::queryBlogPostByLocatorsInto($ids[0], $ids[1], $blogPost) )
                {
                    return $blogPost->isWriter();
                }
                else
                {
                    return false;
                }

            case 'QUEST':
                if ( QuestController::queryQuestByLocatorInto($ids[0], $quest) )
                {
                    return $quest->canEdit();
                }
                else
                {
                    return false;
                }

            case 'QUEST-LINE':

                if ( QuestLineController::queryQuestLineByLocatorInto($ids[0], $questLine) )
                {
                    return $questLine->canEdit();
                }
                else
                {
                    return false;
                }

            case 'LICH-CARD':
                if ( LichCardController::queryLichCardByLocatorInto($ids[0], $lichCard) )
                {
                    return $lichCard->canEdit();
                }
                else
                {
                    return false;
                }


            case 'LICH-SET':
                if ( LichCardController::queryLichSetByLocatorInto($ids[0], $lichSet) )
                {
                    return $lichSet->canEdit();
                }
                else
                {
                    return false;
                }

            case 'TREASURE-HUNT':
                if ( TreasureHuntController::queryEventByLocatorInto($ids[0], $treasureHunt) )
                {
                    return $treasureHunt->canEdit();
                }
                else
                {
                    return false;
                }

            default:
            return false;
        }
    }

    /**
    * @param array<string,?string> $http_post_data
    */
    public static function update_content_data_from_http_post(array $http_post_data) : Response
    {
        if (!array_key_exists('edit-content-content-data',   $http_post_data)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: missing `edit-content-content-data` option.');
        }
        if (!array_key_exists('edit-content-container-type', $http_post_data)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: missing `edit-content-container-type` option.');
        }
        if (!array_key_exists('edit-content-container-id',   $http_post_data)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: missing `edit-content-container-id` option.');
        }

        $content_data_json      = $http_post_data["edit-content-content-data"];
        $content_container_type = $http_post_data["edit-content-container-type"];
        $content_container_id   = $http_post_data["edit-content-container-id"];

        if (!isset($content_data_json)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: null `edit-content-content-data` option.');
        }
        if (!isset($content_container_type)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: null `edit-content-container-type` option.');
        }
        if (!isset($content_container_id)) {
            return new Response(false, 'Invalid edit-content HTTP POST request: null `edit-content-container-id` option.');
        }

        if (!self::canUpdateContent($content_container_id, $content_container_type)) {
            return new Response(false, "You do not have permissions to update this content", null);
        }

        $content_data = json_decode($content_data_json, true);
        assert(is_array($content_data));
        return self::handleContentEditRequests($content_data);
    }

    /**
    * @param editContentRequestListPOST_a $content_data
    */
    private static function handleContentEditRequests(array $content_data) : Response
    {
        $conn = Database::getConnection();

        foreach ($content_data as $contentItem) {
            try {
                self::handleContentEditRequest($conn, $contentItem);
            } catch (\Exception $e) {
                return new Response(false, $e->getMessage(), null);
            }
        }

        return new Response(true, 'Content updated successfully.', null);
    }

    /**
    * @param editContentRequestPOST_a $contentItem
    */
    private static function handleContentEditRequest(\mysqli $conn, array $contentItem) : void
    {
        // Ignore items with both deleted=true and inserted=true
        if (self::editContentFlagIsTrue($contentItem, 'deleted') && self::editContentFlagIsTrue($contentItem, 'inserted')) {
            return;
        }

        // Handle Deleted Items
        if (self::editContentRequestIsDelete($contentItem))
        //if (isset($contentItem['deleted']) && $contentItem['deleted'])
        {
            // Delete content_detail_data
            $stmt = $conn->prepare('DELETE FROM content_detail_data WHERE content_detail_id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $contentItem['content_detail_id']);
            mysqli_stmt_execute($stmt);

            // Delete content_detail
            $stmt = $conn->prepare("DELETE FROM content_detail WHERE Id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $contentItem['content_detail_id']);
            mysqli_stmt_execute($stmt);
        }
        // Handle Updated Items (only if not marked as inserted)
        elseif (self::editContentRequestIsUpdate($contentItem))
        {
            // Disabled for now; it might break things.
            // (I'm not sure if it's valid to have 0 detail items or not.)
            // -- Chad Joan  2025-06-18
            //if ( !self::extractEditContentDetailInto($contentItem, $dataItems) ) {
            //    throw new \Exception('Invalid edit-content HTTP POST request: no detail items in `update` request.');
            //}

            // Update content_detail
            $stmt = $conn->prepare('UPDATE content_detail SET content_type_id = ?, `order` = ? WHERE Id = ?');
            mysqli_stmt_bind_param($stmt, 'iii', $contentItem['content_type'], $contentItem['element_order'], $contentItem['content_detail_id']);
            mysqli_stmt_execute($stmt);

            // If there are no detail items, then we're done.
            // This check guards the foreach loop against
            // potentially trying to iterate over a `null` value.
            // (See also: commented out code above would be how to make this an error, if that's desired.)
            if ( !self::extractEditContentDetailInto($contentItem, $dataItems) ) {
                return;
            }

            // Update, Insert, or Delete content_detail_data (loop through each data item)
            foreach ($dataItems as $dataItem) {
                if (self::editContentDetailIsDelete($dataItem)) {
                    // Delete the data_item if it has a content_detail_data_id
                    if (isset($dataItem['content_detail_data_id'])) {
                        $stmt = $conn->prepare('DELETE FROM content_detail_data WHERE Id = ?');
                        mysqli_stmt_bind_param($stmt, 'i', $dataItem['content_detail_data_id']);
                        mysqli_stmt_execute($stmt);
                    }
                } elseif (isset($dataItem['content_detail_data_id'])) {
                    // It's an existing data item, so update
                    $stmt = $conn->prepare('UPDATE content_detail_data SET data = ?, data_order = ?, media_id = ? WHERE Id = ?');
                    mysqli_stmt_bind_param($stmt, 'siii', $dataItem['data'], $dataItem['data_order'], $dataItem['media_id'], $dataItem['content_detail_data_id']);
                    mysqli_stmt_execute($stmt);
                } else {
                    // It's a new data item, so insert
                    $stmt = $conn->prepare('INSERT INTO content_detail_data (content_detail_id, data, data_order, media_id) VALUES (?, ?, ?, ?)');
                    mysqli_stmt_bind_param($stmt, 'isii', $contentItem['content_detail_id'], $dataItem['data'], $dataItem['data_order'], $dataItem['media_id']);
                    mysqli_stmt_execute($stmt);
                }
            }
        }
        // Handle Inserted Items
        elseif (self::editContentRequestIsInsert($contentItem))
        {
            // Disabled for now; it might break things.
            // (I'm not sure if it's valid to have 0 detail items or not.)
            // -- Chad Joan  2025-06-18
            //if ( !self::extractInsertContentDetailInto($contentItem, $dataItems) ) {
            //    throw new \Exception('Invalid edit-content HTTP POST request: no detail items in `insert` request.');
            //}

            // Insert into content_detail
            $stmt = $conn->prepare('INSERT INTO content_detail (content_id, content_type_id, `order`) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iii', $contentItem['content_id'], $contentItem['content_type'], $contentItem['element_order']);
            mysqli_stmt_execute($stmt);
            $newContentDetailId = mysqli_insert_id($conn);

            // If there are no detail items, then we're done.
            // This check guards the foreach loop against
            // potentially trying to iterate over a `null` value.
            // (See also: commented out code above would be how to make this an error, if that's desired.)
            if ( !self::extractInsertContentDetailInto($contentItem, $dataItems) ) {
                return;
            }

            // Insert into content_detail_data (loop for each data item)
            foreach ($dataItems as $dataItem) {
                $stmt = $conn->prepare('INSERT INTO content_detail_data (content_detail_id, data, data_order, media_id) VALUES (?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'isii', $newContentDetailId, $dataItem['data'], $dataItem['data_order'], $dataItem['media_id']);
                mysqli_stmt_execute($stmt);
            }
        }
    }

    /**
    * @param     editContentRequestPOST_a         $contentItem
    * @param     ?array<editContentDetailPOST_a>  $detailItems
    * @param-out ?array<editContentDetailPOST_a>  $detailItems
    *
    * @phpstan-assert-if-true =array<editContentDetailPOST_a> $detailItems
    */
    private static function extractEditContentDetailInto(
        array  $contentItem,
        ?array &$detailItems
    ) : bool
    {
        $detailItems = null;
        if ( !array_key_exists('data_items', $contentItem) ) {
            return false;
        }

        $detailItems = $contentItem['data_items'];
        return isset($detailItems);
    }

    /**
    * @param     insertContentRequestPOST_a         $contentItem
    * @param     ?array<insertContentDetailPOST_a>  $detailItems
    * @param-out ?array<insertContentDetailPOST_a>  $detailItems
    *
    * @phpstan-assert-if-true =array<insertContentDetailPOST_a> $detailItems
    */
    private static function extractInsertContentDetailInto(
        array  $contentItem,
        ?array &$detailItems
    ) : bool
    {
        return self::extractEditContentDetailInto($contentItem, $detailItems);
    }

    /**
    * @param  editContentDetailPOST_a  $detailItem
    *
    * @phpstan-assert-if-true  deleteContentDetailPOST_a  $detailItem
    */
    private static function editContentDetailIsDelete(array $detailItem) : bool
    {
        return self::editContentFlagIsTrue($detailItem, 'deleted');
    }

    /**
    * @param  editContentRequestPOST_a  $contentItem
    *
    * @phpstan-assert-if-true  deleteContentRequestPOST_a  $contentItem
    */
    private static function editContentRequestIsDelete(array $contentItem) : bool
    {
        return self::editContentFlagIsTrue($contentItem, 'deleted');
    }

    /**
    * @param  editContentRequestPOST_a  $contentItem
    *
    * @phpstan-assert-if-true  insertContentRequestPOST_a  $contentItem
    */
    private static function editContentRequestIsInsert(array $contentItem) : bool
    {
        return self::editContentFlagIsTrue($contentItem, 'inserted');
    }

    /**
    * @param  editContentRequestPOST_a  $contentItem
    *
    * @phpstan-assert-if-true  updateContentRequestPOST_a  $contentItem
    */
    private static function editContentRequestIsUpdate(array $contentItem) : bool
    {
        return self::editContentFlagIsTrue($contentItem, 'updated')
            && !self::editContentFlagIsTrue($contentItem, 'inserted');
    }

    /**
    * @param  editContentRequestPOST_a|editContentDetailPOST_a  $contentOrDetailItem
    */
    private static function editContentFlagIsTrue(array $contentOrDetailItem, string $flag) : bool
    {
        if ( !array_key_exists($flag, $contentOrDetailItem) ) {
            return false;
        }

        $flagValueAsStr = $contentOrDetailItem[$flag];
        if ( !isset($flagValueAsStr) ) {
            return false;
        }

        $is_flagged = filter_var($flagValueAsStr, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return (isset($is_flagged) && $is_flagged);
    }

    public static function insertNewContent() : int|string
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
