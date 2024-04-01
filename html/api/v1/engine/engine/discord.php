<?php

require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/../../../..")) . "/Kickback/init.php");

function DiscordWebHook(mixed $msg) : void
{
    $kk_credentials = \Kickback\Config\ServiceCredentials::instance();

    // Ex: $webhookURL = "https://discord.com/api/webhooks/<some_number>/<api_key>"
    $webhookURL = $kk_credentials["discord_api_url"] . '/' . $kk_credentials["discord_api_key"];

    $message = $msg;

    $jsonData = json_encode(array("content" => $message));
    if ($jsonData === false) {
        echo 'Error: `json_encode` failed to encode message in `DiscordWebHook` function.';
        echo "Input message: $jsonData";
        return;
    }

    // Initialize a cURL session
    $ch = curl_init($webhookURL);
    if ($ch === false) {
        echo 'Error: `curl_init` returned `false`.';
        return;
    }

    // Set the options for the cURL session
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    // Execute the cURL session
    $result = curl_exec($ch);

    // Check for errors
    if (0 < curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // Close the cURL session
    curl_close($ch);
}

?>
