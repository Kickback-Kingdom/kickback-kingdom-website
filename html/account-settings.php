<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Services\Session;

if (!Session::isLoggedIn()) {
    header("Location: login.php?redirect=" . urlencode("account-settings.php"));
    exit();
}

$account = Session::getCurrentAccount();
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
                $activePageName = "Account Settings";
                require("php-components/base-page-breadcrumbs.php");
                ?>

                <!-- Username Section -->
                <form method="POST" class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Username</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="usernameInput" class="form-label">Username</label>
                            <input type="text" class="form-control" id="usernameInput" name="username" value="<?= htmlspecialchars($account->username); ?>" required>
                        </div>
                        <button type="submit" name="submit-username" class="btn btn-primary">Update Username</button>
                    </div>
                </form>

                <!-- Password Section -->
                <form method="POST" class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Password</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="passwordInput" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="passwordInput" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPasswordInput" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPasswordInput" name="password_confirm" required>
                        </div>
                        <button type="submit" name="submit-password" class="btn btn-primary">Update Password</button>
                    </div>
                </form>

                <!-- Subscription Preferences Section -->
                <form method="POST" class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Subscription Preferences</h5></div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="emailSub" name="sub-email">
                            <label class="form-check-label" for="emailSub">
                                Email Notifications
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="newsSub" name="sub-news">
                            <label class="form-check-label" for="newsSub">
                                News and Updates
                            </label>
                        </div>
                        <button type="submit" name="submit-subscription" class="btn btn-primary mt-2">Save Preferences</button>
                    </div>
                </form>

                <!-- Third-Party Linking Section -->
                <form method="POST" class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Third-Party Linking</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="discordInput" class="form-label">Discord Username</label>
                            <input type="text" class="form-control" id="discordInput" name="discord" value="<?= isset($account->discord) ? htmlspecialchars($account->discord) : '';?>">
                        </div>
                        <div class="mb-3">
                            <label for="steamInput" class="form-label">Steam ID</label>
                            <input type="text" class="form-control" id="steamInput" name="steam" value="<?= isset($account->steam) ? htmlspecialchars($account->steam) : '';?>">
                        </div>
                        <button type="submit" name="submit-thirdparty" class="btn btn-primary">Link Accounts</button>
                    </div>
                </form>
            </div>
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
</body>
</html>
