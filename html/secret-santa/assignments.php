<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("../php-components/base-page-pull-active-account-info.php");

$pageTitle = "Secret Santa Assignments";
$pageDesc = "Reveal or send Secret Santa assignments.";

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
                $activePageName = "Secret Santa Assignments";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 48px; height: 48px;">
                                <i class="fa-solid fa-scroll fa-lg"></i>
                            </div>
                            <div>
                                <h1 class="h4 mb-0">Reveal assignments</h1>
                                <small class="text-muted">Only run after signups close to avoid reshuffling participants.</small>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="assignEventCtime">Event ctime</label>
                                <input class="form-control" id="assignEventCtime" value="<?php echo htmlspecialchars($prefillCtime); ?>" placeholder="20241130125959999">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="assignEventCrand">Event crand</label>
                                <input class="form-control" id="assignEventCrand" value="<?php echo htmlspecialchars($prefillCrand); ?>" placeholder="123456789">
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button class="btn btn-warning" id="revealAssignmentsBtn" type="button">Reveal pairs</button>
                            <button class="btn btn-outline-warning" id="sendAssignmentsBtn" type="button">Email everyone</button>
                            <div id="revealStatus" class="small text-muted align-self-center"></div>
                        </div>
                        <div class="alert alert-warning small" role="alert">
                            Generating pairs inserts assignments into the event. If you need to update exclusion groups or participants, do that first in the owner dashboard.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle" id="revealTable">
                                <thead>
                                    <tr>
                                        <th>Giver</th>
                                        <th>Receiver</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-light">
                                        <td colspan="2" class="text-center text-muted">Run "Reveal pairs" to see assignments.</td>
                                    </tr>
                                </tbody>
                            </table>
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
        const revealBtn = document.getElementById('revealAssignmentsBtn');
        const sendBtn = document.getElementById('sendAssignmentsBtn');
        const revealStatus = document.getElementById('revealStatus');
        const revealTable = document.getElementById('revealTable').querySelector('tbody');

        async function postForm(url, params) {
            const formData = new FormData();
            Object.entries(params).forEach(([k, v]) => formData.append(k, v));
            const resp = await fetch(url, { method: 'POST', credentials: 'include', body: formData });
            return resp.json();
        }

        function getEventParams() {
            return {
                event_ctime: document.getElementById('assignEventCtime').value.trim(),
                event_crand: document.getElementById('assignEventCrand').value.trim()
            };
        }

        function renderAssignments(assignments) {
            revealTable.innerHTML = '';
            if (!assignments || !assignments.length) {
                revealTable.innerHTML = '<tr class="table-light"><td colspan="2" class="text-center text-muted">No assignments returned.</td></tr>';
                return;
            }
            assignments.forEach((pair) => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${pair.giver.display_name} (${pair.giver.email})</td><td>${pair.receiver.display_name} (${pair.receiver.email})</td>`;
                revealTable.appendChild(row);
            });
        }

        revealBtn.addEventListener('click', async () => {
            revealStatus.textContent = 'Generating assignments...';
            try {
                const resp = await postForm('/api/v1/secret-santa/generate-pairs.php', getEventParams());
                revealStatus.textContent = resp.message || '';
                if (resp.success) {
                    renderAssignments(resp.data || []);
                }
            } catch (err) {
                console.error(err);
                revealStatus.textContent = 'Unable to reveal assignments.';
            }
        });

        sendBtn.addEventListener('click', async () => {
            revealStatus.textContent = 'Emailing assignments...';
            try {
                const resp = await postForm('/api/v1/secret-santa/email-assignments.php', getEventParams());
                revealStatus.textContent = resp.message || '';
            } catch (err) {
                console.error(err);
                revealStatus.textContent = 'Email request failed.';
            }
        });
    </script>
</body>

</html>
