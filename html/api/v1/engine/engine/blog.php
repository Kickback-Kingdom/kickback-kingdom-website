<?php


////blog post

function BlogPostTitleIsValid($blogPostTitle)
{
    $valid = StringIsValid($blogPostTitle, 10);
    if ($valid) 
    {
        if (strtolower($blogPostTitle) == "new blog post")
            $valid = false;
    }

    return $valid;
}

function BlogPostSummaryIsValid($blogPostSummary) {
    $valid = StringIsValid($blogPostSummary, 200);

    return $valid;
}

function BlogPostPageContentIsValid($pageContent)
{

    return count($pageContent) > 0;
}

function BlogPostLocatorIsValid($locator)
{
    $valid = StringIsValid($locator, 5);
    if ($valid) 
    {
        if (strpos(strtolower($locator), 'new-post-') === 0) {
            $valid = false;
        }
    }

    return $valid;
}

function BlogPostIconIsValid($media_id)
{
    return isset($media_id) && !is_null($media_id);
}

function BlogPostIsValidForPublish($blogPost, $pageContent)
{
    return BlogPostTitleIsValid($blogPost["Title"]) && BlogPostSummaryIsValid($blogPost["Desc"]) && BlogPostLocatorIsValid($blogPost["Postlocator"]) && BlogPostPageContentIsValid($pageContent) && BlogPostIconIsValid($blogPost["Image_Id"]);
}

function IsWriterForBlogPost($blogPost)
{
    if ($blogPost == null)
    {
        return false;
    }
    if (IsLoggedIn())
    {
        return (IsManagerForBlog($blogPost["Blog_id"]) || $_SESSION["account"]["Id"] == $blogPost["Author_id"]) && !isset($_GET['borderless']);

    }
    else
    {
        return false;
    }
}

function IsWriterForBlog($blog_id)
{
    if (IsLoggedIn())
    {
        return AccountIsWriterForBlog($_SESSION["account"]["Id"], $blog_id)->Data;
    }
    else
    {
        return false;
    }
}

function IsManagerForBlog($blog_id)
{
    if (IsLoggedIn())
    {
        return AccountIsManagerForBlog($_SESSION["account"]["Id"], $blog_id)->Data;
    }
    else
    {
        return false;
    }
}


function PublishBlogPost($postId) {
    // Use global connection
    $db = $GLOBALS['conn'];

    // Fetch the current blog post information
    $currentBlogPost = GetBlogPostById($postId)->Data; // You would need a function like this


    //return new APIResponse(false, "testing", $currentBlogPost);

    // Check if the user has permissions
    if (!IsWriterForBlogPost($currentBlogPost)) {
        return (new APIResponse(false, "You do not have permission to edit this blog post.", null));
    }

    $pageContentResp = GetContentDataById($currentBlogPost["Content_id"],"BLOG-POST", $currentBlogPost["Bloglocator"]."/".$currentBlogPost["Postlocator"]);
    if (!$pageContentResp->Success)
    {
        return $pageContentResp;
    }

    $pageContent = $pageContentResp->Data;

    if (!BlogPostIsValidForPublish($currentBlogPost, $pageContent))
    {
        return (new APIResponse(false, "Your blog post isn't ready to publish.", null));
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
        return (new APIResponse(true, "Blog post published successfully!", null));
    } else {
        return (new APIResponse(false, "Error updating blog post.", null));
    }
}


function UpdateBlogPost($postId, $title, $locator, $desc, $imageId) {
    // Use global connection
    $db = $GLOBALS['conn'];

    if (!LocatorIsValidString($locator))
    {
        return (new APIResponse(false, "URL Locator is invalid", null));
    }

    // Fetch the current blog post information
    $currentBlogPost = GetBlogPostById($postId)->Data; // You would need a function like this


    //return new APIResponse(false, "testing", $currentBlogPost);

    // Check if the user has permissions
    if (!IsWriterForBlogPost($currentBlogPost)) {
        return (new APIResponse(false, "You do not have permission to edit this blog post.", null));
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
        return (new APIResponse(true, "Blog post updated successfully!", "/blog/".$currentBlogPost["Bloglocator"]."/".$locator));
    } else {
        return (new APIResponse(false, "Error updating blog post.", null));
    }
}


function AccountIsWriterForBlog($account_id, $blog_id)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT IsManager, IsWriter FROM v_blog_permissions WHERE account_id = ? AND blog_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $account_id, $blog_id); 
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    
    $row = mysqli_fetch_assoc($result);
    $num_rows = mysqli_num_rows($result);
    
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Account or Blog not found.", false));
    }
    else
    {
        if($row['IsManager'] == 1 || $row['IsWriter'] == 1) 
        {
            return (new APIResponse(true, "The account is a writer for the blog.", true));
        } 
        else 
        {
            return (new APIResponse(false, "The account is not a writer for the blog.", false));
        }
    }
}


