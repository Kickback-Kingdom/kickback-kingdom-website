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
    ?>

    <!--TOP BANNER-->
    <div class="d-none d-md-block w-100 ratio" style="--bs-aspect-ratio: 26%; margin-top: 56px">

        <img src="/assets/media/events/1768.png" class="" />

    </div>
    <div class="d-block d-md-none w-100 ratio" style="margin-top: 56px; --bs-aspect-ratio: 46.3%;">

        <img src="/assets/media/events/1769.png" />

    </div>

    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php
                $activePageName = "Secret Santa";
                require("../php-components/base-page-breadcrumbs.php");
                ?>

                <!-- HERO -->
                <section
                    class="position-relative overflow-hidden rounded-4 mb-4 shadow-lg border-0"
                    style="
                        background:
                            radial-gradient(circle at 10% 0%, rgba(255, 255, 255, 0.18), transparent 45%),
                            radial-gradient(circle at 90% 0%, rgba(255, 193, 7, 0.25), transparent 45%),
                            linear-gradient(135deg, #0d6efd 0%, #842029 55%, #0b1729 100%);
                    ">
                    <div class="row g-0 align-items-center text-light p-4 p-md-5">
                        <div class="col-md-7">
                            <div class="d-inline-flex align-items-center mb-2 small text-light text-opacity-75">
                                <span class="badge rounded-pill bg-light text-dark me-2">
                                    <i class="fa-solid fa-snowflake me-1"></i> Holiday event toolkit
                                </span>
                                <span class="text-light text-opacity-75">
                                    For families, guilds, and teams
                                </span>
                            </div>
                            <h1 class="display-5 fw-bold mb-3">
                                Kickback Kingdom Secret Santa
                            </h1>
                            <p class="lead mb-4 text-light text-opacity-90">
                                Plan the exchange, collect signups, manage exclusions, and reveal matches
                                without leaving the Kingdom. Hosts and participants each get a clear path.
                            </p>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <a class="btn btn-light" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Create an event
                                </a>
                                <a class="btn btn-outline-light" href="#how-it-works">
                                    <i class="fa-solid fa-circle-info me-1"></i> See how it works
                                </a>
                            </div>
                            <p class="small mb-0 text-light text-opacity-75">
                                Already have an event? Use your invite token to jump straight in.
                            </p>
                        </div>

                        <div class="col-md-5 text-center text-md-end mt-4 mt-md-0">
                            <div class="card bg-white bg-opacity-90 border-0 shadow-lg d-inline-block text-start w-100"
                                 style="max-width: 360px;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <span
                                            class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                            style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-gifts"></i>
                                        </span>
                                        <div>
                                            <h2 class="h5 mb-0">Join with your code</h2>
                                            <small class="text-muted">
                                                Enter the invite token shared by your host.
                                            </small>
                                        </div>
                                    </div>
                                    <form class="row g-2" action="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/invite.php" method="get">
                                        <div class="col-12">
                                            <label for="heroInviteToken" class="form-label small mb-1">Invite token</label>
                                            <input
                                                class="form-control"
                                                id="heroInviteToken"
                                                name="invite_token"
                                                placeholder="e.g. 4fa31b9c8d7e8c52"
                                                required>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-danger w-100" type="submit">
                                                Join event
                                            </button>
                                        </div>
                                    </form>
                                    <p class="small text-muted mt-2 mb-0">
                                        Tokens stay private to your group and are validated before you join.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ROLE PATHS -->
                 <section id="how-it-works" class="mb-5">
                    <!-- Header -->
                    <div class="text-center mb-3">
                        <div
                            class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center mb-2"
                            style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-route fa-lg"></i>
                        </div>

                        <h2 class="h4 mt-2 mb-1 fw-semibold">
                            How Secret Santa works in Kickback Kingdom
                        </h2>

                        <p class="text-secondary mb-0">
                            A simple four-step flow that keeps your exchange fair, private, and easy to manage.
                        </p>
                    </div>


                    <!-- Steps + Hero Imagery -->
                    <div class="row g-4 align-items-stretch mb-4">
                        <!-- Steps timeline -->
                        <div class="col-12 col-lg-7">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body p-4">
                                    <h3 class="h5 mb-3">Step-by-step flow</h3>
                                    <div class="d-flex flex-column gap-3">

                                        <!-- Step 1 -->
                                        <div class="d-flex">
                                            <div
                                                class="me-3 flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-light fw-semibold"
                                                style="width: 32px; height: 32px;">
                                                1
                                            </div>
                                            <div>
                                                <h4 class="h6 mb-1">Host creates the event</h4>
                                                <p class="mb-1 text-secondary small">
                                                    Set the name, date, budget, and optional gift theme. Kickback Kingdom generates
                                                    an invite token and shareable link for you.
                                                </p>
                                                <a class="small" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">
                                                    Open the event creator
                                                    <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Step 2 -->
                                        <div class="d-flex">
                                            <div
                                                class="me-3 flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-light fw-semibold"
                                                style="width: 32px; height: 32px;">
                                                2
                                            </div>
                                            <div>
                                                <h4 class="h6 mb-1">Everyone joins with the invite token</h4>
                                                <p class="mb-1 text-secondary small">
                                                    Participants enter the invite token, fill in their name and email, and can flag
                                                    anyone they should not be paired with (for example, partners or housemates).
                                                </p>
                                                <form class="row g-2 mt-2" action="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/invite.php" method="get">
                                                    <div class="col-sm-8">
                                                        <label for="timelineInviteToken" class="form-label small mb-1">Invite token</label>
                                                        <input
                                                            class="form-control form-control-sm"
                                                            id="timelineInviteToken"
                                                            name="invite_token"
                                                            placeholder="e.g. 4fa31b9c8d7e8c52"
                                                            required>
                                                    </div>
                                                    <div class="col-sm-4 d-flex align-items-end">
                                                        <button class="btn btn-success btn-sm w-100" type="submit">
                                                            Join event
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Step 3 -->
                                        <div class="d-flex">
                                            <div
                                                class="me-3 flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-light fw-semibold"
                                                style="width: 32px; height: 32px;">
                                                3
                                            </div>
                                            <div>
                                                <h4 class="h6 mb-1">Host locks signups and draws names</h4>
                                                <p class="mb-1 text-secondary small">
                                                    When the signup deadline hits, the host reviews the list, adjusts exclusion groups
                                                    if needed, then locks the event. Kickback Kingdom generates fair pairings for everyone.
                                                </p>
                                                <a class="small" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/manage.php">
                                                    View the owner dashboard
                                                    <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Step 4 -->
                                        <div class="d-flex">
                                            <div
                                                class="me-3 flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-light fw-semibold"
                                                style="width: 32px; height: 32px;">
                                                4
                                            </div>
                                            <div>
                                                <h4 class="h6 mb-1">Assignments are revealed and emailed</h4>
                                                <p class="mb-1 text-secondary small">
                                                    The host previews the giver/receiver list, then sends assignment emails to all
                                                    participants directly from the Kingdom. Everyone gets their person, and the gift
                                                    exchange begins.
                                                </p>
                                            <a class="small" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/manage.php">
                                                Review assignments in the owner dashboard
                                                <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                            </a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Imagery column -->
                        <div class="col-12 col-lg-5">
                            <div class="d-flex flex-column gap-3 h-100">
                                <!-- Host illustration card -->
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span
                                                class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                                style="width: 40px; height: 40px;">
                                                <i class="fa-solid fa-hat-wizard"></i>
                                            </span>
                                            <div>
                                                <div class="small text-uppercase text-muted mb-0">Host view</div>
                                                <h3 class="h6 mb-0">Event overview and signups</h3>
                                            </div>
                                        </div>
                                        <div class="ratio ratio-16x9 mb-2">
                                            <!-- Replace src with your actual host dashboard image/screenshot -->
                                            <img
                                                src="/media/secret-santa/host-dashboard-placeholder.png"
                                                class="img-fluid rounded-3 border"
                                                alt="Host dashboard overview for Kickback Kingdom Secret Santa">
                                        </div>
                                        <p class="small text-secondary mb-0">
                                            Hosts can see who has joined, pending invites, and whether the event is ready
                                            to lock and draw names.
                                        </p>
                                    </div>
                                </div>

                                <!-- Participant illustration card -->
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <span
                                                class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                                style="width: 40px; height: 40px;">
                                                <i class="fa-solid fa-gift"></i>
                                            </span>
                                            <div>
                                                <div class="small text-uppercase text-muted mb-0">Participant view</div>
                                                <h3 class="h6 mb-0">Join and see your assignment</h3>
                                            </div>
                                        </div>
                                        <div class="ratio ratio-16x9 mb-2">
                                            <!-- Replace src with your actual participant screen image/screenshot -->
                                            <img
                                                src="/media/secret-santa/participant-signup-placeholder.png"
                                                class="img-fluid rounded-3 border"
                                                alt="Participant signup and assignment screen for Kickback Kingdom Secret Santa">
                                        </div>
                                        <p class="small text-secondary mb-0">
                                            Participants have a simple signup flow and receive a clear assignment email
                                            when pairings are finalized.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rules -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <div
                                    class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                    style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-scroll"></i>
                                </div>
                                <h3 class="h5 mb-0">Rules at a glance</h3>
                            </div>
                            <p class="text-secondary mb-3">
                                These defaults keep your exchange fair and fun. You can adjust details in the event settings.
                            </p>

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <h4 class="h6 mb-1">Pairing rules</h4>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>No one is paired with themselves.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Exclusion groups prevent specific pairings (for example, couples or same household).</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Assignments are rebalanced if you regenerate pairs.</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="col-12 col-md-4">
                                    <h4 class="h6 mb-1">Timeline rules</h4>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Hosts set signup and gift exchange deadlines.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Signups can be reopened to add late participants.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Assignments are only emailed after the host confirms the final list.</span>
                                        </li>
                                    </ul>
                                </div>

                                <div class="col-12 col-md-4">
                                    <h4 class="h6 mb-1">Budget and etiquette</h4>
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Every event includes a suggested budget range.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-1">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Participants are encouraged to stay within the agreed budget.</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Hosts can add custom notes or house rules to the event description.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- SMOOTH SIGNUPS / CHECKLIST -->
                <section class="mb-5">
                    <div class="row g-4 align-items-stretch">
                        <!-- Smooth signups card -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-2">
                                        <div
                                            class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                            style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-seedling"></i>
                                        </div>
                                        <h2 class="h5 mb-0">Smooth signups for everyone</h2>
                                    </div>
                                    <p class="text-secondary mb-3">
                                        Hosts get clear visibility while participants breeze through signups.
                                        Every step is labeled so nobody is left guessing.
                                    </p>
                                    <ul class="list-unstyled mb-0 small text-secondary">
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Deadline reminders for hosts and participants.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Exclusion groups to avoid awkward pairings.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-2">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>Invite tokens that are easy to share and validate.</span>
                                        </li>
                                        <li class="d-flex align-items-start mb-0">
                                            <i class="fa-solid fa-check text-success me-2 mt-1"></i>
                                            <span>All inside Kickback Kingdom, no external tools required.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Host checklist card -->
                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div
                                            class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2"
                                            style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-timeline"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Host checklist</h3>
                                            <small class="text-muted">Three quick steps to launch.</small>
                                        </div>
                                    </div>
                                    <ol class="mb-0 ps-3 small text-secondary">
                                        <li class="mb-2">
                                            <strong>Create</strong> an event with your date, budget, and theme.
                                        </li>
                                        <li class="mb-2">
                                            <strong>Share</strong> the invite token with your crew
                                            or send invitations directly from the dashboard.
                                        </li>
                                        <li class="mb-0">
                                            <strong>Reveal</strong> pairings when signups close and email
                                            assignments instantly.
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- FAQ -->
                <section class="mb-5">
                    <div class="text-center mb-3">
                        <div
                            class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center mb-2"
                            style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-circle-question fa-lg"></i>
                        </div>

                        <h2 class="h4 mt-2 mb-1 fw-semibold">
                            Quick answers
                        </h2>

                        <p class="text-secondary mb-0">
                            A few common questions about how Kickback Kingdom Secret Santa works.
                        </p>
                    </div>

                    <div class="accordion" id="secretSantaFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqOneCollapse" aria-expanded="true" aria-controls="faqOneCollapse">
                                    How do participants get their assignments?
                                </button>
                            </h2>
                            <div id="faqOneCollapse" class="accordion-collapse collapse show"
                                aria-labelledby="faqOne" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Once the host locks signups and generates pairs, they can preview the giver/receiver list
                                    and send assignment emails directly from the dashboard. Each participant gets a private
                                    email with their recipient details.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqTwoCollapse" aria-expanded="false" aria-controls="faqTwoCollapse">
                                    Can I avoid matching certain people together?
                                </button>
                            </h2>
                            <div id="faqTwoCollapse" class="accordion-collapse collapse"
                                aria-labelledby="faqTwo" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Yes. Hosts can set exclusion groups before generating assignments so people on the same team,
                                    household, or relationship never get paired. Those rules stay in place even if you regenerate pairings.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqThreeCollapse" aria-expanded="false" aria-controls="faqThreeCollapse">
                                    What if someone signs up late?
                                </button>
                            </h2>
                            <div id="faqThreeCollapse" class="accordion-collapse collapse"
                                aria-labelledby="faqThree" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Hosts can reopen signups to add participants, then regenerate assignments when everyone is confirmed.
                                    Nobody gets left off the list, and exclusion rules are still respected.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>


                <!-- FINAL CTA -->
                <section class="bg-primary text-light rounded-4 p-4 p-md-5 mb-5 shadow-sm">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h2 class="h3 mb-2">Ready to wrap this up?</h2>
                            <p class="mb-0">
                                Launch your Secret Santa exchange in minutes, invite your crew,
                                and let Kickback Kingdom handle the logistics while you focus on the fun.
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a class="btn btn-light" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">
                                Start a new exchange
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <?php require("../php-components/base-page-discord.php"); ?>
        </div>

        <?php require("../php-components/base-page-footer.php"); ?>
    </main>

    <?php require("../php-components/base-page-javascript.php"); ?>
</body>

</html>
