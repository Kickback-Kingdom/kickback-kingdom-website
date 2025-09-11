<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");
?>
<!DOCTYPE html>
<html lang="en">
<?php require("php-components/base-page-head.php"); ?>
<body class="bg-body-secondary container p-0">
<?php
    require("php-components/base-page-components.php");
    require("php-components/ad-carousel.php");
?>
<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
    <div class="row">
        <div class="col-12 col-xl-9">
            <?php
                $activePageName = "Strategy Viewer";
                require("php-components/base-page-breadcrumbs.php");
            ?>
            <div class="card">
                <div class="card-header pb-0 text-center">
                    <h1>Strategy Viewer</h1>
                </div>
                <div class="card-body">
                    <canvas id="strategy-canvas" width="1200" height="800" style="border:1px solid #ccc; width:100%; height:60vh;"></canvas>
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <button id="add-milestone" class="btn btn-success btn-sm">Add Milestone</button>
                        <button id="add-prerequisite" class="btn btn-warning btn-sm">Add Prerequisite</button>
                        <button id="export-json" class="btn btn-primary btn-sm ms-auto">Export JSON</button>
                        <label class="btn btn-secondary btn-sm mb-0">
                            Import JSON <input type="file" id="import-json" accept="application/json" hidden>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <?php require("php-components/base-page-discord.php"); ?>
    </div>
    <?php require("php-components/base-page-footer.php"); ?>
</main>
<script src="assets/js/strategy-viewer.js"></script>
<?php require("php-components/base-page-javascript.php"); ?>
</body>
</html>
