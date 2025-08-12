<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Common\Version;
?>
<!DOCTYPE html>
<html lang="en">
<?php
ob_start();
require("php-components/base-page-head.php");
$baseHead = ob_get_clean();
// Attach rulebook-specific stylesheet
$cssHref = Version::urlBetaPrefix().'/assets/css/lich-rulebook.css?v='.Version::current()->number();
$baseHead = str_replace('</head>', '<link rel="stylesheet" href="'.$cssHref.'"></head>', $baseHead);
echo $baseHead;
?>
<body class="bg-body-secondary container p-0">
    <?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
    ?>
    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "L.I.C.H. Rulebook";
                require("php-components/base-page-breadcrumbs.php");
                ?>
                <div class="mb-3">
                    <input type="text" id="rulebook-search" class="form-control" placeholder="Search the rulebook...">
                </div>
                <div id="rulebook-container"></div>
            </div>
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>
    <?php require("php-components/base-page-javascript.php"); ?>
    <?php
    $jsFile = Version::urlBetaPrefix().'/assets/js/lich-rulebook.js';
    $jsVersion = Version::current()->number();
    echo '<script src="'.$jsFile.'?v='.$jsVersion.'"></script>';
    ?>
</body>
</html>
