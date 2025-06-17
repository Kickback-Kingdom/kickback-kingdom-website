<?php
declare(strict_types=1);
use Kickback\Backend\Controllers\BlogPostController;
use Kickback\Common\Utility\FormToken;
use Kickback\Backend\Views\vRecordId;
use Kickback\Common\Version;
use Kickback\Services\Session;

if (isset($_POST["submit-blog-post-publish"]))
{
    $tokenResponse = FormToken::useFormToken();
    
    if ($tokenResponse->success) {
        $blog_post_id = $_POST["blog-post-id"];

        $response = BlogPostController::publishBlogPost(new vRecordId('', (int)$blog_post_id));

        // Handle the response
        if ($response->success) {
            $showPopUpSuccess = true;
            $PopUpTitle = "Updated Blog Post";
            $PopUpMessage= "Your blog post has been published successfully.";
        } else {
            $showPopUpError = true;
            $PopUpTitle = "Error";
            $PopUpMessage = $response->message." -> ".json_encode($response->data);
        }
    }
    else {
        $hasError = true;
        $errorMessage = $tokenResponse->message;
    }
}

if (isset($_POST["submitBlogOptions"])) {
    
    
    /*$showPopUpSuccess = true;
    $PopUpTitle = "Recieved Data";
    $PopUpMessage = json_encode($_POST);*/

    $title = $_POST["blogPostOptionsTitle"];
    $locator = $_POST["blogPostOptionsLocator"];
    $desc = $_POST["blogPostOptionsDesc"];
    $imageId = (int)$_POST["blogPostOptionsIcon"];
    $postIdToUpdate = (int)$_POST["blogPostId"]; // You'll need a way to determine which post to update.

    $response = BlogPostController::updateBlogPost(new vRecordId('', $postIdToUpdate), $title, $locator, $desc, new vRecordId('', $imageId));

    // Handle the response
    if ($response->success) {
        $showPopUpSuccess = true;
        $PopUpTitle = "Updated Blog Post";
        $PopUpMessage= "Your changes have been saved successfully.";
        $newURL = Version::urlBetaPrefix().$response->data;
        Session::redirect($newURL);
    } else {
        $showPopUpError = true;
        $PopUpTitle = "Error";
        $PopUpMessage = $response->message." -> ".json_encode($response->data);
    }
}

?>
