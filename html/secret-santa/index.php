<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");
use Kickback\Common\Version;

$pageTitle = "Secret Santa";
$pageDesc = "Host and join Kickback Kingdom Secret Santa events.";
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php
    require("../php-components/base-page-components.php");
    require("../php-components/ad-carousel.php"); 
    ?>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Secret Santa";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="mb-4">
                    <h1 class="h3">Kickback Kingdom Secret Santa</h1>
                    <p class="text-secondary mb-1">Create a gift exchange, invite friends, manage exclusion groups, and reveal assignments once signups close.</p>
                    <p class="small text-muted">Use the cards below to launch the right workspace for your role.</p>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-hat-wizard"></i>
                                    </div>
                                    <div>
                                        <h2 class="h5 mb-0">Create an Event</h2>
                                        <small class="text-muted">Set deadlines and share an invite link.</small>
                                    </div>
                                </div>
                                <p class="flex-grow-1">Start a new exchange with signup and gift deadlines. You'll get an invite link to send to participants.</p>
                                <a class="btn btn-primary" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">Launch builder</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <h2 class="h5 mb-0">Owner Dashboard</h2>
                                        <small class="text-muted">Manage exclusion groups & assignments.</small>
                                    </div>
                                </div>
                                <p class="flex-grow-1">Update exclusion groups, generate pairings after signups close, and email out assignments to participants.</p>
                                <a class="btn btn-outline-primary" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/manage.php">Open dashboard</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-gifts"></i>
                                    </div>
                                    <div>
                                        <h2 class="h5 mb-0">Join an Event</h2>
                                        <small class="text-muted">Use your invite token to sign up.</small>
                                    </div>
                                </div>
                                <p class="flex-grow-1">Validate the invite token you received, then submit your display name, email, and optional exclusion group.</p>
                                <form class="row g-2" action="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/invite.php" method="get">
                                    <div class="col-12">
                                        <label for="quickInviteToken" class="form-label small mb-1">Invite token</label>
                                        <input class="form-control" id="quickInviteToken" name="invite_token" placeholder="e.g. 4fa31b9c8d7e8c52" required>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-success w-100" type="submit">Continue</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-envelope-open-text"></i>
                                    </div>
                                    <div>
                                        <h2 class="h5 mb-0">Reveal Assignments</h2>
                                        <small class="text-muted">Owners can preview generated pairs.</small>
                                    </div>
                                </div>
                                <p class="flex-grow-1">After pairs are generated, view the giver/receiver list or trigger emails without leaving the Kingdom.</p>
                                <a class="btn btn-outline-warning" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/assignments.php">Review assignments</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
</body>

</html>
