<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Views\vBlogPost;

class BlogPostController 
{
    
    public static function getBlogPostByLocators(string $blogLocator, string $postLocator) : Response {
        $sql = "SELECT * FROM v_blog_post_info WHERE Bloglocator = ? AND Postlocator = ? LIMIT 1";

        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
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
        $blogPost = new vBlogPost();



        return $blogPost;
    }
}

?>