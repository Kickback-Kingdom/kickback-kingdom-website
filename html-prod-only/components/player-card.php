<?php

/*$badgesResp = GetBadgesByAccountId($GLOBALS['account']['Id']);
$badges = $badgesResp->Data;


$prestigeResp = GetAccountPrestige($GLOBALS['account']['Id']);

$prestigeReviews = $prestigeResp->Data;

$prestigeNet = GetAccountPrestigeValue($prestigeReviews);

$playerSkillsResp = GetAccountSkills($GLOBALS['account']['Id']);
$playerSkills = $playerSkillsResp->Data;*/

echo GetPlayerCard($_SESSION['account']);
?>

