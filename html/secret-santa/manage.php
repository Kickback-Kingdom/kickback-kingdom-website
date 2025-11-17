<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$pageTitle = "Secret Santa Owner Dashboard";
$pageDesc = "Manage Secret Santa signups, exclusion groups, and assignments.";

$prefillInvite = $_GET['invite_token'] ?? '';
$prefillCtime = $_GET['event_ctime'] ?? '';
$prefillCrand = $_GET['event_crand'] ?? '';
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
                $activePageName = "Secret Santa Owner Dashboard";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-clipboard-list fa-lg"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0">Owner controls</h1>
                                <small class="text-muted">Use your event identifiers to unlock tools below.</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventCtime">Event ctime</label>
                                <input class="form-control" id="eventCtime" value="<?php echo htmlspecialchars($prefillCtime); ?>" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventCrand">Event crand</label>
                                <input class="form-control" id="eventCrand" value="<?php echo htmlspecialchars($prefillCrand); ?>" placeholder="123456789">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="eventInviteToken">Invite token</label>
                                <input class="form-control" id="eventInviteToken" value="<?php echo htmlspecialchars($prefillInvite); ?>" placeholder="8fd0b1cc1ca223ff">
                                <div class="form-text">Share with participants for joining.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-people-arrows"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Exclusion groups</h2>
                                <small class="text-muted">Keep people in the same household or team from gifting each other.</small>
                            </div>
                        </div>

                        <form id="exclusionGroupForm" class="row g-2 align-items-end">
                            <div class="col-12 col-lg-5">
                                <label class="form-label" for="exclusionName">Group name</label>
                                <input class="form-control" id="exclusionName" placeholder="QA Team" required>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label" for="exclusionCtime">Existing ctime (optional)</label>
                                <input class="form-control" id="exclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label" for="exclusionCrand">Existing crand</label>
                                <input class="form-control" id="exclusionCrand" placeholder="1234">
                            </div>
                            <div class="col-12 col-lg-2 d-grid">
                                <button class="btn btn-secondary" type="submit">Save group</button>
                            </div>
                            <div class="col-12">
                                <div id="exclusionStatus" class="small text-muted"></div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-envelope-open-text"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Assignments</h2>
                                <small class="text-muted">Generate pairs after signups close and send them out.</small>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-primary" id="generatePairsBtn" type="button">Generate pairs</button>
                            <button class="btn btn-outline-primary" id="emailAssignmentsBtn" type="button">Email assignments</button>
                            <div id="assignmentStatus" class="small text-muted align-self-center"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="assignmentTable">
                                <thead>
                                    <tr>
                                        <th>Giver</th>
                                        <th>Receiver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-light">
                                        <td colspan="2" class="text-center text-muted">Assignments will appear here after generation.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                <i class="fa-solid fa-user-pen"></i>
                            </div>
                            <div>
                                <h2 class="h5 mb-0">Participation adjustments</h2>
                                <small class="text-muted">Re-submit a participant using the invite token if they need help.</small>
                            </div>
                        </div>
                        <form id="ownerJoinForm" class="row g-2">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerDisplayName">Display name</label>
                                <input class="form-control" id="ownerDisplayName" placeholder="Robin the Red" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerEmail">Email</label>
                                <input class="form-control" type="email" id="ownerEmail" placeholder="robin@example.com" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerExclusionCtime">Exclusion group ctime (optional)</label>
                                <input class="form-control" id="ownerExclusionCtime" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="ownerExclusionCrand">Exclusion group crand (optional)</label>
                                <input class="form-control" id="ownerExclusionCrand" placeholder="1234">
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-outline-secondary" type="submit">Submit join</button>
                                    <div id="ownerJoinStatus" class="small text-muted"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php require("../php-components/base-page-discord.php"); ?>
        </div>
        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
    <script>
        const exclusionForm = document.getElementById('exclusionGroupForm');
        const exclusionStatus = document.getElementById('exclusionStatus');
        const generatePairsBtn = document.getElementById('generatePairsBtn');
        const emailAssignmentsBtn = document.getElementById('emailAssignmentsBtn');
        const assignmentTable = document.getElementById('assignmentTable').querySelector('tbody');
        const assignmentStatus = document.getElementById('assignmentStatus');
        const ownerJoinForm = document.getElementById('ownerJoinForm');
        const ownerJoinStatus = document.getElementById('ownerJoinStatus');

        function getEventFields() {
            return {
                ctime: document.getElementById('eventCtime').value.trim(),
                crand: document.getElementById('eventCrand').value.trim(),
                invite: document.getElementById('eventInviteToken').value.trim()
            };
        }

        async function postForm(url, params) {
            const formData = new FormData();
            Object.entries(params).forEach(([k, v]) => {
                if (v !== null && v !== undefined) {
                    formData.append(k, v);
                }
            });
            const resp = await fetch(url, { method: 'POST', credentials: 'include', body: formData });
            return resp.json();
        }

        function renderAssignments(pairs) {
            assignmentTable.innerHTML = '';
            if (!pairs || !pairs.length) {
                assignmentTable.innerHTML = '<tr class="table-light"><td colspan="2" class="text-center text-muted">No assignments returned yet.</td></tr>';
                return;
            }
            pairs.forEach((pair) => {
                const row = document.createElement('tr');
                const giver = `${pair.giver.display_name} (${pair.giver.email})`;
                const receiver = `${pair.receiver.display_name} (${pair.receiver.email})`;
                row.innerHTML = `<td>${giver}</td><td>${receiver}</td>`;
                assignmentTable.appendChild(row);
            });
        }

        exclusionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            exclusionStatus.textContent = 'Saving group...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand,
                    name: document.getElementById('exclusionName').value,
                    exclusion_group_ctime: document.getElementById('exclusionCtime').value,
                    exclusion_group_crand: document.getElementById('exclusionCrand').value
                });
                exclusionStatus.textContent = resp.message || '';
                if (resp.success && resp.data) {
                    document.getElementById('exclusionCtime').value = resp.data.ctime ?? '';
                    document.getElementById('exclusionCrand').value = resp.data.crand ?? '';
                }
            } catch (err) {
                console.error(err);
                exclusionStatus.textContent = 'Unable to save exclusion group.';
            }
        });

        generatePairsBtn.addEventListener('click', async () => {
            assignmentStatus.textContent = 'Generating pairs...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/generate-pairs.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand
                });
                assignmentStatus.textContent = resp.message || '';
                if (resp.success) {
                    renderAssignments(resp.data || []);
                }
            } catch (err) {
                console.error(err);
                assignmentStatus.textContent = 'Failed to generate pairs.';
            }
        });

        emailAssignmentsBtn.addEventListener('click', async () => {
            assignmentStatus.textContent = 'Sending assignment emails...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/email-assignments.php', {
                    event_ctime: evt.ctime,
                    event_crand: evt.crand
                });
                assignmentStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                assignmentStatus.textContent = 'Failed to send assignment emails.';
            }
        });

        ownerJoinForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            ownerJoinStatus.textContent = 'Submitting...';
            const evt = getEventFields();
            try {
                const resp = await postForm('/api/v1/secret-santa/join-event.php', {
                    invite_token: evt.invite,
                    display_name: document.getElementById('ownerDisplayName').value,
                    email: document.getElementById('ownerEmail').value,
                    exclusion_group_ctime: document.getElementById('ownerExclusionCtime').value,
                    exclusion_group_crand: document.getElementById('ownerExclusionCrand').value
                });
                ownerJoinStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                ownerJoinStatus.textContent = 'Join request failed.';
            }
        });
    </script>
</body>

</html>
