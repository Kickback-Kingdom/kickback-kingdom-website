<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");

use Kickback\Backend\Controllers\AccountController;
use Kickback\Services\Session;
use Kickback\Backend\Views\vRecordId;

$markyAccount = AccountController::getAccountById(new vRecordId('', 13))->data;
$coleAccount = AccountController::getAccountById(new vRecordId('', 164))->data;

?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                <?php 
                    $activePageName = "Welcome Alpha Team!";
                    require("php-components/base-page-breadcrumbs.php"); 
                ?>

                <!-- Alpha Team Welcome Card with Banner -->
                <div class="card shadow-lg mb-4 overflow-hidden">
                    <!-- Banner Image with Flag -->
<div class="position-relative" style="height: 240px; background-image: url('https://kickback-kingdom.com/assets/media/games/1217.png'); background-size: cover; background-position: center;">
    <img src="https://kickback-kingdom.com/assets/media/quests/1222.png" alt="Alpha Team Flag" class="img-thumbnail position-absolute top-50 start-50 translate-middle shadow-lg" style="max-height: 70%; max-width: 70%;">
</div>


                    <div class="card-body p-5">
                        <h1 class="display-4 fw-bold text-center mb-4">Welcome, Alpha Team!</h1>
                        
                        <p class="lead text-center">
                            On behalf of all of us at <strong>Kickback Kingdom</strong>, we want to give a warm and heartfelt welcome to the entire <strong>Alpha Team</strong> community.
                        </p>
                        <!-- Message from a Kingdom Member -->
                        <div class="d-flex flex-column flex-md-row align-items-start bg-body-secondary rounded-3 p-4 mb-5 shadow-sm border">
                            <!-- Profile Picture -->
                            <a href="/u/AdmiralBurbeh" class="mb-3 mb-md-0 me-md-4">
                                <img src="/assets/media/profiles/young-6.jpg" alt="AdmiralBurbeh's profile picture" width="80" height="80" class="rounded-circle border border-3 border-success">
                            </a>

                            <!-- Message -->
                            <div>
                                <p class="mb-2 fs-5 fst-italic">
                                    "Hello hello, Alpha Team! It's an honor to have you here. Since the old days of liberating entire planets from our enemies, we've stood united with our allies against all odds! Now, join us to liberate&nbsp;Palworld&nbsp;next!"
                                </p>
                                <p class="mb-0">
                                    — <a href="/u/Colethedragon" class="username">Colethedragon</a>
                                </p>
                            </div>
                        </div>

                        <!-- End Message -->

                        <!-- Palworld Launch Quest Panel -->
                        <div class="bg-light rounded-3 p-4 mt-5 border border-success shadow-sm">
                            <div class="text-center mb-4">
                                <h2 class="fw-bold text-success">
                                    <a href="/q/alpha-team-palworld-server-launch" class="text-success text-decoration-none">
                                        Palworld Server Launch Quest
                                    </a>
                                </h2>

                                <p class="mb-1 text-muted">Hosted by 
                                <?= $markyAccount->getAccountElement(); ?>
                                </p>
                            </div>

                            <div class="mx-auto" style="max-width: 720px;">
                                <p class="text-center fs-5">
                                    We're thrilled to announce a brand new <strong>Palworld server</strong> built just for Alpha Team! This is more than just a server — it's the start of a legendary journey together.
                                </p>
                                <p class="text-center fs-5">
                                    Tame creatures, build empires, and forge new alliances with your fellow guildmates in a world designed for shared triumphs.
                                </p>
                            </div>

                            <!-- Raffle Reward Highlight -->
                            <div class="bg-dark text-light rounded-3 p-4 mt-5 text-center shadow-sm border border-warning mx-auto" style="max-width: 700px;">
                                <h4 class="fw-bold mb-3 text-warning">🎟️ Quest Reward: Raffle Ticket</h4>
                                <p class="mb-0 fs-5">
                                    By joining the quest, you'll earn <strong>Raffle Tickets</strong> — your key to entering the 
                                    <a href="/quest-line/monthly-raffle" class="text-warning fw-semibold text-decoration-none">Great Kickback Kingdom Raffles</a>.
                                    Win awesome video games, cosmetics, or real-world rewards!
                                </p>
                            </div>

                            <!-- Call to Action Buttons -->
                            <div class="text-center mt-5">
                                <a href="/q/alpha-team-palworld-server-launch"
                                class="btn btn-success btn-lg px-5 py-3 mb-3 animate__animated animate__pulse animate__infinite animate__slow">
                                    Join the Palworld Server Launch
                                </a>

                                <br />
                                <a href="https://discord.gg/NhTZwaWfqu" class="btn bg-ranked-1 btn-lg">
                                    Join the Community on Discord <i class="fa-brands fa-discord" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                        <!-- End Launch Quest Panel -->

                    </div>
                </div>
                <!-- End Welcome Card with Banner -->

                

