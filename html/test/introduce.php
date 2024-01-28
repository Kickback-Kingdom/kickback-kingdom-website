<?php

$session = require($_SERVER['DOCUMENT_ROOT']."/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

//$msg = GetNewcomerIntroduction("FireFoxGirl");
//echo $msg;

//DiscordWebHook($msg);



//$blogPost = GetBlogPostById(15)->Data;
// Example usage
//$msg = GetNewBlogPostAnnouncement($blogPost);
//DiscordWebHook($msg);


/*$raffleQuest = GetQuestByRaffleId(1)->Data;
$raffleWinner = GetRaffleWinner(1)->Data;
$msg = GetRaffleWinnerAnnouncement($raffleQuest["name"], json_encode($raffleWinner[0]["Username"]));

echo $msg;*/

/*
function postTweet($message, $apiKey, $apiSecretKey, $accessToken, $accessTokenSecret) {
    $url = 'https://api.twitter.com/2/tweets'; // Endpoint for Tweet creation
    $oauth = new TwitterOAuth($apiKey, $apiSecretKey, $accessToken, $accessTokenSecret);

    $content = $oauth->post("statuses/update", ["status" => $message]);

    if ($oauth->getLastHttpCode() == 200) {
        // Tweet was posted successfully
        echo "Tweet posted successfully: " . $message;
    } else {
        // Handle error case
        echo "Error posting tweet: " . $content->errors[0]->message;
    }
}

// Example usage
$apiKey = 'your_api_key';
$apiSecretKey = 'your_api_secret_key';
$accessToken = 'your_access_token';
$accessTokenSecret = 'your_access_token_secret';

try {
    postTweet("Hello, Twitter!", $apiKey, $apiSecretKey, $accessToken, $accessTokenSecret);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
*/

/*
for ($i=0; $i < 100; $i++) { 
    GiveWritOfPassage(46);
}

*/phpinfo();
?>