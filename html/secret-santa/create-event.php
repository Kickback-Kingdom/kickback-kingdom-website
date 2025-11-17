<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");
use Kickback\Common\Version;

$pageTitle = "Create Secret Santa";
$pageDesc = "Set up a new Secret Santa event.";
?>
<!DOCTYPE html>
<html lang="en">

<?php require("../php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    <?php require("../php-components/base-page-components.php"); ?>
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Create Secret Santa";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-hat-wizard fa-lg"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0">Secret Santa Event Builder</h1>
                                <small class="text-muted">Set the basics and grab your invite link.</small>
                            </div>
                        </div>

                        <form id="secretSantaCreateForm" class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="eventName">Event name</label>
                                <input class="form-control" id="eventName" name="name" placeholder="Winter Guild Exchange" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="eventDescription">Description (optional)</label>
                                <textarea class="form-control" id="eventDescription" name="description" rows="3" placeholder="Share gift themes, budget, or meeting details."></textarea>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="signupDeadline">Signup deadline</label>
                                <input class="form-control" type="datetime-local" id="signupDeadline" required>
                                <div class="form-text">UTC time. Signups close before pair generation.</div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="giftDeadline">Gift deadline</label>
                                <input class="form-control" type="datetime-local" id="giftDeadline" required>
                            </div>
                            <div class="col-12 d-flex align-items-center gap-2 mt-2">
                                <button type="submit" class="btn btn-primary">Create event</button>
                                <div id="createStatus" class="small text-muted"></div>
                            </div>
                        </form>

                        <div id="createResult" class="mt-4 d-none">
                            <div class="alert alert-success mb-3" role="alert">
                                <strong>Event created!</strong> Share this link with participants.
                            </div>
                            <div class="bg-body-secondary rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <div class="small text-muted">Invite link</div>
                                        <div class="fw-semibold" id="inviteLink"></div>
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm mt-2 mt-md-0" id="copyInvite">Copy</button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <div class="small text-muted">Event ID (ctime / crand)</div>
                                    <div class="fw-semibold" id="eventId"></div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="small text-muted">Invite token</div>
                                    <div class="fw-semibold" id="inviteToken"></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a class="btn btn-outline-primary" id="manageLink" href="#">Open owner dashboard</a>
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
    <script>
        const createForm = document.getElementById('secretSantaCreateForm');
        const createStatus = document.getElementById('createStatus');
        const createResult = document.getElementById('createResult');
        const inviteLinkEl = document.getElementById('inviteLink');
        const inviteTokenEl = document.getElementById('inviteToken');
        const eventIdEl = document.getElementById('eventId');
        const manageLink = document.getElementById('manageLink');
        const copyInvite = document.getElementById('copyInvite');

        function toApiDate(value) {
            if (!value) return '';
            const date = new Date(value);
            const pad = (n) => n.toString().padStart(2, '0');
            return `${date.getUTCFullYear()}-${pad(date.getUTCMonth() + 1)}-${pad(date.getUTCDate())} ${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())}`;
        }

        async function postFormData(url, data) {
            const resp = await fetch(url, {
                method: 'POST',
                body: data,
                credentials: 'include'
            });
            return resp.json();
        }

        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            createStatus.textContent = 'Creating event...';
            createResult.classList.add('d-none');

            const formData = new FormData();
            formData.append('name', document.getElementById('eventName').value);
            formData.append('description', document.getElementById('eventDescription').value);
            formData.append('signup_deadline', toApiDate(document.getElementById('signupDeadline').value));
            formData.append('gift_deadline', toApiDate(document.getElementById('giftDeadline').value));

            try {
                const resp = await postFormData('/api/v1/secret-santa/create-event.php', formData);
                createStatus.textContent = resp.message || '';

                if (resp.success && resp.data) {
                    const inviteToken = resp.data.invite_token;
                    const inviteUrl = `${window.location.origin}${'<?php echo Version::urlBetaPrefix(); ?>'}/secret-santa/invite.php?invite_token=${inviteToken}`;
                    inviteLinkEl.textContent = inviteUrl;
                    inviteLinkEl.dataset.url = inviteUrl;
                    inviteTokenEl.textContent = inviteToken;
                    eventIdEl.textContent = `${resp.data.ctime} / ${resp.data.crand}`;
                    manageLink.href = `${'<?php echo Version::urlBetaPrefix(); ?>'}/secret-santa/manage.php?event_ctime=${resp.data.ctime}&event_crand=${resp.data.crand}&invite_token=${inviteToken}`;
                    createResult.classList.remove('d-none');
                }
            } catch (err) {
                console.error(err);
                createStatus.textContent = 'Something went wrong creating the event.';
            }
        });

        copyInvite.addEventListener('click', async (e) => {
            e.preventDefault();
            const url = inviteLinkEl.dataset.url || inviteLinkEl.textContent;
            if (!url) return;
            try {
                await navigator.clipboard.writeText(url);
                copyInvite.textContent = 'Copied!';
                setTimeout(() => copyInvite.textContent = 'Copy', 2000);
            } catch (err) {
                copyInvite.textContent = 'Copy failed';
            }
        });
    </script>
</body>

</html>