<style>
    .server-card {
    position: relative;
    color: white;
    border-radius: 0.75rem;
    overflow: hidden;
    min-height: 240px;
    box-shadow: 0 0 20px rgba(0,0,0,0.2);
    transition: transform 0.2s ease-in-out;
}

.server-card:hover {
    transform: scale(1.02);
}

/* Background image + dark overlay */
.server-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    filter: brightness(1) blur(2px);
    z-index: 1;
}

.server-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4); /* Full dark overlay */
    z-index: 2;
}

.server-card-content {
    position: relative;
    z-index: 3;
    padding: 1.5rem;
    font-size: 1.05rem;
}

.server-card-content h4 {
    font-size: 1.5rem;
}

    .bg-palworld::before {
        background-image: url('https://kickback-kingdom.com/assets/media/games/1217.png');
    }

    .bg-conan::before {
        background-image: url('https://kickback-kingdom.com/assets/media/games/1223.png');
    }

    .bg-minecraft::before {
        background-image: url('https://kickback-kingdom.com/assets/media/games/1225.png');
    }

    .bg-arma::before {
        background-image: url('https://kickback-kingdom.com/assets/media/games/238.png');
    }

    .bg-barotrauma::before {
        background-image: url('https://kickback-kingdom.com/assets/media/games/1224.png');
    }

    code {
    background-color: rgba(255,255,255,0.15);
    color: #fff;
    padding: 2px 6px;
    border-radius: 4px;
}
</style>


<!-- Server Info Showcase -->
<div class="card shadow-lg mb-5">
    <div class="card-body p-5">
        <h2 class="fw-bold text-center mb-4 text-dark">Kingdom Realms – Server Info</h2>
        <p class="text-center mb-5 text-dark">
            Here are the gates to our worlds — each realm is ready for your arrival. Join us, explore, and make your mark.
        </p>

        <div class="row g-4">
            <!-- Palworld -->
            <div class="col-md-6 col-lg-6">
                <div class="server-card bg-palworld">
                    <div class="server-card-content p-4 text-center">
                        <h4 class="fw-bold mb-5">Palworld</h4>

                        <?php if (Session::IsLoggedIn()): ?>
                            <p class="mb-1"><strong>Server Name:</strong> Kickback Kingdom - Palworld</p>
                            <p class="mb-1"><strong>IP Address:</strong> <code>209.145.48.193</code></p>
                            <p class="mb-1"><strong>Port:</strong> <code>28000</code></p>
                        <?php else: ?>
                            <a href="/login.php?redirect=/welcome-alpha-team.php" class="btn btn-outline-light mt-3 px-4 py-2">
                                Connect to Server
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- Conan Exiles -->
            <div class="col-md-6 col-lg-6">
                <div class="server-card bg-conan">
                    <div class="server-card-content p-4 text-center">
                        <h4 class="fw-bold mb-5">Conan Exiles</h4>
                        <?php if (Session::IsLoggedIn()): ?>
                            <p class="mb-1"><strong>Server Name:</strong> Kickback Exiles</p>
                            <p class="mb-1"><strong>IP Address:</strong> <code>144.126.153.200</code></p>
                            <p class="mb-1"><strong>Port:</strong> <code>30000</code></p>
                        <?php else: ?>
                            <a href="/login.php?redirect=/welcome-alpha-team.php" class="btn btn-outline-light mt-3 px-4 py-2">
                                Connect to Server
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Minecraft -->
            <div class="col-md-6 col-lg-6">
                <div class="server-card bg-minecraft">
                    <div class="server-card-content p-4 text-center">
                        <h4 class="fw-bold mb-5">Minecraft Roleplay</h4>
                        <?php if (Session::IsLoggedIn()): ?>
                            <p class="mb-1"><strong>Server Name:</strong> Kickback Kingdom</p>
                            <p class="mb-1"><strong>IP Address:</strong> <code>144.126.153.68</code></p>
                            <p class="mb-1"><strong>Port:</strong> <code>26945</code></p>
                        <?php else: ?>
                            <a href="/login.php?redirect=/welcome-alpha-team.php" class="btn btn-outline-light mt-3 px-4 py-2">
                                Connect to Server
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Arma 3 -->
            <div class="col-md-6 col-lg-6">
                <div class="server-card bg-arma">
                    <div class="server-card-content p-4 text-center">
                        <h4 class="fw-bold mb-5">Arma 3</h4>
                        <?php if (Session::IsLoggedIn()): ?>
                            <p class="mb-1"><strong>Server Name:</strong> Kickback Kingdom - Arma Arena</p>
                            <p class="mb-1"><strong>IP Address:</strong> <code>54.159.107.49</code></p>
                            <p class="mb-1"><strong>Port:</strong> <code>2302</code></p>
                        <?php else: ?>
                            <a href="/login.php?redirect=/welcome-alpha-team.php" class="btn btn-outline-light mt-3 px-4 py-2">
                                Connect to Server
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Barotrauma -->
            <div class="col-md-6 col-lg-6">
                <div class="server-card bg-barotrauma">
                    <div class="server-card-content p-4 text-center">
                        <h4 class="fw-bold mb-5">Barotrauma</h4>
                        <?php if (Session::IsLoggedIn()): ?>
                            <p class="mb-1"><strong>Server Name:</strong> KICKBACK KINGDOM - DEDICATED</p>
                            <p class="mb-1"><strong>IP Address:</strong> <code>ec2-54-159-107-49.compute-1.amazonaws.com</code></p>
                        <?php else: ?>
                            <a href="/login.php?redirect=/welcome-alpha-team.php" class="btn btn-outline-light mt-3 px-4 py-2">
                                Connect to Server
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a  href="https://discord.gg/NhTZwaWfqu" class="btn bg-ranked-1 btn-lg">
                Need Help Connecting? Join Our Discord
            </a>
        </div>
    </div>
