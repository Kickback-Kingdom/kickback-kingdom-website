<?php 
use Kickback\Controllers\AnalyticController;
$pageVisitId = AnalyticController::getCurrentPage();


if (!isset($pageTitle))
{
    $pageTitle = "Kickback Kingdom";
    $pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
    $pageDesc = "Top secret super cool hangout spot";
}

if (isset($thisQuest))
{
    $pageTitle = $thisQuest->title;
    $pageImage = "https://kickback-kingdom.com".$thisQuest->icon->getFullPath();
    $pageDesc = 'Hosted on Kickback Kingdom';
    $pageVisitId = '/q/'.$thisQuest->crand;
}

if (isset($thisQuestLine))
{
    $pageTitle = $thisQuestLine->title;
    $pageImage = "https://kickback-kingdom.com".$thisQuestLine->icon->getFullPath();
    $pageDesc = 'Hosted on Kickback Kingdom';
    $pageVisitId = '/quest-line/'.$thisQuestLine->crand;
}

if (isset($thisProfile))
{
    $pageTitle = $thisProfile->username;
    $pageImage = "https://kickback-kingdom.com".$thisProfile->getProfilePictureURL();
    $pageDesc = $thisProfile->title." in Kickback Kingdom";
    $pageVisitId = '/u/'.$thisProfile->crand;
}

if (isset($thisBlogs))
{
    $pageTitle = 'Kickback Kingdom Blogs';
    $pageImage = "https://kickback-kingdom.com/assets/media/context/loading.gif";
    $pageDesc = 'Read articles written by the community';
    $pageVisitId = 'blogs';
}

if (isset($thisBlog))
{
    $pageTitle = $thisBlog->title;
    $pageImage = "https://kickback-kingdom.com".$thisBlog->icon->getFullPath();
    $pageDesc = $thisBlog->description;
    $pageVisitId = '/blog/'.$thisBlog->crand;
}

if (isset($thisBlogPost))
{
    $pageTitle = $thisBlogPost->title;
    $pageImage = "https://kickback-kingdom.com".$thisBlogPost->icon->getFullPath();
    $pageDesc = 'Written by '.$thisBlogPost->author->username;
    $pageVisitId = '/blog-post/'.$thisBlogPost->crand;
}


AnalyticController::recordPageVisit($pageVisitId);
$pageVisitResp = AnalyticController::getPageVisits($pageVisitId);
$thisPageVisits = 0;
if ($pageVisitResp->success)
{
    $thisPageVisits = $pageVisitResp->data;
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title><?php echo $pageTitle; ?></title>

    
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <meta name="keywords" content="rank, ranked, gaming, games, publisher, crypto, ada, cardano, kickback, kingdom, twilight racer, end of empires, lich, l.i.c.h., community, casual, roleplay, competitive, <?php $pageTitle; ?>">
    <meta name="author" content="Kickback Kingdom">
    <link rel="icon" href="/assets/media/icons/64.png" type="image/x-icon">
    <link rel="shortcut icon" href="/assets/media/icons/64.png" type="image/x-icon">
    <!-- iOS Web App Specific Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Kickback Kingdom">
    <link rel="apple-touch-icon" href="/assets/media/icons/64.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/media/icons/64.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/media/icons/64.png">
    <link rel="apple-touch-icon" sizes="167x167" href="/assets/media/icons/64.png">
    
    <!-- Android Web App Specific Tags -->
    <?php
        $manifestFile = $urlPrefixBeta.'/manifest.json';
        $manifestFileVersion = filemtime($_SERVER['DOCUMENT_ROOT'].$manifestFile);
    ?>

    <!--<link rel="manifest" href="<?= $manifestFile.'?v='.$manifestFileVersion ?>">-->


    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDesc; ?>">
    <meta property="og:image" content="<?php echo $pageImage; ?>">
    <meta property="og:url" content="https://kickback-kingdom.com<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@KickbackCastles"> <!-- Replace @YourTwitterHandle with your Twitter username -->
    <meta name="twitter:creator" content="@KickbackCastles"> <!-- Replace @YourTwitterHandle with your Twitter username -->
    <meta name="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta name="twitter:description" content="<?php echo $pageDesc; ?>">
    <meta name="twitter:image" content="<?php echo $pageImage; ?>">

    <!-- Bootstrap CSS -->
    <link href="<?php echo $urlPrefixBeta; ?>/assets/vendors/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Basic stylesheet -->
    <link rel="stylesheet" href="<?php echo $urlPrefixBeta; ?>/assets/vendors/owl-carousel/owl.carousel.css">

    <!-- Default Theme -->
    <link rel="stylesheet" href="<?php echo $urlPrefixBeta; ?>/assets/vendors/owl-carousel/owl.theme.css">
    <link rel="stylesheet" href="<?php echo $urlPrefixBeta; ?>/assets/vendors/animate/animate.min.css"/>
    <script src="https://kit.fontawesome.com/f098b8e570.js" crossorigin="anonymous"></script>

    <?php
        $cssFile = $urlPrefixBeta.'/assets/css/kickback-kingdom.css';
        $cssVersion = filemtime($_SERVER['DOCUMENT_ROOT'].$cssFile);

        echo '<link rel="stylesheet" type="text/css" href="'.$cssFile.'?v='.$cssVersion.'">';
    ?>

    <?php
        $cssFile = $urlPrefixBeta.'/assets/css/animate.css';
        $cssVersion = filemtime($_SERVER['DOCUMENT_ROOT'].$cssFile);

        echo '<link rel="stylesheet" type="text/css" href="'.$cssFile.'?v='.$cssVersion.'">';
    ?>

    

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.css" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.css" />
  
    
    <?php if (isset($_GET['borderless'])) { ?>
    <style>
        body {
            margin: 0 !important;
            background-image: none !important;
        }
        main {
            width: 100vw !important;
            max-width: 100vw !important;
        }
    </style>
    <?php } ?>

</head>