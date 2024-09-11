<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vBlogPost;
use Kickback\Backend\Views\vBlog;
use Kickback\Backend\Views\vContent;
use Kickback\Backend\Views\vDateTime;
use Kickback\Backend\Views\vAccount;
use Kickback\Backend\Views\vReviewStatus;
use Kickback\Backend\Views\vMedia;
use Kickback\Backend\Views\vRecordId;
use Kickback\Services\Database;
use Kickback\Services\Session;
use Kickback\Backend\Controllers\SocialMediaController;

class BlogPostController 
{
    
    public static function locatorIsValidString(string $str) : bool {
        // The regex checks for a string made up of only letters (a-z, A-Z), numbers (0-9), underscores, or hyphens.
        return preg_match('/^[a-zA-Z0-9_-]+$/', $str) === 1;
    }

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

    public static function getBlogPostById(vRecordId $postId): Response {
        $conn = Database::getConnection();

        $sql = "SELECT * FROM v_blog_post_info WHERE Id = ? LIMIT 1";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return new Response(false, "SQL statement preparation failed.", null);
        }

        // Bind the ID parameter
        mysqli_stmt_bind_param($stmt, "i", $postId->crand);  // 'i' means the parameter is an integer

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return new Response(false, "Query execution failed.", null);
        }

        // Fetch the results
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if a row was returned
        if (!$row) {
            return new Response(false, "Blog post not found.", null);
        }

        // Convert the row into a vBlogPost object and return it
        return new Response(true, "Blog post retrieved successfully.", self::row_to_vBlogPost($row));
    }

    public static function publishBlogPost(vRecordId $postId): Response {
        $conn = Database::getConnection();

        // Fetch the current blog post information
        $currentBlogPostResp = self::getBlogPostById($postId);
        if (!$currentBlogPostResp->success) {
            return new Response(false, "Blog post not found.", null);
        }

        $currentBlogPost = $currentBlogPostResp->data;

        // Check if the user has permissions
        if (!$currentBlogPost->isWriter()) {
            return new Response(false, "You do not have permission to publish this blog post.", null);
        }

        // Fetch content data for the blog post
        $pageContentResp = ContentController::getContentDataById($currentBlogPost->content, "BLOG-POST", $currentBlogPost->blogLocator . "/" . $currentBlogPost->postLocator);
        if (!$pageContentResp->success) {
            return $pageContentResp;
        }

        $pageContent = $pageContentResp->data;
        $currentBlogPost->content->pageContent = $pageContent;
        // Check if the blog post is valid for publishing
        if (!$currentBlogPost->isValidForPublish()) {
            return new Response(false, "Your blog post isn't ready to publish.", null);
        }

        // Prepare the update statement to publish the blog post
        $query = "UPDATE blog_post SET Published = 1, PostDate = NOW() WHERE Id = ?";
        $stmt = mysqli_prepare($conn, $query);

        // Bind the postId parameter
        mysqli_stmt_bind_param($stmt, 'i', $postId->crand);

        // Execute the statement
        $success = mysqli_stmt_execute($stmt);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if the update was successful
        if ($success) {
            // Send the blog post announcement via Discord webhook
            $msg = FlavorTextController::getNewBlogPostAnnouncement($currentBlogPost);
            SocialMediaController::discordWebHook($msg);

            return new Response(true, "Blog post published successfully!", null);
        } else {
            return new Response(false, "Error publishing the blog post.", null);
        }
    }

    public static function updateBlogPost(vRecordId $postId, string $title, string $locator, string $desc, vRecordId $imageId): Response {
        $conn = Database::getConnection();

        // Validate the locator string
        if (!self::locatorIsValidString($locator)) {
            return new Response(false, "URL Locator is invalid", null);
        }

        // Fetch the current blog post information
        $currentBlogPostResp = self::getBlogPostById($postId); // Assume this method is implemented
        if (!$currentBlogPostResp->success) {
            return new Response(false, "Blog post not found.", null);
        }

        $currentBlogPost = $currentBlogPostResp->data;

        // Check if the user has permissions
        if (!$currentBlogPost->isWriter()) {
            return new Response(false, "You do not have permission to edit this blog post.", null);
        }

        // Prepare the update statement
        $query = "UPDATE blog_post SET Title = ?, Locator = ?, `Desc` = ?, Image_id = ? WHERE Id = ?";
        $stmt = mysqli_prepare($conn, $query);

        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'sssii', $title, $locator, $desc, $imageId->crand, $postId->crand);

        // Execute the statement
        $success = mysqli_stmt_execute($stmt);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if the update was successful
        if ($success) {
            return new Response(true, "Blog post updated successfully!", "/blog/" . $currentBlogPost->blogLocator . "/" . $locator);
        } else {
            return new Response(false, "Error updating blog post.", null);
        }
    }

    public static function insertNewBlogPost(vRecordId $blogId, string $blogLocator): Response {
        $conn = Database::getConnection();

        // Generate title and details within the function
        $title = "";
        $postLocator = "new-post-" . $blogId->crand . "-" . Session::getCurrentAccount()->crand;

        // Use getBlogPostByLocators to check if a blog post already exists
        $existingPostResp = self::getBlogPostByLocators($blogLocator, $postLocator);
        if ($existingPostResp->success) {
            // If the post already exists, return it
            return new Response(true, "Blog post already exists.", $existingPostResp->data);
        }

        
        // If no existing post, create a new one
        if (BlogController::accountIsWriter(Session::getCurrentAccount(), new vBlog('', $blogId->crand))) {
            $desc = "";

            // Insert new content
            $contentId = ContentController::insertNewContent();

            // Insert new blog post
            $stmt = $conn->prepare("INSERT INTO blog_post (Blog_id, Title, `Desc`, `Locator`, Author_id, Content_id) VALUES (?,?,?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isssii', $blogId->crand, $title, $desc, $postLocator, Session::getCurrentAccount()->crand, $contentId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Retrieve the newly created post
            $postResp = self::getBlogPostByLocators($blogLocator, $postLocator);

            if ($postResp->success) {
                return new Response(true, "New blog post created.", $postResp->data);
            } else {
                return new Response(false, "Error retrieving the created blog post.", null);
            }
        } else {
            return new Response(false, "You do not have permissions to post a new blog post.", null);
        }
    }

    private static function row_to_vBlogPost(array $row, ?vBlog $blog = null) : vBlogPost {
        $blogPost = new vBlogPost('', $row["Id"]);

        $blogPost->title = $row["Title"];
        $blogPost->summary = $row["Desc"];
        $blogPost->content = new vContent('', $row["Content_id"]);
        $blogPost->publishedDateTime = new vDateTime($row["PostDate"]);
        $blogPost->postLocator = $row["Postlocator"];
        $blogPost->blogLocator = $row["Bloglocator"];

        if ($blog == null)
        {
            $blog = BlogController::getBlogByLocator($blogPost->blogLocator)->data;
        }
        $blogPost->blog = $blog;

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
        else{
            $blogPost->icon = vMedia::defaultIcon();
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