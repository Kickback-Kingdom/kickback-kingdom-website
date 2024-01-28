<?php

function DiscordWebHook($msg)
{
    $webhookURL = "***REMOVED***";
    $message = $msg;

    $jsonData = json_encode(array("content" => $message));

    // Initialize a cURL session
    $ch = curl_init($webhookURL);

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
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }

    // Close the cURL session
    curl_close($ch);
}

?>