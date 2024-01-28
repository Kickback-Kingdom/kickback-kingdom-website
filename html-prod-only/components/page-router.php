<?php
$currentPageUrl = strtok($_SERVER["REQUEST_URI"], "?");
$parts = explode('/', $currentPageUrl);
$url = $currentPageUrl;
if (isset($parts[1])) {
    $folderName = $parts[1];
    //echo $folderName; // Output: q
    $url = $folderName;
}
//echo "<br/>";
//echo $url;

switch ($url) {
    case 'adventurers-guild.php':
        require("pages/adventurers.php");
        break;
    
    case 'merchants-guild.php':
        require("pages/merchants.php");
        break;
            
    case 'craftsmen-guild.php':
        require("pages/craftsmen.php");
        break;
        
    case 'apprentices-guild.php':
        require("pages/apprentices.php");
        break;

    case 'profile.php':
    case 'u':
        require("pages/profile.php");
        break;
                
    case 'quest.php':
    case 'q':
        require("pages/quest.php");
        break;

    case 'town-square.php':
        require("pages/town-square.php");
        break;

    case 'coming-soon.php':
        require("pages/coming-soon.php");
        break;

    default:
    require("pages/dashboard.php");
        break;
}



?>