</div>
<!-- End Server Info -->



                <!-- Why Kickback Kingdom? -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <h2 class="fw-bold text-center mb-4">Why Kickback Kingdom?</h2>

                        <p class="text-center">
                            Kickback Kingdom isn't just another gaming community — it's a living, breathing world built by players, for players.
                            We host cross-game events, design custom quests, and create systems that reward teamwork, creativity, and persistence.
                        </p>

                        <div class="row mt-4">
                            <div class="col-md-4 text-center">
                                <h4 class="fw-semibold">⚔️ Competitive & Cooperative</h4>
                                <p>
                                    From ranked matches to cooperative games, we love both friendly rivalry and shared adventure.
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="fw-semibold">🛠 Player-Driven Features</h4>
                                <p>
                                    Suggest features, build squads, host events — we build this world together. The tools are in your hands.
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="fw-semibold">🎁 Real Rewards & Recognition</h4>
                                <p>
                                    Earn in-game loot, profile badges, video games, or even real-life rewards through your contributions and victories.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How Alpha Team Makes It Better -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body p-5">
                        <h2 class="fw-bold text-center mb-4">How Alpha Team Makes It Even Better</h2>
                        <p class="text-center">
                            Your crew brings passion, experience, and tight-knit coordination — all things that make Kickback stronger.
                            Whether you're organizing events, dominating PvP, or just bringing the vibes, your presence levels us all up.
                        </p>

                        <p class="text-center">
                            We're here to give you a platform — a kingdom — to grow even bigger, louder, and more unstoppable together.
                        </p>
                    </div>
                </div>

                <!-- What You'll Get -->
                <div class="card shadow-sm mb-5">
                    <div class="card-body p-5">
                        <h2 class="fw-bold text-center mb-4">What's in It for You?</h2>

                        <ul class="list-group list-group-flush fs-5">
                            <li class="list-group-item">✅ Access to new servers and events</li>
                            <li class="list-group-item">✅ Earn rewards, badges and trophies!</li>
                            <li class="list-group-item">✅ A voice in community decisions and direction</li>
                            <li class="list-group-item">✅ The chance to grow your own legend within the Kingdom</li>
                        </ul>

                        <div class="text-center mt-4">
                            <a href="https://discord.gg/NhTZwaWfqu" class="btn bg-ranked-1 btn-lg">
                                Get Involved on Discord <i class="fa-brands fa-discord" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- End Extra Sections -->

            </div>

            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
</body>

</html>
