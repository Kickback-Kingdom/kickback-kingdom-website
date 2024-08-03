<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Views\vBlog;
use Kickback\Services\Database;
use Kickback\Views\vMedia;
use Kickback\Views\vItem;
use Kickback\Views\vAccount;
use Kickback\Views\vDateTime;

class BlogController 
{
    public static function getBlogByLocator(string $locator) : Response {
        $sql = "SELECT * FROM v_blog_info WHERE locator = ? LIMIT 1";

        $conn = Database::getConnection();
        // Prepare the SQL statement using the mysqli connection
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return (new Response(false, "SQL statement preparation failed."));
        }

        // Bind the locator parameter
        mysqli_stmt_bind_param($stmt, "s", $locator);

        // Execute the statement
        if (!mysqli_stmt_execute($stmt)) {
            return (new Response(false, "Query execution failed."));
        }

        // Fetch the results
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        // Close the statement
        mysqli_stmt_close($stmt);

        // Check if a row was returned
        if (!$row) {
            return (new Response(false, "Blog not found."));
        }

        return (new Response(true, "Blog retrieved successfully.", self::row_to_vBlog($row)));
    }

    public static function getAllBlogs() : Response {
        $conn = Database::getConnection();
        $sql = "SELECT * FROM v_blog_info ORDER BY Id DESC";  // Adjust ordering as needed
    
        $result = mysqli_query($conn, $sql);
    
        $num_rows = mysqli_num_rows($result); // This line is redundant since you are not using $num_rows in this function
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        $newsList = array_map([self::class, 'row_to_vBlog'], $rows);

        return (new Response(true, "Available Blogs",  $newsList));
    }
    
    public static function accountIsWriter(vAccount $account, vBlog $blog) : Response {
        $conn = Database::getConnection();

        $stmt = mysqli_prepare($conn, "SELECT IsManager, IsWriter FROM v_blog_permissions WHERE account_id = ? AND blog_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $account->crand, $blog->crand); 
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        
        $row = mysqli_fetch_assoc($result);
        $num_rows = mysqli_num_rows($result);
        
        if ($num_rows === 0)
        {
            return (new Response(false, "Account or Blog not found.", false));
        }
        else
        {
            if($row['IsManager'] == 1 || $row['IsWriter'] == 1) 
            {
                return (new Response(true, "The account is a writer for the blog.", true));
            } 
            else 
            {
                return (new Response(false, "The account is not a writer for the blog.", false));
            }
        }
    }

    public static function accountIsManager(vAccount $account, vBlog $blog) : Response {
        
        $conn = Database::getConnection();
        $stmt = mysqli_prepare($conn, "SELECT IsManager FROM v_blog_permissions WHERE account_id = ? AND blog_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $account->crand, $blog->crand); 
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        
        $row = mysqli_fetch_assoc($result);
        $num_rows = mysqli_num_rows($result);
        
        if ($num_rows === 0)
        {
            return (new Response(false, "Account or Blog not found.", false));
        }
        else
        {
            if($row['IsManager'] == 1) 
            {
                return (new Response(true, "The account is a manager for the blog.", true));
            } 
            else 
            {
                return (new Response(false, "The account is not a manager for the blog.", false));
            }
        }
    }

    private static function row_to_vBlog(array $row) : vBlog {
        $blog = new vBlog('', $row["Id"]);

        $blog->title = $row["name"];
        $blog->description = $row["desc"];
        $blog->locator = $row["locator"];
        
        if ($row["image_id"] != null)
        {

            $icon = new vMedia('', $row["image_id"]);
            $icon->setMediaPath($row["imagePath"]);
    
            $blog->icon = $icon;
        }
        else{
            $blog->icon = vMedia::defaultIcon();
        }

        if ($row["manager_item_id"] != null)
        {
            $blog->managerItem = new vItem('', $row["manager_item_id"]);
        }

        if ($row["writer_item_id"] != null)
        {
            $blog->writerItem = new vItem('', $row["writer_item_id"]);
        }

        if ($row["LastWriterId"] != null)
        {
            $lastWriter = new vAccount('', $row["LastWriterId"]);
            $lastWriter->username = $row["LastWriterUsername"];

            $blog->lastWriter = $lastWriter;
        }

        if ($row["LastPostDate"] != null)
        {
            $blog->lastPostDate = new vDateTime($row["LastPostDate"]);
        }

        return $blog;
    }
}
?>