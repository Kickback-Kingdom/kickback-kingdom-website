<?php

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vMedia;
use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Services\Session;

class QuestLineController {
    
    public static function getQuestLineById(vRecordId $questLineId) : Response
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE Id = ?");
        $stmt->bind_param("i", $questLineId->crand);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (new Response(true, "Quest Line Information.", self::row_to_vQuestLine($row)));
        }
        else
        {
            $stmt->close();
            return (new Response(false, "We couldn't find a quest line with that id.", null));
        }
    }
    
    public static function getQuestLineByLocator(string $locator) : Response {
        
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT * FROM v_quest_line_info WHERE locator = ?");
        $stmt->bind_param("s", $locator);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $row = $result->fetch_assoc();
            $stmt->close();
            return (new Response(true, "Quest Line Information.", self::row_to_vQuestLine($row)));
        }
        else
        {
            $stmt->close();
            return (new Response(false, "Couldn't find a quest line with that locator.", null));
        }
    }

    public static function getMyQuestLines(?vAccount $account = null, bool $publishedOnly = true)
    {
        if ($account == null)
        {
            $account = Session::getCurrentAccount();
        }
        $conn = Database::getConnection();
        if ($publishedOnly)
        {
            $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ? and published = 1";
        }
        else
        {
            $sql = "SELECT * FROM v_quest_line_info WHERE created_by_id = ?";
        }
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $account->crand);
        $stmt->execute();
        $result = $stmt->get_result();
    
    
        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $questLines = [];
        foreach($rows as $row) {
            // Remove unwanted fields

            $questLine = self::row_to_vQuestLine($row);

            $questLines[] = $questLine;
        }

        return (new Response(true, "My Quest Lines",  $questLines ));
    }
    
    private static function row_to_vQuestLine($row) : vQuestLine
    {
        $questLine = new vQuestLine('',$row["Id"]);

        $questLine->title = $row["name"];
        $questLine->summary = $row["desc"];
        $questLine->dateCreated = new vDateTime();
        $questLine->dateCreated->setDateTimeFromString($row["date_created"]);
        $questLine->locator = $row["locator"];


        if ($row["content_id"] != null)
        {
            $questLine->content = new vContent('', $row["content_id"]);
        }
        else{
            $questLine->content = new vContent();
            $questLine->content->htmlContent = $row["desc"];
        }

        $questLine->createdBy = new vAccount('', $row["created_by_id"]);
        $questLine->createdBy->username = $row["created_by_username"];
        $questLine->reviewStatus = new vReviewStatus((bool)$row["published"], (bool)$row["being_reviewed"]);

        
        if ($row["image_id"] != null)
        {
            $banner = new vMedia('',$row["image_id"]);
            $banner->setMediaPath($row["imagePath"]);
            $questLine->banner = $banner;
        }

        if ($row["image_id_icon"] != null)
        {
            $icon = new vMedia('',$row["image_id_icon"]);
            $icon->setMediaPath($row["imagePath_icon"]);
            $questLine->icon = $icon;
        }

        if ($row["image_id_mobile"] != null)
        {
            $bannerMobile = new vMedia('',$row["image_id_mobile"]);
            $bannerMobile->setMediaPath($row["imagePath_mobile"]);
            $questLine->bannerMobile = $bannerMobile;
        }

        return $questLine;
    }
}


?>