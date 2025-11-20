<?php
	require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");
	
	use Kickback\Services\Session;
	use Kickback\Backend\Controllers\SecretSantaController;
	
	$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
	require("../php-components/base-page-pull-active-account-info.php");
	
	$inviteToken = $_GET['invite_token'] ?? '';
	$kickbackAccount = Session::getCurrentAccount();
	$defaultDisplayName = $kickbackAccount ? trim(($kickbackAccount->firstName ?? '') . ' ' . ($kickbackAccount->lastName ?? '')) : '';
	$defaultEmail = $kickbackAccount->email ?? '';
	$redirectTarget = 'secret-santa/invite.php' . ($inviteToken ? '?invite_token=' . urlencode($inviteToken) : '');
	$loginRedirectUrl = \Kickback\Common\Version::urlBetaPrefix() . '/login.php?redirect=' . rawurlencode($redirectTarget);
	$registerRedirectUrl = \Kickback\Common\Version::urlBetaPrefix() . '/register.php?redirect=' . rawurlencode($redirectTarget);
	$managePageBaseUrl = \Kickback\Common\Version::urlBetaPrefix() . '/secret-santa/manage.php';
	$prefetchedEvent = null;
	$prefetchedParticipants = [];
	$invitePrefetchMessage = '';
	
	if ($inviteToken) {
	    try {
	        $inviteValidationResponse = SecretSantaController::validateInvite($inviteToken);
	        $invitePrefetchMessage = $inviteValidationResponse->message ?? '';
	
	        if ($inviteValidationResponse->success && is_array($inviteValidationResponse->data ?? null)) {
	            $prefetchedEvent = $inviteValidationResponse->data;
	            if (isset($prefetchedEvent['participants']) && is_array($prefetchedEvent['participants'])) {
	                $prefetchedParticipants = $prefetchedEvent['participants'];
	            }
	        }
	    } catch (\Throwable $th) {
	        $invitePrefetchMessage = 'Unable to validate invite token right now.';
	        $prefetchedEvent = null;
	        $prefetchedParticipants = [];
	    }
	}
	
	$userIsParticipant = false;
	$userIsHost = false;
	$prefetchedEventName = $prefetchedEvent['name'] ?? 'this exchange';
	
	if ($prefetchedEvent && $kickbackAccount) {
	    $accountCrand = $kickbackAccount->crand ?? null;
	    $accountEmail = $kickbackAccount->email ?? '';
	
	    if (!is_null($accountCrand) && isset($prefetchedEvent['owner_id']) && (string)$prefetchedEvent['owner_id'] === (string)$accountCrand) {
	        $userIsHost = true;
	    }
	
	    foreach ($prefetchedParticipants as $participant) {
	        $participantAccountId = $participant['account_id'] ?? null;
	        if (!is_null($accountCrand) && !is_null($participantAccountId) && (string)$participantAccountId === (string)$accountCrand) {
	            $userIsParticipant = true;
	            break;
	        }
	
	        $participantEmail = $participant['email'] ?? '';
	        if ($participantEmail && $accountEmail && strcasecmp($participantEmail, $accountEmail) === 0) {
	            $userIsParticipant = true;
	            break;
	        }
	    }
	}
	
	if ($userIsHost) {
	    $userIsParticipant = true;
	}
	
	$joinRowsState = $userIsHost ? 'host' : ($userIsParticipant ? 'participant' : 'default');
	$joinRowsInitialStyle = $prefetchedEvent ? '' : 'display:none;';
	$hostManageUrl = $managePageBaseUrl;
	
	$serverAssignmentsGenerated = (bool)($prefetchedEvent['assignments_generated'] ?? false);
	$serverCurrentAssignment = is_array($prefetchedEvent['current_assignment'] ?? null) ? $prefetchedEvent['current_assignment'] : null;
	$serverAssignmentReceiver = is_array($serverCurrentAssignment['receiver'] ?? null) ? $serverCurrentAssignment['receiver'] : null;
	
        if ($prefetchedEvent && !empty($prefetchedEvent['invite_token'])) {
            $hostManageUrl = $managePageBaseUrl . '?invite_token=' . urlencode($prefetchedEvent['invite_token']);
        }
	$pageTitle = "Join Secret Santa";
	$pageDesc = "Join a Kickback Kingdom Secret Santa event.";
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
		<style>
			.invite-hero {
			position: relative;
			background: radial-gradient(circle at 10% 20%, rgba(111, 66, 193, 0.3) 0, transparent 30%),
			radial-gradient(circle at 80% 0, rgba(13, 110, 253, 0.35) 0, transparent 32%),
			linear-gradient(135deg, #0a1a2f 0%, #1f2f55 50%, #29132a 100%);
			color: #f8f9fa;
			}
			.invite-hero::after {
			content: "";
			position: absolute;
			inset: 0;
			background: linear-gradient(135deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.02));
			pointer-events: none;
			}
			.invite-hero .hero-grid {
			position: relative;
			z-index: 1;
			display: grid;
			grid-template-columns: 1fr;
			grid-template-areas:
			"content"
			"card";
			gap: 1.5rem;
			align-items: stretch;
			}
			.invite-hero .hero-content {
			grid-area: content;
			}
			.invite-hero .event-timing {
			grid-area: card;
			}
			.invite-hero .event-timing .hero-card {
			width: 100%;
			}
			.invite-hero .eyebrow {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.35rem 0.75rem;
			border-radius: 999px;
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.16);
			text-transform: uppercase;
			letter-spacing: 0.05em;
			font-weight: 700;
			font-size: 0.75rem;
			}
			.invite-hero .hero-title {
			font-size: clamp(2.25rem, 3vw + 1.5rem, 3rem);
			line-height: 1.1;
			}
			.token-chip {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			padding: 0.4rem 0.75rem;
			border-radius: 0.75rem;
			background: rgba(255, 255, 255, 0.12);
			border: 1px solid rgba(255, 255, 255, 0.2);
			font-weight: 600;
			}
			.token-code {
			background: rgba(255, 255, 255, 0.08);
			border: 1px solid rgba(255, 255, 255, 0.18);
			border-radius: 0.65rem;
			padding: 0.65rem 0.9rem;
			color: #fff;
			font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
			}
			.hero-card {
			background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(241, 243, 246, 0.96));
			border: 1px solid rgba(255, 255, 255, 0.6);
			box-shadow: 0 1rem 2.5rem rgba(9, 14, 30, 0.25);
			}
			.countdown-card {
			background: transparent;
			}
			.countdown-grid {
			display: grid;
			gap: 1rem;
			grid-template-columns: repeat(1, minmax(0, 1fr));
			}
			@media (min-width: 768px) {
			.countdown-grid {
			grid-template-columns: repeat(2, minmax(0, 1fr));
			}
			}
			.countdown-block {
			border: 1px solid var(--bs-border-color-translucent);
			border-radius: 1rem;
			padding: 1rem 1.25rem;
			background: #fff;
			height: 100%;
			}
			.countdown-values {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 0.75rem;
			align-items: stretch;
			}
			.count-chip {
			width: 100%;
			min-width: 0;
			padding: 0.65rem 0.8rem;
			background: #0d6efd;
			color: #fff;
			border-radius: 0.9rem;
			text-align: center;
			box-shadow: 0 0.35rem 1rem rgba(13, 110, 253, 0.18);
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0.1rem;
			}
			.count-chip small {
			display: block;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			font-weight: 600;
			opacity: 0.9;
			}
			.count-number {
			transition: transform 0.2s ease;
			line-height: 1;
			white-space: nowrap;
			font-size: clamp(1.1rem, 5vw, 2.25rem);
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			min-width: 0;
			}
			.event-details-card {
			border: 1px solid var(--bs-border-color-translucent);
			background: #ffffff;
			border-radius: 1.5rem;
			}
			.host-note-panel {
			background: linear-gradient(145deg, #fff8ff 0%, #f5f9ff 50%, #ffffff 100%);
			border: 1px dashed rgba(90, 99, 235, 0.25);
			border-radius: 1.25rem;
			padding: 1.25rem;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.5);
			}
			.host-note-icon {
			width: 52px;
			height: 52px;
			border-radius: 14px;
			background: linear-gradient(180deg, #4f46e5 0%, #7c3aed 100%);
			color: #fff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 10px 30px rgba(79, 70, 229, 0.35);
			}
			.host-note-text {
			white-space: pre-line;
			font-size: 1rem;
			line-height: 1.65;
			}
			.host-note-meta {
			border-top: 1px solid var(--bs-border-color-translucent);
			padding-top: 0.75rem;
			margin-top: 0.75rem;
			}
			.join-layout {
			display: grid;
			grid-template-columns: 1fr;
			gap: 1rem;
			}
			#joinRows[data-state="participant"] #joinFormSection,
			#joinRows[data-state="participant"] #joinLoginPrompt,
			#joinRows[data-state="host"] #joinFormSection,
			#joinRows[data-state="host"] #joinLoginPrompt {
			display: none !important;
			}
			#joinRows[data-state="host"] #joinHostPrompt {
			display: flex !important;
			}
			.join-state-banner {
			border: 1px solid var(--bs-border-color-translucent);
			border-radius: 1rem;
			background: var(--bs-success-bg-subtle);
			color: var(--bs-success-text-emphasis);
			padding: 1rem 1.25rem;
			display: flex;
			align-items: flex-start;
			gap: 0.75rem;
			}
			.join-state-banner.join-state-host {
			background: var(--bs-warning-bg-subtle);
			color: var(--bs-warning-text-emphasis);
			}
			.join-cta {
			display: flex;
			align-items: center;
			gap: 1rem;
			padding: 1.25rem;
			border-radius: 1.25rem;
			border: 1px dashed rgba(13, 110, 253, 0.25);
			background: linear-gradient(135deg, rgba(13, 110, 253, 0.08), rgba(111, 66, 193, 0.05));
			box-shadow: 0 10px 30px rgba(12, 23, 52, 0.08);
			}
			.join-cta + .join-cta {
			margin-top: 1rem;
			}
			.join-cta-icon {
			width: 52px;
			height: 52px;
			border-radius: 1rem;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 1.25rem;
			box-shadow: 0 1rem 2rem rgba(13, 110, 253, 0.15);
			}
			.join-panel {
			background: linear-gradient(145deg, #ffffff 0%, #f7f9ff 50%, #ffffff 100%);
			border: 1px solid var(--bs-border-color-translucent);
			border-radius: 1.25rem;
			padding: 1.25rem;
			box-shadow: 0 10px 30px rgba(12, 23, 52, 0.08);
			}
			.join-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			padding: 0.35rem 0.75rem;
			border-radius: 999px;
			background: rgba(13, 110, 253, 0.08);
			color: #0d6efd;
			font-weight: 600;
			}
			.join-meta {
			border-top: 1px dashed var(--bs-border-color-translucent);
			margin-top: 1rem;
			padding-top: 1rem;
			}
			.join-hint {
			display: inline-flex;
			align-items: center;
			gap: 0.5rem;
			padding: 0.55rem 0.85rem;
			border-radius: 0.85rem;
			background: rgba(40, 167, 69, 0.08);
			color: #198754;
			font-weight: 600;
			}
			@media (min-width: 992px) {
			.join-layout {
			grid-template-columns: 1fr 1.15fr;
			}
			}
			@media (max-width: 576px) {
			.join-cta {
			flex-direction: column;
			align-items: flex-start;
			}
			.join-cta .text-end {
			width: 100%;
			text-align: left !important;
			}
			}
			.count-number.pulse {
			animation: pulse 0.6s ease;
			}
			@keyframes pulse {
			0% {
			transform: scale(1);
			}
			50% {
			transform: scale(1.05);
			}
			100% {
			transform: scale(1);
			}
			}
		</style>
		<main class="container pt-3 bg-body" style="margin-bottom: 56px;">
			<div class="row">
				<div class="col-12 col-xl-9">
					<?php
						$activePageName = "Join Secret Santa";
						require("../php-components/base-page-breadcrumbs.php");
						?>
					<!-- HERO / INVITE LOOKUP -->
					<section class="invite-hero rounded-4 mb-4 shadow-lg overflow-hidden border-0">
						<div class="hero-grid p-4 p-md-5">
							<div class="d-flex flex-column gap-3 gap-lg-4 hero-content">
								<div class="eyebrow text-light text-opacity-85">
									<i class="fa-solid fa-sleigh"></i>
									Secret Santa Invitation
								</div>
								<div class="d-flex flex-column gap-2">
									<h1 class="hero-title fw-bold mb-0">Claim your spot in the gift exchange</h1>
									<p class="lead mb-0 text-light text-opacity-85">
										Load the invite, see the deadlines in real time, and get ready for reveal day. Your host’s token keeps
										the exchange private and coordinated.
									</p>
								</div>
							</div>
							<div class="event-timing">
								<div class="card hero-card rounded-4">
									<div class="card-body p-4 p-md-4 d-flex flex-column gap-3">
										<div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
											<div>
												<div class="small text-uppercase text-muted mb-1">Current event</div>
												<h2 class="h4 mb-1" id="heroEventTitle">Secret Santa exchange</h2>
												<div class="text-muted" id="heroEventSubtitle">Use your invite link to load the event details.</div>
											</div>
											<span class="badge bg-primary-subtle text-primary-emphasis align-self-start">Live countdowns</span>
										</div>
										<div class="countdown-card" id="countdownCard" style="display: none;">
											<div class="countdown-grid">
												<div class="countdown-block d-flex flex-column gap-3" id="signupCountdown" style="display:none;">
													<div class="d-flex align-items-center gap-2 text-start">
														<span class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
														<i class="fa-solid fa-user-clock"></i>
														</span>
														<div>
															<div class="small text-uppercase text-muted">Until signups close</div>
															<div class="fw-semibold text-secondary">Lock in before names are drawn.</div>
														</div>
													</div>
													<div class="countdown-values">
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countSignupDays">--</div>
															<small>Days</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countSignupHours">--</div>
															<small>Hours</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countSignupMinutes">--</div>
															<small>Minutes</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countSignupSeconds">--</div>
															<small>Seconds</small>
														</div>
													</div>
												</div>
												<div class="countdown-block d-flex flex-column gap-3" id="giftCountdown" style="display:none;">
													<div class="d-flex align-items-center gap-2 text-start">
														<span class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
														<i class="fa-solid fa-gift"></i>
														</span>
														<div>
															<div class="small text-uppercase text-muted">Until gift exchange</div>
															<div class="fw-semibold text-secondary">Count down to reveal day.</div>
														</div>
													</div>
													<div class="countdown-values">
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countGiftDays">--</div>
															<small>Days</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countGiftHours">--</div>
															<small>Hours</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countGiftMinutes">--</div>
															<small>Minutes</small>
														</div>
														<div class="count-chip">
															<div class="display-6 fw-bold count-number" id="countGiftSeconds">--</div>
															<small>Seconds</small>
														</div>
													</div>
												</div>
											</div>
											<p class="small text-muted mt-3 mb-0" id="countdownNote"></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</section>
					<!-- EVENT DETAILS -->
					<div class="card shadow-sm border-0 mb-4 event-details-card" id="eventDetailsCard" style="display:none;">
						<div class="card-body p-4 d-flex flex-column gap-3">
							<div class="d-flex align-items-center gap-3 flex-wrap">
								<div class="host-note-icon">
									<i class="fa-solid fa-scroll"></i>
								</div>
								<div class="flex-grow-1">
									<h2 class="h5 mb-1">Notes from your host</h2>
									<div class="text-muted small">These details keep everyone on the same page.</div>
								</div>
							</div>
							<div class="host-note-panel">
								<div class="d-flex flex-column flex-md-row gap-3 align-items-start">
									<div class="d-flex flex-column gap-2 flex-grow-1">
										<p id="eventDescription" class="mb-0 text-secondary host-note-text"></p>
										<div class="host-note-meta d-flex flex-wrap gap-3 text-muted small">
											<div class="d-flex align-items-center gap-2">
												<i class="fa-solid fa-lightbulb text-warning"></i>
												<span>Check back for last-minute updates or changes.</span>
											</div>
											<div class="d-flex align-items-center gap-2">
												<i class="fa-solid fa-envelope-open-text text-primary"></i>
												<span>Invite emails include the host's contact info.</span>
											</div>
											<div class="d-flex align-items-center gap-2">
												<i class="fa-solid fa-gift text-success"></i>
												<span>Bring the vibe: budget, theme, and swap rules all live here.</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
                                        <div class="card shadow-sm border-0 mb-4 d-none" id="assignmentEmailCard">
                                                <div class="card-body p-4 d-flex flex-column gap-3">
                                                        <div class="d-flex align-items-center gap-3">
                                                                <div class="rounded-circle bg-info-subtle text-info-emphasis d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                                        <i class="fa-solid fa-envelope-circle-check"></i>
                                                                </div>
                                                                <div>
                                                                        <h2 class="h5 mb-1" id="assignmentEmailTitle">Assignment email status</h2>
                                                                        <p class="mb-0 text-muted" id="assignmentEmailSubtitle">We'll email your match when assignments are ready.</p>
                                                                </div>
                                                        </div>
                                                        <div class="d-flex flex-column flex-md-row gap-2 align-items-start" id="assignmentEmailActions">
                                                                <button class="btn btn-primary d-none" id="resendAssignmentEmail">
                                                                        <i class="fa-solid fa-arrow-rotate-right me-1"></i>Resend my assignment email
                                                                </button>
                                                                <div class="text-muted small" id="assignmentEmailNote"></div>
                                                        </div>
                                                        <div class="text-muted small" id="assignmentEmailStatus"></div>
                                                </div>
                                        </div>
					<!-- JOIN + EXCLUSIONS -->
					<div class="row mb-4" id="joinRows" data-state="<?php echo htmlspecialchars($joinRowsState); ?>" style="<?php echo htmlspecialchars($joinRowsInitialStyle); ?>">
						<div class="col-12">
							<div class="card shadow-sm border-0" id="joinFormCard">
								<div class="card-body p-4 d-flex flex-column gap-4">
									<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2 border-bottom">
										<div class="d-flex align-items-center gap-3">
											<div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
												<i class="fa-solid fa-user-plus fs-5"></i>
											</div>
											<div>
												<h2 class="h5 mb-1">Join this exchange</h2>
												<div class="text-muted small mb-0">Confirm your details, choose a group, and hop in.</div>
											</div>
										</div>
									</div>
									<?php if ($prefetchedEvent && $userIsParticipant && !$userIsHost) : ?>
									<div class="join-state-banner" id="prefetchedJoinBanner">
										<i class="fa-solid fa-circle-check fs-5"></i>
										<div>
											<div class="fw-semibold">You're already registered for <?php echo htmlspecialchars($prefetchedEventName); ?>.</div>
											<small>Look out for assignment emails or ask your host if details change.</small>
										</div>
									</div>
									<?php elseif ($prefetchedEvent && $userIsHost) : ?>
									<div class="join-state-banner join-state-host" id="prefetchedJoinBanner">
										<i class="fa-solid fa-crown fs-5"></i>
										<div>
											<div class="fw-semibold">You're hosting <?php echo htmlspecialchars($prefetchedEventName); ?>.</div>
											<small>Use the manage button below to adjust signups or reroll matches.</small>
										</div>
									</div>
									<?php endif; ?>
									<div id="joinHostPrompt" class="join-cta<?php echo $userIsHost ? '' : ' d-none'; ?>">
										<div class="join-cta-icon bg-warning-subtle text-warning-emphasis">
											<i class="fa-solid fa-crown"></i>
										</div>
										<div class="flex-grow-1">
											<div class="fw-semibold mb-1">You're hosting <span id="joinHostEventName"><?php echo htmlspecialchars($prefetchedEventName); ?></span></div>
											<p class="mb-0 text-muted small">Open the control room to adjust signups, reroll pairings, and send assignment emails.</p>
										</div>
										<div class="text-end">
											<a id="joinHostManageLink" href="<?php echo htmlspecialchars($hostManageUrl); ?>" class="btn btn-primary">
											<i class="fa-solid fa-wand-magic-sparkles me-2"></i>Manage event
											</a>
											<div class="small text-muted mt-2">Jumps to your host dashboard.</div>
										</div>
									</div>
                                    <div id="joinLoginPrompt" class="card mt-4 shadow-sm rounded<?php echo Session::isLoggedIn() ? ' d-none' : ''; ?>">
                                        <div class="card-body py-4 px-4 px-md-5 text-center d-flex flex-column gap-3">

                                            <div class="d-flex flex-column gap-2">
                                                <div class="mb-2">
                                                    <img src="/assets/media/logo.png" alt="Kickback Kingdom" style="width: 150px; height: 150px;">
                                                </div>

                                                <h5 class="mb-1">
                                                    <i class="fa-solid fa-exclamation-triangle fa-lg me-2 text-muted"></i>
                                                    Login Required
                                                </h5>

                                                <p class="mb-0 text-muted">
                                                    You must be logged in to view the Secret Santa page.
                                                </p>
                                            </div>

                                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-2">
                                                <a href="<?php echo htmlspecialchars($loginRedirectUrl); ?>" class="btn btn-primary w-100 w-md-auto">
                                                    Log In
                                                </a>
                                            </div>

                                        </div>
                                    </div>

									<div id="joinFormSection" class="join-panel d-flex flex-column gap-4<?php echo Session::isLoggedIn() ? '' : ' d-none'; ?>">
										<form id="joinForm" class="d-flex flex-column gap-4">
											<div class="d-flex align-items-start gap-3 flex-wrap pb-2 border-bottom">
												<div class="join-pill"><i class="fa-solid fa-user"></i> You, as the giver</div>
												<div class="d-flex flex-column">
													<div class="fw-semibold">Your profile is ready to join</div>
													<div class="small text-muted">We pre-fill your info so your host knows who joined.</div>
												</div>
												<span class="badge bg-light text-secondary border ms-auto">Auto-filled</span>
											</div>
											<div class="row gy-3">
												<div class="col-md-6">
													<div class="small text-muted">Display name</div>
													<div class="fw-semibold fs-6" id="participantDisplayNameText">
														<?php echo htmlspecialchars($defaultDisplayName) ?: 'Secret Santa adventurer'; ?>
													</div>
												</div>
												<div class="col-md-6">
													<div class="small text-muted">Email</div>
													<div class="fw-semibold fs-6" id="participantEmailText">
														<?php echo htmlspecialchars($defaultEmail) ?: 'Update your account email to join'; ?>
													</div>
												</div>
												<div class="col-12">
													<div class="join-meta">
														<div class="small text-muted">Why we need this</div>
														<ul class="list-unstyled small text-secondary mb-0 d-flex flex-column gap-1">
															<li class="d-flex gap-2"><i class="fa-solid fa-envelope text-primary"></i><span>We email your confirmation and assignment.</span></li>
															<li class="d-flex gap-2"><i class="fa-solid fa-pen-to-square text-success"></i><span>Your host can recognize who joined.</span></li>
														</ul>
													</div>
												</div>
											</div>
											<div class="d-flex align-items-center gap-3 flex-wrap">
												<div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
													<i class="fa-solid fa-gift"></i>
												</div>
												<div>
													<div class="fw-semibold mb-1">How should your Santa encourage you?</div>
													<small class="text-muted">Share one thing you want to learn or try so your Santa can choose a thoughtful push.</small>
												</div>
												<div class="ms-auto join-hint"><i class="fa-solid fa-stars"></i> Still a surprise</div>
											</div>
											<input type="hidden" id="participantExclusionCtime">
											<input type="hidden" id="participantExclusionCrand">
											<div class="d-flex flex-column gap-2">
												<label class="form-label mb-1" for="interest">Learning or trying next</label>
												<textarea class="form-control shadow-sm" id="interest" rows="3" placeholder="Example: Learning to draw, wants to cook more, wants to relax more"></textarea>
												<div class="form-text">Keep it short. Your Santa will pick a gift that nudges you toward this goal.</div>
											</div>
											<div class="d-flex flex-column gap-2">
												<div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
													<label class="form-label mb-0" for="exclusionSelect">Exclusion group (optional)</label>
													<div class="d-flex gap-2">
														<button type="button" class="btn btn-outline-primary btn-sm" id="openExclusionModal">
														<i class="fa-solid fa-users-gear me-1"></i>Add group
														</button>
														<button type="button" class="btn btn-outline-secondary btn-sm" id="refreshExclusions">
														<i class="fa-solid fa-rotate me-1"></i>Refresh
														</button>
													</div>
												</div>
												<select class="form-select shadow-sm" id="exclusionSelect">
													<option value="">No exclusion group</option>
												</select>
												<div class="form-text">Pick who should never draw each other. Couples or roommates usually share a group.</div>
											</div>
											<div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3 pt-1">
												<button class="btn btn-success px-4" type="submit">
												<i class="fa-solid fa-gift me-2"></i>Join event
												</button>
												<div id="joinStatus" class="small text-muted"></div>
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>
						<div class="modal fade" id="exclusionModal" tabindex="-1" aria-labelledby="exclusionModalLabel" aria-hidden="true">
							<div class="modal-dialog modal-dialog-centered">
								<div class="modal-content">
									<div class="modal-header">
										<h5 class="modal-title" id="exclusionModalLabel">Add exclusion group</h5>
										<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
									</div>
									<div class="modal-body">
										<form id="exclusionBuilder" class="d-flex flex-column gap-3">
											<div>
												<label class="form-label mb-1" for="newExclusionName">Group name</label>
												<input class="form-control" id="newExclusionName" placeholder="Roommates, partners, work team">
											</div>
											<input type="hidden" id="newExclusionCtime">
											<input type="hidden" id="newExclusionCrand">
											<div id="exclusionBuilderStatus" class="small text-muted mb-0"></div>
										</form>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
										<button class="btn btn-primary" form="exclusionBuilder" type="submit">Add exclusion group</button>
									</div>
								</div>
							</div>
						</div>
						<div class="card shadow-sm border-0 mb-4" id="participantListCard" style="display:none;">
							<div class="card-body p-4">
								<div class="d-flex align-items-center mb-3">
									<div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
										<i class="fa-solid fa-list-check"></i>
									</div>
									<div>
										<h2 class="h6 mb-0">Who's already in</h2>
										<small class="text-muted">Participants and their exclusion groups.</small>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table table-sm align-middle mb-0" id="participantTable">
										<thead class="table-light">
											<tr>
												<th scope="col">Name</th>
												<th scope="col">Email</th>
												<th scope="col">Encouragement focus</th>
												<th scope="col">Exclusion group</th>
											</tr>
										</thead>
										<tbody id="participantTableBody">
											<tr class="table-light">
												<td colspan="4" class="text-center text-muted">No one has signed up yet.</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					<!-- RULES / FUN COPY -->
					<section class="mb-4">
						<div class="card shadow-sm border-0">
							<div class="card-body p-4">
								<div class="d-flex align-items-center mb-2">
									<div class="rounded-circle bg-warning-subtle text-warning-emphasis d-inline-flex align-items-center justify-content-center me-2" style="width: 42px; height: 42px;">
										<i class="fa-solid fa-scroll"></i>
									</div>
									<div>
										<div class="small text-uppercase text-muted mb-0">House rules</div>
										<h2 class="h5 mb-0">Make the exchange fair and festive</h2>
									</div>
								</div>
								<div class="row g-4 mt-1">
									<div class="col-md-4">
										<h3 class="h6 mb-1">Pairing magic</h3>
										<ul class="list-unstyled small text-secondary mb-0">
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>No self-pairing—we only match you with someone new.</span>
											</li>
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Exclusion groups keep partners, roommates, or teams apart.</span>
											</li>
											<li class="d-flex align-items-start">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Hosts can reroll pairings if plans change.</span>
											</li>
										</ul>
									</div>
									<div class="col-md-4">
										<h3 class="h6 mb-1">Timeline checkpoints</h3>
										<ul class="list-unstyled small text-secondary mb-0">
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Signup closes before names are drawn.</span>
											</li>
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Gift exchange day gets a live countdown right here.</span>
											</li>
											<li class="d-flex align-items-start">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Hosts can reopen signups if new friends appear.</span>
											</li>
										</ul>
									</div>
									<div class="col-md-4">
										<h3 class="h6 mb-1">Budget + vibe</h3>
										<ul class="list-unstyled small text-secondary mb-0">
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Stick to the posted budget so everyone feels comfy.</span>
											</li>
											<li class="d-flex align-items-start mb-1">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Use the description for any theme, wishlist, or swap rules.</span>
											</li>
											<li class="d-flex align-items-start">
												<i class="fa-solid fa-check text-success me-2 mt-1"></i>
												<span>Have fun! The Kingdom keeps the admin tidy.</span>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>
				<?php require("../php-components/base-page-discord.php"); ?>
			</div>
			<?php require("../php-components/base-page-footer.php"); ?>
		</main>
		<?php require("../php-components/base-page-javascript.php"); ?>
		<script>
			const inviteTokenFromUrl = <?php echo json_encode($inviteToken); ?>;
			const accountDisplayName = <?php echo json_encode($defaultDisplayName); ?>;
			const accountEmail = <?php echo json_encode($defaultEmail); ?>;
			const accountCrand = <?php echo json_encode($kickbackAccount ? $kickbackAccount->crand : null); ?>;
			const isLoggedIn = <?php echo json_encode(Session::isLoggedIn()); ?>;
			const registerRedirectUrl = <?php echo json_encode($registerRedirectUrl); ?>;
			const managePageBaseUrl = <?php echo json_encode($managePageBaseUrl); ?>;
			const prefetchedEvent = <?php echo json_encode($prefetchedEvent); ?>;
			const invitePrefetchMessage = <?php echo json_encode($invitePrefetchMessage); ?>;
			const inviteStatus = document.getElementById('inviteStatus');
			const heroEventTitle = document.getElementById('heroEventTitle');
			const heroEventSubtitle = document.getElementById('heroEventSubtitle');
			const eventDetailsCard = document.getElementById('eventDetailsCard');
			const eventDescEl = document.getElementById('eventDescription');
			const joinRows = document.getElementById('joinRows');
			const joinFormSection = document.getElementById('joinFormSection');
			const joinLoginPrompt = document.getElementById('joinLoginPrompt');
			const joinHostPrompt = document.getElementById('joinHostPrompt');
			const joinHostEventName = document.getElementById('joinHostEventName');
			const joinHostManageLink = document.getElementById('joinHostManageLink');
			const joinForm = document.getElementById('joinForm');
			const joinStatus = document.getElementById('joinStatus');
			const hostSignupLink = document.getElementById('hostSignupLink');
			const participantExclusionCtime = document.getElementById('participantExclusionCtime');
			const participantExclusionCrand = document.getElementById('participantExclusionCrand');
			const interestInput = document.getElementById('interest');
			const exclusionSelect = document.getElementById('exclusionSelect');
			const exclusionBuilderCard = document.getElementById('exclusionBuilderCard');
			const exclusionBuilder = document.getElementById('exclusionBuilder');
			const exclusionBuilderStatus = document.getElementById('exclusionBuilderStatus');
			const newExclusionName = document.getElementById('newExclusionName');
			const newExclusionCtime = document.getElementById('newExclusionCtime');
			const newExclusionCrand = document.getElementById('newExclusionCrand');
			const exclusionModalElement = document.getElementById('exclusionModal');
			const exclusionModal = exclusionModalElement ? new bootstrap.Modal(exclusionModalElement) : null;
			const openExclusionModalBtn = document.getElementById('openExclusionModal');
			const refreshExclusionsBtn = document.getElementById('refreshExclusions');
			const participantListCard = document.getElementById('participantListCard');
			const participantTableBody = document.getElementById('participantTableBody');
			const countdownCard = document.getElementById('countdownCard');
			const signupCountdown = document.getElementById('signupCountdown');
			const giftCountdown = document.getElementById('giftCountdown');
			const countSignupDays = document.getElementById('countSignupDays');
			const countSignupHours = document.getElementById('countSignupHours');
			const countSignupMinutes = document.getElementById('countSignupMinutes');
			const countSignupSeconds = document.getElementById('countSignupSeconds');
			const countGiftDays = document.getElementById('countGiftDays');
			const countGiftHours = document.getElementById('countGiftHours');
			const countGiftMinutes = document.getElementById('countGiftMinutes');
			const countGiftSeconds = document.getElementById('countGiftSeconds');
			const countdownNote = document.getElementById('countdownNote');
			const countNumbers = document.querySelectorAll('.count-number');
                        const assignmentEmailCard = document.getElementById('assignmentEmailCard');
                        const assignmentEmailTitle = document.getElementById('assignmentEmailTitle');
                        const assignmentEmailSubtitle = document.getElementById('assignmentEmailSubtitle');
                        const assignmentEmailNote = document.getElementById('assignmentEmailNote');
                        const assignmentEmailStatus = document.getElementById('assignmentEmailStatus');
                        const resendAssignmentEmailButton = document.getElementById('resendAssignmentEmail');
                        const serverAssignmentDefaults = {
                            assignmentsGenerated: <?php echo json_encode($serverAssignmentsGenerated); ?>,
                            userIsParticipant: <?php echo json_encode($userIsParticipant); ?>,
                            currentAssignment: <?php echo json_encode($serverCurrentAssignment); ?>
                        };
			let countdownInterval = null;
			let currentEvent = null;
			let currentExclusionGroup = { ctime: '', crand: '' };
			let exclusionGroups = [];
			let participants = [];
			let assignmentsGenerated = serverAssignmentDefaults.assignmentsGenerated;
			let currentAssignment = serverAssignmentDefaults.currentAssignment;
			let hostSignupRedirect = registerRedirectUrl;
			let hasQueuedSignupRedirect = false;
			
			function setStatus(element, message) {
			    if (!element) return;
			    element.textContent = message || '';
			    if (message) {
			        element.classList.remove('d-none');
			    } else {
			        element.classList.add('d-none');
			    }
			}
			
			function toggleSection(element, shouldShow) {
			    if (!element) return;
			    if (shouldShow) {
			        element.classList.remove('d-none');
			    } else {
			        element.classList.add('d-none');
			    }
			}
			
			async function getJson(url) {
			    const resp = await fetch(url, { credentials: 'include' });
			    return resp.json();
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
			
			function setCountdowns(signupDate, giftDate) {
			    const countdownElements = [
			        countdownCard,
			        signupCountdown,
			        giftCountdown,
			        countSignupDays,
			        countSignupHours,
			        countSignupMinutes,
			        countSignupSeconds,
			        countGiftDays,
			        countGiftHours,
			        countGiftMinutes,
			        countGiftSeconds,
			        countdownNote
			    ];
			
			    if (countdownElements.some(el => !el)) {
			        console.warn('Countdown elements missing; skipping timer updates.');
			        return;
			    }
			
			    if (countdownInterval) {
			        clearInterval(countdownInterval);
			    }
			
			    if (!signupDate && !giftDate) {
			        countdownCard.style.display = 'none';
			        return;
			    }
			
			    countdownCard.style.display = 'block';
			    countdownNote.textContent = '';
			
			    const updateBlock = (targetDate, elements) => {
			        if (!targetDate) {
			            elements.container.style.display = 'none';
			            return false;
			        }
			
			        const now = new Date();
			        const diff = targetDate - now;
			
			        elements.container.style.display = 'flex';
			
			        if (diff <= 0) {
			            elements.days.textContent = '00';
			            elements.hours.textContent = '00';
			            elements.minutes.textContent = '00';
			            elements.seconds.textContent = '00';
			            return true;
			        }
			
			        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
			        const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
			        const minutes = Math.floor((diff / (1000 * 60)) % 60);
			        const seconds = Math.floor((diff / 1000) % 60);
			
			        elements.days.textContent = String(days).padStart(2, '0');
			        elements.hours.textContent = String(hours).padStart(2, '0');
			        elements.minutes.textContent = String(minutes).padStart(2, '0');
			        elements.seconds.textContent = String(seconds).padStart(2, '0');
			        return false;
			    };
			
			    const updateCountdown = () => {
			        const signupEnded = !signupDate ? false : updateBlock(signupDate, {
			            container: signupCountdown,
			            days: countSignupDays,
			            hours: countSignupHours,
			            minutes: countSignupMinutes,
			            seconds: countSignupSeconds
			        });
			
			        const giftEnded = !giftDate ? false : updateBlock(giftDate, {
			            container: giftCountdown,
			            days: countGiftDays,
			            hours: countGiftHours,
			            minutes: countGiftMinutes,
			            seconds: countGiftSeconds
			        });
			
			        countNumbers.forEach(el => {
			            el.classList.remove('pulse');
			            void el.offsetWidth;
			            el.classList.add('pulse');
			        });
			
			        if (signupEnded && giftEnded) {
			            countdownNote.textContent = 'Countdown finished—check with your host for the latest details.';
			        } else if (signupEnded && giftDate) {
			            countdownNote.textContent = 'Signups are closed. Gift exchange countdown continues below.';
			        } else {
			            countdownNote.textContent = '';
			        }
			    };
			
			    updateCountdown();
			    countdownInterval = setInterval(updateCountdown, 1000);
			}
			
			function renderExclusionOptions(groups) {
			    exclusionSelect.innerHTML = '<option value="">No exclusion group</option>';
			    groups.forEach(group => {
			        const option = document.createElement('option');
			        option.value = `${group.ctime}|${group.crand}`;
			        option.textContent = group.group_name;
			        exclusionSelect.appendChild(option);
			    });
			}
			
			function getExclusionName(ctime, crand) {
			    if (!ctime || !crand) return '';
			    const match = exclusionGroups.find(group => group.ctime === ctime && String(group.crand) === String(crand));
			    return match ? match.group_name : '';
			}
			
			function renderParticipants() {
			    participantTableBody.innerHTML = '';
			
			    if (!participants.length) {
			        participantTableBody.innerHTML = '<tr class="table-light"><td colspan="4" class="text-center text-muted">No one has signed up yet.</td></tr>';
			        participantListCard.style.display = 'block';
			        return;
			    }
			
			    participants.forEach(person => {
			        const row = document.createElement('tr');
			        const exclusionName = person.exclusion_group_name || getExclusionName(person.exclusion_group_ctime, person.exclusion_group_crand);
			        row.innerHTML = `
			            <td>${person.display_name || 'Unknown adventurer'}</td>
			            <td>${person.email || ''}</td>
			            <td>${person.interest || '—'}</td>
			            <td>${exclusionName || 'None'}</td>
			        `;
			        participantTableBody.appendChild(row);
			    });
			
			    participantListCard.style.display = 'block';
			}
			
			function resetExclusionBuilder() {
			    if (newExclusionName) newExclusionName.value = '';
			    if (newExclusionCtime) newExclusionCtime.value = currentExclusionGroup.ctime || '';
			    if (newExclusionCrand) newExclusionCrand.value = currentExclusionGroup.crand || '';
			    if (exclusionBuilderStatus) exclusionBuilderStatus.textContent = '';
			}
			
			function isCurrentUserParticipant() {
			    const emailLower = (accountEmail || '').trim().toLowerCase();
			
			    if (participants.length === 0 && serverAssignmentDefaults.userIsParticipant) {
			        return true;
			    }
			
			    return participants.some(participant => {
			        const participantEmail = (participant.email || '').trim().toLowerCase();
			        const participantAccountId = participant.account_id ?? participant.account_crand ?? null;
			
			        if (accountCrand !== null && participantAccountId !== null && String(participantAccountId) === String(accountCrand)) {
			            return true;
			        }
			
			        return !!emailLower && !!participantEmail && participantEmail === emailLower;
			    });
			}
			
                        function updateAssignmentEmailState() {
                            if (!assignmentEmailCard) {
                                return;
                            }

                            const userIsParticipant = isCurrentUserParticipant();
                            const canShow = isLoggedIn && currentEvent && userIsParticipant;

                            if (!canShow) {
                                assignmentEmailCard.classList.add('d-none');
                                return;
                            }

                            assignmentEmailCard.classList.remove('d-none');
                            if (assignmentEmailStatus) {
                                assignmentEmailStatus.textContent = '';
                            }

                            const assignmentsReady = assignmentsGenerated && !!currentAssignment;

                            if (!assignmentsReady) {
                                if (assignmentEmailTitle) assignmentEmailTitle.textContent = 'Assignment email pending';
                                if (assignmentEmailSubtitle)
                                    assignmentEmailSubtitle.textContent = 'We will email your match as soon as assignments are finalized.';
                                toggleSection(resendAssignmentEmailButton, false);
                                if (assignmentEmailNote) assignmentEmailNote.textContent = 'Waiting on the host to send assignments.';
                                return;
                            }

                            if (assignmentEmailTitle) assignmentEmailTitle.textContent = 'Assignment email sent';
                            if (assignmentEmailSubtitle)
                                assignmentEmailSubtitle.textContent = 'We sent your Secret Santa match to your inbox.';
                            toggleSection(resendAssignmentEmailButton, true);
                            if (assignmentEmailNote)
                                assignmentEmailNote.textContent = accountEmail
                                    ? `We'll resend to ${accountEmail}.`
                                    : 'We will resend your assignment email.';
                        }

                        function setAssignmentStateFromEvent(event) {
                            assignmentsGenerated = !!(event && event.assignments_generated);
                            currentAssignment = event && event.current_assignment ? event.current_assignment : null;
                            updateAssignmentEmailState();
                        }
			
			function updateJoinVisibility(alreadyJoinedMessage = 'You are already registered for this exchange.') {
			    if (!joinRows || !inviteStatus) return;
			
			    const userIsParticipant = isCurrentUserParticipant();
			    const userIsHost = Boolean(
			        currentEvent && accountCrand !== null && currentEvent.owner_id && String(currentEvent.owner_id) === String(accountCrand)
			    );
			    const shouldShowLoginPrompt = !isLoggedIn;
			    const shouldShowHostPrompt = userIsHost;
			    const shouldShowForm = !shouldShowLoginPrompt && !shouldShowHostPrompt && !userIsParticipant;
			
			    toggleSection(joinLoginPrompt, shouldShowLoginPrompt);
			    toggleSection(joinHostPrompt, shouldShowHostPrompt);
			    toggleSection(joinFormSection, shouldShowForm);
			
			    if (shouldShowHostPrompt) {
			        joinRows.style.display = '';
			        if (joinHostEventName && currentEvent?.name) {
			            joinHostEventName.textContent = currentEvent.name;
			        }
			        if (joinHostManageLink) {
			            const manageUrl = currentEvent?.invite_token
			                ? `${managePageBaseUrl}?invite_token=${encodeURIComponent(currentEvent.invite_token)}`
			                : managePageBaseUrl;
			            joinHostManageLink.href = manageUrl;
			        }
                                updateAssignmentEmailState();
			        return;
			    }
			
			    if (shouldShowLoginPrompt) {
			        joinRows.style.display = '';
			        if (!hasQueuedSignupRedirect && hostSignupRedirect) {
			            hasQueuedSignupRedirect = true;
			            setTimeout(() => {
			                window.location.href = hostSignupRedirect;
			            }, 400);
			        }
                                updateAssignmentEmailState();
			        return;
			    }
			
			    if (userIsParticipant) {
			        joinRows.style.display = 'none';
			        setStatus(inviteStatus, alreadyJoinedMessage);
			    } else {
			        joinRows.style.display = '';
			        setStatus(inviteStatus, inviteStatus.textContent);
			    }
			
                            updateAssignmentEmailState();
			}
                        function renderEvent(event) {
                if (!eventDetailsCard || !joinRows || !participantListCard) {
                    console.warn('Invite page elements missing; cannot render event UI.');
                    return;
                }

                eventDetailsCard.style.display = 'block';

                // Host-only card
                if (exclusionBuilderCard) {
                    exclusionBuilderCard.style.display = 'block';
                }

                // Load exclusion groups only if the UI exists
                setExclusionGroup('', '');
                exclusionGroups = event.exclusion_groups || [];
                if (exclusionBuilderCard) {
                    renderExclusionOptions(exclusionGroups);
                }

                // --- SAFE DATE PARSER FOR MYSQL DATETIME ---
                function parseUtcDate(raw) {
                    if (!raw) return null;
                    const iso = raw.replace(' ', 'T') + 'Z';
                    const d = new Date(iso);
                    return isNaN(d.getTime()) ? null : d;
                }

                const signup = parseUtcDate(event.signup_deadline);
                const gift   = parseUtcDate(event.gift_deadline);


                // --- HERO TITLE / SUBTITLE ---
                heroEventTitle.textContent = event.name;

                if (signup && gift) {
                    heroEventSubtitle.textContent =
                        `Signup closes ${signup.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}` +
                        ` • Gifts due ${gift.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`;
                } else if (gift) {
                    heroEventSubtitle.textContent =
                        `Gifts due ${gift.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })}`;
                } else {
                    heroEventSubtitle.textContent = 'Use your invite link to load the event details.';
                }

                eventDescEl.textContent = event.description || 'No description provided.';

                // --- SIGNUP REDIRECT ---
                hostSignupRedirect = event.write_of_passage_link || event.host_signup_link || registerRedirectUrl;
                if (hostSignupLink) {
                    hostSignupLink.href = hostSignupRedirect;
                    hostSignupLink.textContent =
                        (event.write_of_passage_link || event.host_signup_link)
                        ? 'Sign up with the host link'
                        : 'Create an account to join';
                }

                joinRows.style.display = '';

                // --- PARTICIPANTS ---
                participants = event.participants || [];
                renderParticipants();

                // --- ASSIGNMENT STATUS ---
                setAssignmentStateFromEvent(event);
                updateJoinVisibility();

                // --- COUNTDOWN LOGIC ---
                const now = new Date();

                // signup countdown only shows if still in the future
                const signupDate = signup && signup > now ? signup : null;

                // gift countdown ALWAYS shows until the date has passed
                const giftDate = gift ? gift : null;

                setCountdowns(signupDate, giftDate);
            }

			
			function setExclusionGroup(ctime, crand) {
			    currentExclusionGroup = {
			        ctime: ctime ?? '',
			        crand: crand ?? ''
			    };
			    participantExclusionCtime.value = currentExclusionGroup.ctime;
			    participantExclusionCrand.value = currentExclusionGroup.crand;
			    newExclusionCtime.value = currentExclusionGroup.ctime;
			    newExclusionCrand.value = currentExclusionGroup.crand;
			    const matchValue = currentExclusionGroup.ctime && currentExclusionGroup.crand
			        ? `${currentExclusionGroup.ctime}|${currentExclusionGroup.crand}`
			        : '';
			    exclusionSelect.value = matchValue;
			}
			
			async function validateInvite(token) {
			    setStatus(inviteStatus, 'Checking invite...');
			    if (eventDetailsCard) eventDetailsCard.style.display = 'none';
			    if (joinRows) joinRows.style.display = 'none';
			    if (exclusionBuilderCard) exclusionBuilderCard.style.display = 'none';
			    if (countdownCard) countdownCard.style.display = 'none';
			    currentEvent = null;
			    exclusionGroups = [];
			    participants = [];
			    hostSignupRedirect = registerRedirectUrl;
			    hasQueuedSignupRedirect = false;
			    if (hostSignupLink) hostSignupLink.href = hostSignupRedirect;
			    if (participantListCard) participantListCard.style.display = 'none';
			    assignmentsGenerated = false;
			    currentAssignment = null;
                            updateAssignmentEmailState();
			    try {
			        const resp = await getJson(`/api/v1/secret-santa/validate-invite.php?invite_token=${encodeURIComponent(token)}`);
			        setStatus(inviteStatus, resp.message || '');
			        if (resp.success) {
			            currentEvent = resp.data;
			            renderEvent(resp.data);
			        }
			    } catch (err) {
			        console.error(err);
			        setStatus(inviteStatus, 'Unable to validate invite token right now.');
			    }
			}
			
			if (prefetchedEvent) {
			    setStatus(inviteStatus, invitePrefetchMessage || 'Invite loaded.');
			    currentEvent = prefetchedEvent;
			    renderEvent(prefetchedEvent);
			} else if (inviteTokenFromUrl) {
			    setStatus(inviteStatus, invitePrefetchMessage || 'Checking invite...');
			    validateInvite(inviteTokenFromUrl);
			} else {
			    setStatus(inviteStatus, 'No invite token detected. Please use your invite link.');
			}
			
			if (openExclusionModalBtn && exclusionModal) {
			    openExclusionModalBtn.addEventListener('click', () => {
			        if (!currentEvent) return;
			        resetExclusionBuilder();
			        exclusionModal.show();
			        setTimeout(() => newExclusionName && newExclusionName.focus(), 200);
			    });
			
			    exclusionModalElement.addEventListener('hidden.bs.modal', () => {
			        resetExclusionBuilder();
			    });
			}
			
                        if (refreshExclusionsBtn) {
                            const defaultRefreshContent = refreshExclusionsBtn.innerHTML;
                            refreshExclusionsBtn.addEventListener('click', async () => {
                                if (!currentEvent?.invite_token) return;
                                refreshExclusionsBtn.disabled = true;
			        refreshExclusionsBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Refreshing';
			        try {
			            await validateInvite(currentEvent.invite_token);
			        } catch (err) {
			            console.error(err);
			        } finally {
			            refreshExclusionsBtn.disabled = false;
			            refreshExclusionsBtn.innerHTML = defaultRefreshContent;
                                }
                            });
                        }

                        if (resendAssignmentEmailButton) {
                            const defaultResendContent = resendAssignmentEmailButton.innerHTML;
                            resendAssignmentEmailButton.addEventListener('click', async () => {
                                if (!currentEvent || !assignmentsGenerated || !currentAssignment) {
                                    setStatus(assignmentEmailStatus, 'Assignments are not ready yet.');
                                    return;
                                }

                                resendAssignmentEmailButton.disabled = true;
                                resendAssignmentEmailButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending';
                                try {
                                    const resp = await postForm('/api/v1/secret-santa/resend-assignment.php', {
                                        event_ctime: currentEvent.ctime,
                                        event_crand: currentEvent.crand
                                    });
                                    setStatus(assignmentEmailStatus, resp.message || '');
                                } catch (err) {
                                    console.error(err);
                                    setStatus(assignmentEmailStatus, 'Unable to resend your assignment right now.');
                                } finally {
                                    resendAssignmentEmailButton.disabled = false;
                                    resendAssignmentEmailButton.innerHTML = defaultResendContent;
                                }
                            });
                        }
			
			joinForm.addEventListener('submit', async (e) => {
			    e.preventDefault();
			    if (!currentEvent) return;
			    joinStatus.textContent = 'Submitting...';
			    try {
			        const displayName = (accountDisplayName || '').trim() || 'Secret Santa adventurer';
			        const email = (accountEmail || '').trim();
			        const interest = (interestInput?.value || '').trim();
			
			        if (!email) {
			            joinStatus.textContent = 'Missing account email. Please update your profile and try again.';
			            return;
			        }
			
			        const resp = await postForm('/api/v1/secret-santa/join-event.php', {
			            invite_token: currentEvent.invite_token,
			            display_name: displayName,
			            email: email,
			            ...(interest ? { interest } : {}),
			            ...(participantExclusionCtime.value ? { exclusion_group_ctime: participantExclusionCtime.value } : {}),
			            ...(participantExclusionCrand.value ? { exclusion_group_crand: participantExclusionCrand.value } : {})
			        });
			        joinStatus.textContent = resp.message || '';
			        if (resp.success && resp.data && resp.data.event) {
			            setStatus(inviteStatus, 'You are in!');
			            const addedParticipant = resp.data.participant || {
			                display_name: displayName,
			                email: email,
			                interest,
			                exclusion_group_ctime: participantExclusionCtime.value,
			                exclusion_group_crand: participantExclusionCrand.value
			            };
			            addedParticipant.account_id = resp.data.participant?.account_id ?? accountCrand ?? null;
			            addedParticipant.exclusion_group_name = getExclusionName(addedParticipant.exclusion_group_ctime, addedParticipant.exclusion_group_crand);
			            participants.push(addedParticipant);
			            renderParticipants();
			            updateJoinVisibility('You are in!');
			        }
			    } catch (err) {
			        console.error(err);
			        joinStatus.textContent = 'Unable to join right now.';
			    }
			});
			
			exclusionBuilder.addEventListener('submit', async (e) => {
			    e.preventDefault();
			    if (!currentEvent) return;
			    exclusionBuilderStatus.textContent = 'Saving...';
			    try {
			        const payload = {
			            event_ctime: currentEvent.ctime,
			            event_crand: currentEvent.crand,
			            name: document.getElementById('newExclusionName').value
			        };
			
			        if (newExclusionCtime.value) {
			            payload.exclusion_group_ctime = newExclusionCtime.value;
			        }
			
			        if (newExclusionCrand.value) {
			            payload.exclusion_group_crand = newExclusionCrand.value;
			        }
			
			        const resp = await postForm('/api/v1/secret-santa/exclusion-group.php', payload);
			        exclusionBuilderStatus.textContent = resp.message || '';
			        if (resp.success && resp.data) {
			            setExclusionGroup(resp.data.ctime, resp.data.crand);
			            exclusionGroups.push({
			                ctime: resp.data.ctime,
			                crand: resp.data.crand,
			                group_name: document.getElementById('newExclusionName').value
			            });
			            renderExclusionOptions(exclusionGroups);
			            setExclusionGroup(resp.data.ctime, resp.data.crand);
			            renderParticipants();
			            if (exclusionModal) exclusionModal.hide();
			        }
			    } catch (err) {
			        console.error(err);
			        exclusionBuilderStatus.textContent = 'Could not save exclusion group.';
			    }
			});
			
			exclusionSelect.addEventListener('change', (e) => {
			    if (!e.target.value) {
			        setExclusionGroup('', '');
			        return;
			    }
			    const [ctime, crand] = e.target.value.split('|');
			    setExclusionGroup(ctime, crand);
			});
		</script>
	</body>
</html>