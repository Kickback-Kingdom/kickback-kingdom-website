<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vBlogPost;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vMedia;
use Kickback\Services\Database;

class BlogPostController 
{
    
    public static function getBlogPostByLocators(string $blogLocator, string $postLocator) : Response {
        
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_blog_post_info WHERE Bloglocator = ? AND Postlocator = ? LIMIT 1";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return (new Response(false, "SQL statement preparation failed.", null));
        }

        // Bind the locator parameters
        mysqli_stmt_bind_param($stmt, "ss", $blogLocator, $postLocator);

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return (new Response(false, "Query execution failed.", null));
        }

        // Fetch the results
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if a row was returned
        if (!$row) {
            return (new Response(false, "Blog post not found.", null));
        }

        return (new Response(true, "Blog post retrieved successfully.", self::row_to_vBlogPost($row)));
    }

    private static function row_to_vBlogPost(array $row) : vBlogPost {
        $blogPost = new vBlogPost('', $row["Id"]);

        $blogPost->title = $row["Title"];
        $blogPost->summary = $row["Desc"];
        $blogPost->content = new vContent('', $row["Content_id"]);
        $blogPost->publishedDateTime = new vDateTime($row["PostDate"]);
        $blogPost->postLocator = $row["Postlocator"];
        $blogPost->blogLocator = $row["Bloglocator"];

        $blogPost->blog = BlogController::getBlogByLocator($blogPost->blogLocator)->data;

        $author = new vAccount('', $row["Author_id"]);
        $author->username = $row["Author_Username"];

        $blogPost->author = $author;

        $blogPost->reviewStatus = new vReviewStatus((bool)$row["BlogPublished"]);

        if ($row["Image_Id"] != null)
        {
            $icon = new vMedia('', $row["Image_Id"]);
            $icon->setMediaPath($row["Image_Path"]);

            $blogPost->icon = $icon;
        }

        if ($row["Prev_Locator"] != null)
        {
            $prevBlogPost = new vBlogPost();
            $prevBlogPost->title = $row["Prev_Title"];
            $prevBlogPost->publishedDateTime = new vDateTime($row["Prev_PostDate"]);
            
            $prevAuthor = new vAccount();
            $prevAuthor->username = $row["Prev_Author"];
            
            $prevBlogPost->author = $prevAuthor;

            $prevIcon = new vMedia();
            $prevIcon->setMediaPath($row["Prev_Image_Path"]);

            $prevBlogPost->icon = $prevIcon;

            $prevBlogPost->postLocator = $row["Prev_Locator"];
            $prevBlogPost->content = new vContent('', $row["Prev_Content_id"]);
            $prevBlogPost->summary = $row["Prev_Desc"];

            $blogPost->prevBlogPost = $prevBlogPost;
        }

        if ($row["Next_Locator"] != null)
        {
            $nextBlogPost = new vBlogPost();
            $nextBlogPost->title = $row["Next_Title"];
            $nextBlogPost->publishedDateTime = new vDateTime($row["Next_PostDate"]);
            
            $prevAuthor = new vAccount();
            $prevAuthor->username = $row["Next_Author"];
            
            $nextBlogPost->author = $prevAuthor;

            $nextIcon = new vMedia();
            $nextIcon->setMediaPath($row["Next_Image_Path"]);

            $nextBlogPost->icon = $nextIcon;

            $nextBlogPost->postLocator = $row["Next_Locator"];
            $nextBlogPost->content = new vContent('', $row["Next_Content_id"]);
            $nextBlogPost->summary = $row["Next_Desc"];

            $blogPost->nextBlogPost = $nextBlogPost;
        }

        return $blogPost;
    }
}

?>