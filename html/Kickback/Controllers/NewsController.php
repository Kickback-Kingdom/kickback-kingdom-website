<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vNews;
use Kickback\Views\vRecordId;
use Kickback\Views\vAccount;
use Kickback\Views\vMedia;
use Kickback\Views\vQuest;
use Kickback\Views\vBlogPost;

class NewsController
{
    public static function getNeedsReviewedFeed(int $page = 1, int $itemsPerPage = 10) : Response
    {
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
            $news = self::row_to_vNews($row);
            $newsList[] = $news;
        }

        return new Response(true, "news feed",  $newsList);
    }

    public static function getNewsFeed(int $page = 1, int $itemsPerPage = 10) : Response
    {
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
            $news = self::row_to_vNews($row);
            $newsList[] = $news;
        }

        return new Response(true, "news feed",  $newsList);
    }

    private static function row_to_vNews(array $row) : vNews
    {
        $news = new vNews();
        $news->type = $row["type"];

        if ($news->type == "QUEST")
        {
            $quest = new vQuest('', $row["Id"]);
            $quest->locator = $row["locator"];
            $quest->title = $row["title"];
            $quest->summary = $row["text"];
            if (!empty($row["image"]))
            {
                $icon = new vMedia();
                $icon->mediaPath = $row["image"];

                $quest->icon = $icon;
            }

            $host1 = new vAccount('', $row["account_1_id"]);
            $host1->username = $row["account_1_username"];

            $quest->host1 = $host1;


            if ($row["account_2_id"] != null)
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
            $blogPost->locator = $row["locator"];
            $blogPost->title = $row["title"];
            $blogPost->summary = $row["text"];
            if (!empty($row["image"]))
            {
                $icon = new vMedia();
                $icon->mediaPath = $row["image"];

                $blogPost->icon = $icon;
            }

            $news->blogPost = $blogPost;
        }

        return $news;
    }
}
?>
