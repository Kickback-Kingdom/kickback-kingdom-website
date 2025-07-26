<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Services\Database;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vServer;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vGame;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vAccount;

class ServerController
{
    public static function getAllServers(): Response {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                s.ctime, s.crand, s.name, s.description, s.region, s.locator,
                s.ip, s.port, s.password, s.join_method, s.is_official,
                s.server_version, s.content_id,
                s.is_public, s.requires_whitelist, s.is_online,
                s.current_players, s.max_players, s.tags_json,
                s.last_seen_online, s.last_seen_offline,

                g.Id as game_id, g.Name as game_name, g.locator as game_locator,
                a.Id as owner_id, a.username as owner_name,
                
                CONCAT(gi.Directory, '/', gi.Id, '.', gi.extension) AS game_icon_url,

                CONCAT(mi.Directory, '/', mi.Id, '.', mi.extension) AS icon_url,
                CONCAT(mb.Directory, '/', mb.Id, '.', mb.extension) AS banner_url,
                CONCAT(mm.Directory, '/', mm.Id, '.', mm.extension) AS banner_mobile_url

            FROM server s
            LEFT JOIN game g ON s.game_id = g.Id
            LEFT JOIN account a ON s.owner_id = a.Id
            LEFT JOIN Media mi ON s.media_icon_id = mi.Id
            LEFT JOIN Media mb ON s.media_banner_id = mb.Id
            LEFT JOIN Media mm ON s.media_banner_mobile_id = mm.Id
            LEFT JOIN Media gi ON g.media_icon_id = gi.Id


            ORDER BY s.name ASC
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt || !$stmt->execute()) {
            return new Response(false, "Failed to fetch server list.");
        }

        $result = $stmt->get_result();
        $servers = [];
        while ($row = $result->fetch_assoc()) {
            $servers[] = self::row_to_vServer($row);
        }

        $stmt->close();
        return new Response(true, "Servers loaded successfully.", $servers);
    }

    public static function queryServerByLocatorAsResponse(string $locator): Response {
        $conn = Database::getConnection();

        $sql = "
            SELECT 
                s.ctime, s.crand, s.name, s.description, s.region, s.locator,
                s.ip, s.port, s.password, s.join_method, s.is_official,
                s.server_version, s.content_id,
                s.is_public, s.requires_whitelist, s.is_online,
                s.current_players, s.max_players, s.tags_json,
                s.last_seen_online, s.last_seen_offline,

                g.Id as game_id, g.Name as game_name, g.locator as game_locator,
                a.Id as owner_id, a.name as owner_name,

                CONCAT(mi.Directory, '/', mi.Id, '.', mi.extension) AS icon_url,
                CONCAT(mb.Directory, '/', mb.Id, '.', mb.extension) AS banner_url,
                CONCAT(mm.Directory, '/', mm.Id, '.', mm.extension) AS banner_mobile_url

            FROM server s
            LEFT JOIN game g ON s.game_id = g.Id
            LEFT JOIN account a ON s.owner_id = a.Id
            LEFT JOIN Media mi ON s.media_icon_id = mi.Id
            LEFT JOIN Media mb ON s.media_banner_id = mb.Id
            LEFT JOIN Media mm ON s.media_banner_mobile_id = mm.Id
            WHERE s.locator = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $locator);
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute locator query.");
        }

        $result = $stmt->get_result();
        if (!$result || !$row = $result->fetch_assoc()) {
            return new Response(false, "Server not found.");
        }

        return new Response(true, "Server found.", self::row_to_vServer($row));
    }

    private static function row_to_vServer(array $row): vServer {
        $server = new vServer($row['ctime'], (int)$row['crand']);

        $server->name = $row['name'];
        $server->description = $row['description'];
        $server->region = $row['region'];
        $server->locator = $row['locator'];
        $server->ip = $row['ip'];
        $server->port = (int)$row['port'];
        $server->password = $row['password'] ?? null;
        $server->joinMethod = $row['join_method'];
        $server->isOfficial = (bool)$row['is_official'];
        $server->isPublic = (bool)$row['is_public'];
        $server->requiresWhitelist = (bool)$row['requires_whitelist'];
        $server->isOnline = (bool)$row['is_online'];
        $server->serverVersion = $row['server_version'];
        $server->currentPlayers = (int)$row['current_players'];
        $server->maxPlayers = (int)$row['max_players'];
        $server->tags = json_decode($row['tags_json'] ?? '[]', true) ?? [];

        $server->game = new vGame('', (int)$row['game_id']);
        $server->game->name = $row['game_name'];
        $server->game->locator = $row['game_locator'] ?? '';
        $server->game->icon = vMedia::fromUrl($row['game_icon_url'] ?? null);



        if (!empty($row['owner_id'])) {
            $owner = new vAccount('', (int)$row['owner_id']);
            $owner->username = $row['owner_username'] ?? '';
            $owner->firstName = $row['owner_first_name'] ?? '';
            $owner->lastName = $row['owner_last_name'] ?? '';
            $owner->isBanned = (bool)($row['owner_banned'] ?? 0);
            $server->owner = $owner;
        }
        

        $server->icon = vMedia::fromUrl($row['icon_url']);
        $server->banner = vMedia::fromUrl($row['banner_url']);
        $server->bannerMobile = vMedia::fromUrl($row['banner_mobile_url']);

        $contentId = (int)($row['content_id'] ?? -1);
        $server->content = new vContent('', $contentId);

        $server->lastSeenOnline = !empty($row['last_seen_online']) ? new vDateTime($row['last_seen_online']) : null;
        $server->lastSeenOffline = !empty($row['last_seen_offline']) ? new vDateTime($row['last_seen_offline']) : null;

        // Can be overridden elsewhere if needed
        $server->dateCreated = new vDateTime($row['ctime']);
        $server->lastUpdated = new vDateTime(); // optional: set from trigger or updated_at field if you have one

        return $server;
    }
}
