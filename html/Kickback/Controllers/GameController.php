<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Game;
use Kickback\Views\vGame;
use Kickback\Views\vMedia;
use Kickback\Models\Response;
use Kickback\Services\Database;

class GameController
{
    public static function getGames($rankedOnly = false, $searchTerm = '', $page = 0, $pageSize = 0): Response {
        $conn = Database::getConnection();
    
        $sql = "SELECT Id, `Name`, `Desc`, MinRankedMatches, ShortName, CanRank, media_icon_id, media_banner_id, media_banner_mobile_id, icon_path, banner_path, banner_mobile_path 
                FROM kickbackdb.v_game_info";
    
        $conditions = [];
        $params = [];
        $types = '';
    
        if ($rankedOnly) {
            $conditions[] = "CanRank = ?";
            $params[] = 1;
            $types .= 'i';
        }
    
        // Search term filter
        if (!empty($searchTerm)) {
            $conditions[] = "(Name LIKE ? OR Desc LIKE ?)";
            $params[] = "%{$searchTerm}%";
            $params[] = "%{$searchTerm}%";
            $types .= 'ss';
        }
    
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
    
        $sql .= " ORDER BY Name";
    
        // Pagination handling
        if ($page >= 0 && $pageSize > 0) {
            $offset = $page * $pageSize;
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $pageSize;
            $types .= 'ii';
        }
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return new Response(false, "Failed to prepare the SQL statement.");
        }

        if (!empty($params)) {
            if (!$stmt->bind_param($types, ...$params)) {
                return new Response(false, "Failed to bind parameters.");
            }
        }
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute the SQL statement.");
        }
    
        $result = $stmt->get_result();
        if (!$result) {
            return new Response(false, "Failed to retrieve the result set.");
        }
    
        
        $games = [];
        while ($row = $result->fetch_assoc()) {
            $game = self::row_to_vGame($row);
            $games[] = $game;
        }

        $stmt->close();
    
        return new Response(true, "Retrieved games successfully", $games);
    }
    
    private static function row_to_vGame($row) : vGame
    {
        $game = new vGame('', $row['Id']);
        $game->name = $row['Name'];
        $game->description = $row['Desc'];
        $game->minRankedMatches = $row['MinRankedMatches'];
        $game->shortName = $row['ShortName'];
        $game->canRank = $row['CanRank'] == 1;

        if ($row['media_icon_id'] != null)
        {
            $icon = new vMedia('', $row['media_icon_id']);
            $icon->mediaPath = $row['icon_path'];
            $game->icon = $icon;
        }

        if ($row['media_banner_id'] != null)
        {
            $banner = new vMedia('', $row['media_banner_id']);
            $banner->mediaPath = $row['banner_path'];
            $game->banner = $banner;
        }

        if ($row['media_banner_mobile_id'] != null)
        {
            $bannerMobile = new vMedia('', $row['media_banner_mobile_id']);
            $bannerMobile->mediaPath = $row['banner_mobile_path'];
            $game->bannerMobile = $bannerMobile;
        }

        return $game;
    }

    private static function insert(Game $game) : Response {

        return new Response(false, 'GameController::Insert not implemented');

    }
}
?>
