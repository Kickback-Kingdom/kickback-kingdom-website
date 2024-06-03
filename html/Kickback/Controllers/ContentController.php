<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Views\vRecordId;
use Kickback\Models\Response;
use Kickback\Services\Database;

class ContentController {

    
    public static function getContentDataById(vRecordId $contentId, $container_type, $container_id) {
        
        $conn = Database::getConnection();
        
        // Query to select content details and their corresponding data
        $sql = "
            SELECT * from v_content_data_info
            WHERE 
                content_id = ?";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return (new Kickback\Models\Response(false, "SQL statement preparation failed."));
        }

        // Bind the content ID parameter
        mysqli_stmt_bind_param($stmt, "i", $contentId->crand);

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return (new Kickback\Models\Response(false, "Query execution failed."));
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


        return (new Response(true, "Content retrieved successfully.", $contentData));
    }
}

?>