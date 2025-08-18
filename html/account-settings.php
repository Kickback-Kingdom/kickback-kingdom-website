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

    <!-- Discord Unlink Confirmation Modal -->
    <div class="modal fade" id="unlinkDiscordModal" tabindex="-1" aria-labelledby="unlinkDiscordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="unlinkDiscordModalLabel">Unlink Discord</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to unlink your Discord account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmUnlinkDiscord">Unlink</button>
                </div>
            </div>
        </div>
    </div>

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
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Third-Party Linking</h5></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Discord</label><br>
                            <?php if (!$account->isDiscordLinked()) { ?>
                                <button type="button" class="btn btn-primary" id="btnLinkDiscord">Link Discord</button>
                            <?php } else { ?>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fa-brands fa-discord"></i>
                                    <span><?= htmlspecialchars($account->discordUsername); ?></span>
                                    <span class="badge text-bg-success">Linked</span>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm ms-3 d-flex align-items-center gap-1"
                                        id="btnUnlinkDiscord"
                                    >
                                        <i class="fa-solid fa-link-slash"></i>
                                        <span>Unlink</span>
                                    </button>
                                </div>
                            <?php } ?>
                        </div>
                        <div id="discordStatus" class="alert d-none" role="alert"></div>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="steamInput" class="form-label">Steam ID</label>
                                <input type="text" class="form-control" id="steamInput" name="steam" value="<?= isset($account->steam) ? htmlspecialchars($account->steam) : '';?>">
                            </div>
                            <button type="submit" name="submit-thirdparty" class="btn btn-primary">Link Accounts</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    <?php require("php-components/base-page-javascript.php"); ?>
    <script>
        const params = new URLSearchParams(window.location.search);
        const discordError = params.get('discord_error');
        if (discordError) {
            const statusDiv = $('#discordStatus');
            statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(discordError);
            ShowPopError(discordError, 'Discord');
            params.delete('discord_error');
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            history.replaceState({}, '', newUrl);
        }

        $('#btnLinkDiscord').on('click', function () {
            const statusDiv = $('#discordStatus');
            fetch('/api/v1/discord/link-start.php', { credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.url) {
                        window.location = data.data.url;
                    } else {
                        const msg = data.message || 'Failed to link Discord';
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                        ShowPopError(msg, 'Discord');
                    }
                })
                .catch(() => {
                    const msg = 'Failed to link Discord';
                    statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(msg);
                    ShowPopError(msg, 'Discord');
                });
        });

        $('#btnUnlinkDiscord').on('click', function () {
            $('#unlinkDiscordModal').modal('show');
        });

        $('#confirmUnlinkDiscord').on('click', function () {
            fetch('/api/v1/discord/unlink.php', { method: 'POST', credentials: 'same-origin' })
                .then(response => response.json())
                .then(data => {
                    const statusDiv = $('#discordStatus');
                    if (data.success) {
                        statusDiv.removeClass('d-none alert-danger').addClass('alert-success').text(data.message);
                        ShowPopSuccess(data.message, 'Discord');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        statusDiv.removeClass('d-none alert-success').addClass('alert-danger').text(data.message);
                        ShowPopError(data.message, 'Discord');
                    }
                })
                .catch(() => {
                    ShowPopError('Failed to unlink Discord', 'Discord');
                })
                .finally(() => {
                    $('#unlinkDiscordModal').modal('hide');
                });
        });
    </script>
</body>
</html>
