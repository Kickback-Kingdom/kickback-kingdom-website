<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Backend\Views\vFeedRecord;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vQuest;
use Kickback\Backend\Views\vBlog;
use Kickback\Backend\Views\vBlogPost;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Models\PlayStyle;
use Kickback\Backend\Views\vQuestLine;
use Kickback\Backend\Views\vPageResult;

class FeedController
{
    public static function getQuestsByGameId(vRecordId $gameId, int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
    
        // Query to get the total number of items
        $totalItemsSql = "SELECT COUNT(*) AS total 
                          FROM v_feed f 
                          LEFT JOIN quest q ON f.Id = q.Id 
                          LEFT JOIN tournament t ON q.tournament_id = t.Id 
                          WHERE f.type = 'QUEST' AND t.game_id = ?";
    
        $totalItemsStmt = $conn->prepare($totalItemsSql);
        if ($totalItemsStmt === false) {
            return new Response(false, "Failed to prepare total items statement: " . $conn->error, []);
        }
    
        $gameIdValue = $gameId->crand;
        $totalItemsStmt->bind_param('i', $gameIdValue);
    
        if (!$totalItemsStmt->execute()) {
            return new Response(false, "Failed to execute total items statement: " . $totalItemsStmt->error, []);
        }
    
        $totalItemsResult = $totalItemsStmt->get_result();
        $totalItemsRow = $totalItemsResult->fetch_assoc();
        $totalItems = (int)$totalItemsRow['total'];
        $totalItemsStmt->close();
    
        // Query to get the paginated items
        $sql = "SELECT f.* 
                FROM v_feed f 
                LEFT JOIN quest q ON f.Id = q.Id 
                LEFT JOIN tournament t ON q.tournament_id = t.Id 
                WHERE f.type = 'QUEST' AND t.game_id = ? 
                ORDER BY f.date ASC 
                LIMIT ? OFFSET ?";
    
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, []);
        }
    
        $stmt->bind_param('iii', $gameIdValue, $itemsPerPage, $offset);
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }
    
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    
        $questsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);
    
        // Create the vPageResult object
        $pageResult = new vPageResult($totalItems, $questsList, $itemsPerPage, $page);
    
        return new Response(true, "Quests for Game ID: " . $gameIdValue, $pageResult);
    }
    

    public static function getAvailableQuestsFeed(int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' AND date > CURRENT_TIMESTAMP AND published = 1 ORDER BY date ASC LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, []);
        }

        $stmt->bind_param('ii', $itemsPerPage, $offset);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $newsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);

        return new Response(true, "Available Quests", $newsList);
    }

    public static function getArchivedQuestsFeed(int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' AND date <= CURRENT_TIMESTAMP AND published = 1 AND finished = 1 ORDER BY date DESC LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, []);
        }

        $stmt->bind_param('ii', $itemsPerPage, $offset);

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        $newsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);

        return new Response(true, "Archived Quests", $newsList);
    }

    public static function getTBAQuestsFeed(int $page = 1, int $itemsPerPage = 10): Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;

        if (!Session::isLoggedIn()) {
            return new Response(true, "TBA Quests", []);
        }

        if (Session::isMagisterOfTheAdventurersGuild()) {
            $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' AND published = 0 ORDER BY date DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                return new Response(false, "Failed to prepare statement: " . $conn->error, []);
            }
            $stmt->bind_param('ii', $itemsPerPage, $offset);
        } else {
            $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST' AND published = 0 AND (account_1_id = ? OR account_2_id = ?) ORDER BY date DESC LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                return new Response(false, "Failed to prepare statement: " . $conn->error, []);
            }
            $stmt->bind_param('iiii', Session::getCurrentAccount()->crand, Session::getCurrentAccount()->crand, $itemsPerPage, $offset);
        }

        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $newsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);

        return new Response(true, "TBA Quests", $newsList);
    }

    public static function getAvailableQuestLinesFeed(int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'QUEST-LINE' ORDER BY date ASC LIMIT ? OFFSET ?";
    
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return new Response(false, "Failed to prepare statement: " . $conn->error, []);
        }
    
        $stmt->bind_param('ii', $itemsPerPage, $offset);
    
        if (!$stmt->execute()) {
            return new Response(false, "Failed to execute statement: " . $stmt->error, []);
        }
    
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    
        $stmt->close();
    
        $newsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);
    
        return new Response(true, "Available Quest Lines", $newsList);
    }
    

    public static function getNeedsReviewedFeed(int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;

        $sql = "SELECT * FROM kickbackdb.v_feed WHERE being_reviewed = 1 LIMIT ? OFFSET ?";

        // Prepare the statement
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Failed to prepare statement: " . mysqli_error($conn));
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "ii", $itemsPerPage, $offset); // "ii" indicates two integer parameters

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            die("Failed to execute statement: " . mysqli_stmt_error($stmt));
        }

        // Get the result
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all the rows
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free the result
        mysqli_free_result($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        $newsList = [];
        foreach ($rows as $row) {
            $news = self::row_to_vFeedRecord($row);
            $newsList[] = $news;
        }

        return new Response(true, "news feed",  $newsList);
    }
    
    public static function getBlogsFeed(int $page = 1,int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'BLOG'";
    
        
        $result = mysqli_query($conn,$sql);
    
        $num_rows = mysqli_num_rows($result);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $newsList = array_map([self::class, 'row_to_vFeedRecord'], $rows);

        return (new Response(true, "blogs feed",  $newsList ));
    }

    public static function getNewsFeed(int $page = 1, int $itemsPerPage = 10) : Response {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;

        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type in ('QUEST','BLOG-POST') and published = 1 LIMIT ? OFFSET ?";

        // Prepare the statement
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("Failed to prepare statement: " . mysqli_error($conn));
        }

        // Bind parameters
        mysqli_stmt_bind_param($stmt, "ii", $itemsPerPage, $offset); // "ii" indicates two integer parameters

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            die("Failed to execute statement: " . mysqli_stmt_error($stmt));
        }

        // Get the result
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all the rows
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free the result
        mysqli_free_result($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        $newsList = [];
        foreach ($rows as $row) {
            $news = self::row_to_vFeedRecord($row);
            $newsList[] = $news;
        }

        return new Response(true, "news feed",  $newsList);
    }

    
    public static function getBlogFeed(string $blogLocator, int $page = 1, int $itemsPerPage = 10) : Response {
        // Prepare the SQL query with placeholders
        $conn = Database::getConnection();
        $offset = ($page - 1) * $itemsPerPage;
        $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'BLOG-POST' and `locator` LIKE ?";

        // Prepare the statement
        $stmt = mysqli_prepare($conn, $sql);

        // Check if the statement was prepared successfully
        if (!$stmt) {
            die("Failed to prepare statement: " . mysqli_error($conn));
        }

        // Bind the parameters
        $param = $blogLocator . "/%";
        mysqli_stmt_bind_param($stmt, "s", $param); // 's' denotes that the parameter is a string

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            die("Failed to execute statement: " . mysqli_stmt_error($stmt));
        }

        // Get the result
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all the rows
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Free the result
        mysqli_free_result($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        return (new Response(true, "blog feed", $rows));
    }

    private static function row_to_vFeedRecord(array $row) : vFeedRecord {
        $news = new vFeedRecord();
        $news->type = $row["type"];
        if (!is_null($row["date"]))
        {
            $dateTime = new vDateTime();
            $dateTime->setDateTimeFromString($row["date"]);
        }

        if ($news->type == "QUEST-LINE")
        {
            $questLine = new vQuestLine('', $row["Id"]);
            $questLine->locator = $row["locator"];
            $questLine->title = $row["title"];
            $questLine->summary = is_null($row["text"]) ? "" : $row["text"];
            $questLine->reviewStatus = new vReviewStatus((bool) $row["published"]);
            $questLine->dateCreated = $dateTime;

            if (!is_null($row["image"]))
            {
                $icon = new vMedia();
                $icon->setMediaPath($row["image"]);

                $questLine->icon = $icon;
            }
            else{
                $questLine->icon = vMedia::defaultIcon();
            }

            $news->questLine = $questLine;
        }

        if ($news->type == "QUEST")
        {
            $quest = new vQuest('', $row["Id"]);
            $quest->locator = $row["locator"];
            $quest->title = $row["title"];
            $quest->summary = is_null($row["text"]) ? "" : $row["text"];
            $quest->reviewStatus = new vReviewStatus((bool) $row["published"]);
            
            if (isset($dateTime))
                $quest->endDate = $dateTime;

            $quest->playStyle = PlayStyle::from((int)$row["style"]);

            if (!is_null($row["image"]))
            {
                $icon = new vMedia();
                $icon->setMediaPath($row["image"]);

                $quest->icon = $icon;
            }
            else{
                $quest->icon = vMedia::defaultIcon();
            }

            $host1 = new vAccount('', $row["account_1_id"]);
            $host1->username = $row["account_1_username"];

            $quest->host1 = $host1;


            if ($row["account_2_id"] != null && $row["account_2_username"] != null)
            {

                $host2 = new vAccount('', $row["account_2_id"]);
                $host2->username = $row["account_2_username"];
    
                $quest->host2 = $host2;
            }


            

            $news->quest = $quest;
        }

        if ($news->type == "BLOG-POST")
        {
            $blogPost = new vBlogPost('', $row["Id"]);
            //$blogPost->locator = $row["locator"];
            $blogPost->title = $row["title"];
            $blogPost->summary = $row["text"];
            $blogPost->publishedDateTime = $dateTime;
            $blogPost->setLocator($row["locator"]);
            $blogPost->reviewStatus = new vReviewStatus((bool) $row["published"]);


            $author = new vAccount('', $row["account_1_id"]);
            $author->username = $row["account_1_username"];
            $blogPost->author = $author;

            if (!empty($row["image"]))
            {
                $icon = new vMedia();
                $icon->setMediaPath($row["image"]);

                $blogPost->icon = $icon;
            }

            $news->blogPost = $blogPost;
        }

        if ($news->type == "BLOG")
        {
            $blog = new vBlog('',(int) $row["Id"]);

            $blog->title = $row["title"];
            $blog->description = $row["text"];
            $blog->locator = $row["locator"];

            if (isset($dateTime))
                $blog->lastPostDate = $dateTime;

            if ($row["account_1_id"] != null)
            {
                $author = new vAccount('',(int) $row["account_1_id"]);
                $author->username = $row["account_1_username"];
                $blog->lastWriter = $author;
            }


            if (!empty($row["image"]))
            {
                $icon = new vMedia();
                $icon->setMediaPath($row["image"]);

                $blog->icon = $icon;
            }

            $news->blog = $blog;
        }

        return $news;
    }
}
?>