function AccountIsManagerForBlog($account_id, $blog_id)
{
    $stmt = mysqli_prepare($GLOBALS["conn"], "SELECT IsManager FROM v_blog_permissions WHERE account_id = ? AND blog_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $account_id, $blog_id); 
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    
    $row = mysqli_fetch_assoc($result);
    $num_rows = mysqli_num_rows($result);
    
    if ($num_rows === 0)
    {
        return (new APIResponse(false, "Account or Blog not found.", false));
    }
    else
    {
        if($row['IsManager'] == 1) 
        {
            return (new APIResponse(true, "The account is a manager for the blog.", true));
        } 
        else 
        {
            return (new APIResponse(false, "The account is not a manager for the blog.", false));
        }
    }
}

function GetBlogFeed($blogLocator,$page = 1, $itemsPerPage = 10) {
    // Prepare the SQL query with placeholders
    $offset = ($page - 1) * $itemsPerPage;
    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'BLOG-POST' and `locator` LIKE ?";

    // Prepare the statement
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);

    // Check if the statement was prepared successfully
    if (!$stmt) {
        die("Failed to prepare statement: " . mysqli_error($GLOBALS["conn"]));
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

    return (new APIResponse(true, "blog feed", $rows));
}

function GetBlogsFeed($page = 1, $itemsPerPage = 10)
{
    $offset = ($page - 1) * $itemsPerPage;
    $sql = "SELECT * FROM kickbackdb.v_feed WHERE type = 'BLOG'";

    
    $result = mysqli_query($GLOBALS["conn"],$sql);

    $num_rows = mysqli_num_rows($result);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "blogs feed",  $rows ));
}


function GetAllBlogs() {
    $sql = "SELECT * FROM v_blog_info ORDER BY Id DESC";  // Adjust ordering as needed

    $result = mysqli_query($GLOBALS["conn"], $sql);

    $num_rows = mysqli_num_rows($result); // This line is redundant since you are not using $num_rows in this function
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return (new APIResponse(true, "Available Blogs",  $rows));
}

function GetAllBlogPostsForBlog($blogId) {
    $sql = "SELECT * FROM v_blog_post_info WHERE Blog_id = ? ORDER BY PostDate DESC";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed."));
    }

    // Bind the blog ID parameter
    mysqli_stmt_bind_param($stmt, "i", $blogId);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed."));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Close the statement
    mysqli_stmt_close($stmt);

    return (new APIResponse(true, "Blog posts retrieved successfully.", $rows));
}

function GetBlogPostById($postId) {
    $sql = "SELECT * FROM v_blog_post_info WHERE Id = ? LIMIT 1";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed.", null));
    }

    // Bind the ID parameter
    mysqli_stmt_bind_param($stmt, "i", $postId);  // 'i' means the parameter is an integer

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed.", null));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if a row was returned
    if (!$row) {
        return (new APIResponse(false, "Blog post not found.", null));
    }

    return (new APIResponse(true, "Blog post retrieved successfully.", $row));
}

function GetBlogPostByLocators($blogLocator, $postLocator) {
    $sql = "SELECT * FROM v_blog_post_info WHERE Bloglocator = ? AND Postlocator = ? LIMIT 1";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed.", null));
    }

    // Bind the locator parameters
    mysqli_stmt_bind_param($stmt, "ss", $blogLocator, $postLocator);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed.", null));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if a row was returned
    if (!$row) {
        return (new APIResponse(false, "Blog post not found.", null));
    }

    return (new APIResponse(true, "Blog post retrieved successfully.", $row));
}

function InsertNewBlogPost($blog_id, $blog_locator)
{
    global $conn;

    // Generate title and details within the function
    $title = "";
    $postLocator = "new-post-".$blog_id."-" . $_SESSION["account"]["Id"];
    // Use GetBlogPostByLocators to check if a blog post already exists
    
    $existingPostResp = GetBlogPostByLocators($blog_locator, $postLocator);
    if($existingPostResp->Success) {
        // If the post already exists, return it
        return (new APIResponse(true, "Blog post already exists.", $existingPostResp->Data));
    }
    
    // If no existing post, create new one
    if (IsWriterForBlog($blog_id))
    {
        $desc = "";

        // Insert new content
        $content_id = InsertNewContent();

        // Insert new blog post
        $stmt = $conn->prepare("INSERT INTO blog_post (Blog_id, Title, `Desc`, `Locator`, Author_id, Content_id) values (?,?,?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'isssii', $blog_id, $title, $desc, $postLocator, $_SESSION["account"]["Id"], $content_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt); // Close the statement after execution

        // Use GetBlogPostByLocators again to get the newly created post
        $postResp = GetBlogPostByLocators($blog_locator, $postLocator);

        if($postResp->Success) {
            return (new APIResponse(true, "New blog post created.", $postResp->Data));
        } else {
            return (new APIResponse(false, "Error retrieving the created blog post.", null));
        }
    }
    else
    {
        return (new APIResponse(false, "You do not have permissions to post a new blog post.", null));
    }
}

function GetBlogByLocator($locator) {
    $sql = "SELECT * FROM v_blog_info WHERE locator = ? LIMIT 1";

    // Prepare the SQL statement using the mysqli connection
    $stmt = mysqli_prepare($GLOBALS["conn"], $sql);
    if (!$stmt) {
        return (new APIResponse(false, "SQL statement preparation failed."));
    }

    // Bind the locator parameter
    mysqli_stmt_bind_param($stmt, "s", $locator);

    // Execute the statement
    if (!mysqli_stmt_execute($stmt)) {
        return (new APIResponse(false, "Query execution failed."));
    }

    // Fetch the results
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Close the statement
    mysqli_stmt_close($stmt);

    // Check if a row was returned
    if (!$row) {
        return (new APIResponse(false, "Blog not found."));
    }

    return (new APIResponse(true, "Blog retrieved successfully.", $row));
}

function GetNewBlogPostAnnouncement($blogPost) {
    $blogName = $blogPost["BlogName"];
    $postTitle = $blogPost["Title"];
    $postUrl = "https://kickback-kingdom.com/blog/".$blogPost["Bloglocator"]."/".$blogPost["Postlocator"];
    $writerUsername = $blogPost["Author_Username"];
    return "Exciting News from $blogName! 🌟 Our talented writer $writerUsername has just published a new blog post titled '$postTitle'. Dive into this captivating read at $postUrl";
}

?>