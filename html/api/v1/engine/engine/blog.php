<?php


////blog post


function PublishBlogPost($postId) {
    // Use global connection
    $db = $GLOBALS['conn'];

    // Fetch the current blog post information
    $currentBlogPost = GetBlogPostById($postId)->data; // You would need a function like this


    //return new Kickback\Models\Response(false, "testing", $currentBlogPost);

    // Check if the user has permissions
    if (!IsWriterForBlogPost($currentBlogPost)) {
        return (new Kickback\Models\Response(false, "You do not have permission to edit this blog post.", null));
    }

    $pageContentResp = GetContentDataById($currentBlogPost["Content_id"],"BLOG-POST", $currentBlogPost["Bloglocator"]."/".$currentBlogPost["Postlocator"]);
    if (!$pageContentResp->success)
    {
        return $pageContentResp;
    }

    $pageContent = $pageContentResp->data;

    if (!BlogPostIsValidForPublish($currentBlogPost, $pageContent))
    {
        return (new Kickback\Models\Response(false, "Your blog post isn't ready to publish.", null));
    }

    // Prepare the update statement
    $query = "UPDATE blog_post SET Published=1, PostDate = NOW() WHERE Id=?";
    $stmt = mysqli_prepare($db, $query);

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'i', $postId);

    // Execute the statement
    $success = mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if the update was successful
    if($success) {
        
        $msg = GetNewBlogPostAnnouncement($currentBlogPost);
        DiscordWebHook($msg);
        return (new Kickback\Models\Response(true, "Blog post published successfully!", null));
    } else {
        return (new Kickback\Models\Response(false, "Error updating blog post.", null));
    }
}


function UpdateBlogPost($postId, $title, $locator, $desc, $imageId) {
    // Use global connection
    $db = $GLOBALS['conn'];

    if (!LocatorIsValidString($locator))
    {
        return (new Kickback\Models\Response(false, "URL Locator is invalid", null));
    }

    // Fetch the current blog post information
    $currentBlogPost = GetBlogPostById($postId)->data; // You would need a function like this


    //return new Kickback\Models\Response(false, "testing", $currentBlogPost);

    // Check if the user has permissions
    if (!IsWriterForBlogPost($currentBlogPost)) {
        return (new Kickback\Models\Response(false, "You do not have permission to edit this blog post.", null));
    }

    // Prepare the update statement
    $query = "UPDATE blog_post SET Title=?, Locator=?, `Desc`=?, Image_id=? WHERE Id=?";
    $stmt = mysqli_prepare($db, $query);

    // Bind the parameters
    mysqli_stmt_bind_param($stmt, 'sssii', $title, $locator, $desc, $imageId, $postId);

    // Execute the statement
    $success = mysqli_stmt_execute($stmt);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if the update was successful
    if($success) {
        return (new Kickback\Models\Response(true, "Blog post updated successfully!", "/blog/".$currentBlogPost["Bloglocator"]."/".$locator));
    } else {
        return (new Kickback\Models\Response(false, "Error updating blog post.", null));
    }
}



function GetAllBlogPostsForBlog($blogId) {
    $sql = "SELECT * FROM v_blog_post_info WHERE Blog_id = ? ORDER BY PostDate DESC";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new Kickback\Models\Response(false, "SQL statement preparation failed."));
    }

    // Bind the blog ID parameter
    mysqli_stmt_bind_param($stmt, "i", $blogId);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new Kickback\Models\Response(false, "Query execution failed."));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Close the statement
    mysqli_stmt_close($stmt);

    return (new Kickback\Models\Response(true, "Blog posts retrieved successfully.", $rows));
}

function GetBlogPostById($postId) {
    $sql = "SELECT * FROM v_blog_post_info WHERE Id = ? LIMIT 1";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new Kickback\Models\Response(false, "SQL statement preparation failed.", null));
    }

    // Bind the ID parameter
    mysqli_stmt_bind_param($stmt, "i", $postId);  // 'i' means the parameter is an integer

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new Kickback\Models\Response(false, "Query execution failed.", null));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if a row was returned
    if (!$row) {
        return (new Kickback\Models\Response(false, "Blog post not found.", null));
    }

    return (new Kickback\Models\Response(true, "Blog post retrieved successfully.", $row));
}

function InsertNewBlogPost($blog_id, $blog_locator)
{
    global $conn;

    // Generate title and details within the function
    $title = "";
    $postLocator = "new-post-".$blog_id."-" . Kickback\Services\Session::getCurrentAccount()->crand;
    // Use GetBlogPostByLocators to check if a blog post already exists
    
    $existingPostResp = GetBlogPostByLocators($blog_locator, $postLocator);
    if($existingPostResp->success) {
        // If the post already exists, return it
        return (new Kickback\Models\Response(true, "Blog post already exists.", $existingPostResp->data));
    }
    
    // If no existing post, create new one
    if (IsWriterForBlog($blog_id))
    {
        $desc = "";

        // Insert new content
        $content_id = InsertNewContent();

        // Insert new blog post
        $stmt = $conn->prepare("INSERT INTO blog_post (Blog_id, Title, `Desc`, `Locator`, Author_id, Content_id) values (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'isssii', $blog_id, $title, $desc, $postLocator, Kickback\Services\Session::getCurrentAccount()->crand, $content_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt); // Close the statement after execution

        // Use GetBlogPostByLocators again to get the newly created post
        $postResp = GetBlogPostByLocators($blog_locator, $postLocator);

        if($postResp->success) {
            return (new Kickback\Models\Response(true, "New blog post created.", $postResp->data));
        } else {
            return (new Kickback\Models\Response(false, "Error retrieving the created blog post.", null));
        }
    }
    else
    {
        return (new Kickback\Models\Response(false, "You do not have permissions to post a new blog post.", null));
    }
}


function GetNewBlogPostAnnouncement($blogPost) {
    $blogName = $blogPost["BlogName"];
    $postTitle = $blogPost["Title"];
    $postUrl = "https://kickback-kingdom.com/blog/".$blogPost["Bloglocator"]."/".$blogPost["Postlocator"];
    $writerUsername = $blogPost["Author_Username"];
    return "Exciting News from $blogName! 🌟 Our talented writer $writerUsername has just published a new blog post titled '$postTitle'. Dive into this captivating read at $postUrl";
}

?>