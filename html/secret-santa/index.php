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

                <section class="position-relative overflow-hidden rounded-4 mb-5" style="background: radial-gradient(circle at 15% 20%, rgba(255, 99, 132, 0.2), transparent 35%), radial-gradient(circle at 85% 10%, rgba(13, 110, 253, 0.2), transparent 30%), linear-gradient(135deg, #0d6efd 0%, #842029 100%);">
                    <div class="row g-4 align-items-center text-light p-4 p-md-5">
                        <div class="col-lg-6">
                            <div class="badge bg-light text-primary-emphasis rounded-pill mb-3">Holiday-ready in minutes</div>
                            <h1 class="display-5 fw-bold mb-3">Launch a Secret Santa your crew will love</h1>
                            <p class="lead mb-4">Bring players, coworkers, or family together with a hosted exchange that keeps signups simple, pairings fair, and communication clear.</p>
                            <div class="d-flex flex-wrap gap-2 mb-4">
                                <a class="btn btn-light" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">
                                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Create an event
                                </a>
                                <a class="btn btn-outline-light" href="#how-it-works">
                                    <i class="fa-solid fa-circle-info me-1"></i> See how it works
                                </a>
                            </div>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <span class="rounded-circle bg-white bg-opacity-25 d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-sparkles"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold">Hosted in the Kingdom</div>
                                        <small class="text-white-50">No spreadsheets, no guessing.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="rounded-circle bg-white bg-opacity-25 d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-bell"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold">Automated reminders</div>
                                        <small class="text-white-50">Keep hosts and gifters on time.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-12 col-md-7 order-md-2">
                                    <div class="card bg-white bg-opacity-75 border-0 shadow-lg h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center mb-3">
                                                <span class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                                    <i class="fa-solid fa-gifts"></i>
                                                </span>
                                                <div>
                                                    <h2 class="h5 mb-0">Join with your code</h2>
                                                    <small class="text-muted">Participants enter the invite token from the host.</small>
                                                </div>
                                            </div>
                                            <form class="row g-2" action="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/invite.php" method="get">
                                                <div class="col-12">
                                                    <label for="heroInviteToken" class="form-label small mb-1">Invite token</label>
                                                    <input class="form-control" id="heroInviteToken" name="invite_token" placeholder="e.g. 4fa31b9c8d7e8c52" required>
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-danger w-100" type="submit">Join event</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-5 order-md-1">
                                    <div class="bg-white bg-opacity-25 border border-white border-opacity-25 rounded-4 p-4 h-100 d-flex align-items-center justify-content-center">
                                        <div class="text-center">
                                            <div class="rounded-4 bg-body text-dark fw-semibold px-3 py-4 shadow-sm">
                                                <i class="fa-solid fa-image mb-2 d-block"></i>
                                                <span>Room for your event art</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 order-md-3">
                                    <div class="bg-white bg-opacity-10 border border-white border-opacity-10 rounded-4 p-3">
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-success text-light me-2">New</span>
                                                <span>Set budgets, gift themes, and exclusion rules.</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-warning text-dark me-2">Shareable</span>
                                                <span>Send invite tokens or links instantly.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="how-it-works" class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-route"></i>
                        </div>
                        <h2 class="h4 mb-0">Pick the right path</h2>
                    </div>
                    <p class="text-secondary mb-4">Everything you need to launch, manage, or participate in a gift exchange. Choose the card that matches your role.</p>

                    <div class="row g-4">
                        <div class="col-12 col-lg-6">
                            <div class="card shadow-sm h-100 border-0">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-danger-subtle text-danger-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-hat-wizard"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Create an Event</h3>
                                            <small class="text-muted">Hosts set the rules, dates, and budget.</small>
                                        </div>
                                    </div>
                                    <p class="flex-grow-1">Spin up a new exchange with signup and gift deadlines, optional gift themes, and budget guidance. Generate a shareable invite link instantly.</p>
                                    <a class="btn btn-primary" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">Launch builder</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card shadow-sm h-100 border-0">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-clipboard-list"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Owner Dashboard</h3>
                                            <small class="text-muted">Keep signups fair and on track.</small>
                                        </div>
                                    </div>
                                    <p class="flex-grow-1">Manage exclusion groups, confirm participants, and lock in assignments once signups close. Email the pairings right from the dashboard.</p>
                                    <a class="btn btn-outline-primary" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/manage.php">Open dashboard</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card shadow-sm h-100 border-0">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-gifts"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Join an Event</h3>
                                            <small class="text-muted">Participants sign up with a token.</small>
                                        </div>
                                    </div>
                                    <p class="flex-grow-1">Enter the invite token from your host, add your name and email, and optionally flag anyone you should not be paired with.</p>
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
                            <div class="card shadow-sm h-100 border-0">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 44px; height: 44px;">
                                            <i class="fa-solid fa-envelope-open-text"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Reveal Assignments</h3>
                                            <small class="text-muted">Preview and share pairings.</small>
                                        </div>
                                    </div>
                                    <p class="flex-grow-1">Once pairs are generated, review giver/receiver matches and send the assignment emails without leaving the Kingdom.</p>
                                    <a class="btn btn-outline-warning" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/assignments.php">Review assignments</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-6">
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-seedling"></i>
                                </div>
                                <h2 class="h4 mb-0">Smooth signups for everyone</h2>
                            </div>
                            <p class="text-secondary">Hosts get clear visibility while participants breeze through signups. We keep every step labeled and transparent so nobody is left guessing.</p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-start mb-2"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Deadline reminders for hosts and participants.</span></li>
                                <li class="d-flex align-items-start mb-2"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Exclusion groups to avoid awkward pairings.</span></li>
                                <li class="d-flex align-items-start mb-2"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Invite tokens that are easy to share and validate.</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-6">
                            <div class="card shadow-sm border-0 h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-timeline"></i>
                                        </div>
                                        <div>
                                            <h3 class="h5 mb-0">Host checklist</h3>
                                            <small class="text-muted">Three quick steps to launch.</small>
                                        </div>
                                    </div>
                                    <ol class="mb-0 ps-3">
                                        <li class="mb-2"><strong>Create</strong> an event with your date, budget, and theme.</li>
                                        <li class="mb-2"><strong>Share</strong> the invite token with your crew (or send directly from the dashboard).</li>
                                        <li class="mb-0"><strong>Reveal</strong> pairings when signups close and email assignments instantly.</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-5 order-lg-2">
                            <div class="text-uppercase text-primary fw-semibold small mb-2">Show and tell</div>
                            <h2 class="h4 mb-3">Market your exchange with visuals</h2>
                            <p class="text-secondary">Add your own artwork, mascot, or company flair to make the invite feel personal. These image blocks give your crew a taste of the vibe before they ever click “Join.”</p>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex align-items-start mb-2"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Drop in banners or product shots to set the theme.</span></li>
                                <li class="d-flex align-items-start mb-2"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Spotlight gift ideas or past highlights.</span></li>
                                <li class="d-flex align-items-start"><i class="fa-solid fa-check text-success me-2 mt-1"></i><span>Keep everything on-brand with your logo front and center.</span></li>
                            </ul>
                        </div>
                        <div class="col-lg-7 order-lg-1">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <div class="bg-body-secondary border rounded-4 p-4 h-100 d-flex align-items-center justify-content-center">
                                        <div class="text-center text-secondary">
                                            <i class="fa-solid fa-image fa-2x mb-3"></i>
                                            <div class="fw-semibold">Feature graphic</div>
                                            <small>Upload a banner, illustration, or hero shot.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="bg-body-secondary border rounded-4 p-4 h-100 d-flex align-items-center justify-content-center">
                                        <div class="text-center text-secondary">
                                            <i class="fa-solid fa-photo-film fa-2x mb-3"></i>
                                            <div class="fw-semibold">Gallery slots</div>
                                            <small>Add snapshots from last year’s exchange.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-4 p-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-light d-inline-flex align-items-center justify-content-center me-3" style="width: 44px; height: 44px;">
                                                <i class="fa-solid fa-wand-magic"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">Need creative help?</div>
                                                <small class="text-secondary">Our team can add festive art to your invite on request.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-circle-question"></i>
                        </div>
                        <h2 class="h4 mb-0">Quick answers</h2>
                    </div>
                    <div class="accordion" id="secretSantaFAQ">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqOneCollapse" aria-expanded="true" aria-controls="faqOneCollapse">
                                    How do participants get their assignments?
                                </button>
                            </h2>
                            <div id="faqOneCollapse" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Once the host locks signups and generates pairs, they can preview the giver/receiver list and send assignment emails directly from the dashboard.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwoCollapse" aria-expanded="false" aria-controls="faqTwoCollapse">
                                    Can I avoid matching certain people together?
                                </button>
                            </h2>
                            <div id="faqTwoCollapse" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Yes. Hosts can set exclusion groups before generating assignments so people on the same team, household, or relationship never get paired.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThreeCollapse" aria-expanded="false" aria-controls="faqThreeCollapse">
                                    What if someone signs up late?
                                </button>
                            </h2>
                            <div id="faqThreeCollapse" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#secretSantaFAQ">
                                <div class="accordion-body">
                                    Hosts can reopen signups to add participants, then regenerate assignments when everyone is confirmed. Nobody gets left off the list.
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bg-primary text-light rounded-4 p-4 p-md-5 mb-5">
                    <div class="row align-items-center g-3">
                        <div class="col-md-8">
                            <h2 class="h3 mb-2">Ready to wrap this up?</h2>
                            <p class="mb-0">Launch your Secret Santa exchange in minutes, invite your crew, and let Kickback Kingdom handle the logistics.</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a class="btn btn-light" href="<?php echo Version::urlBetaPrefix(); ?>/secret-santa/create-event.php">Start a new exchange</a>
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
