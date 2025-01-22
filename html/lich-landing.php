<?php
require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");
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
                
                
                $activePageName = "L.I.C.H.";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>

                <!-- HERO SECTION -->
                <section class="position-relative py-5 text-center text-light" style="overflow: hidden;">
                    <!-- Video Background -->
                    <video autoplay="" muted="" loop="" playsinline="" style="position: absolute;top: 0;left: 0;width: 100%;height: 100%;object-fit: cover;">
                        <source src="/assets/media/videos/lich5.mp4" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>

                    <!-- Content -->
                    <div class="container position-relative" style="z-index: 1;">
                        <h1 class="display-3 fw-bold mb-3" style="text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.7);">
                            Welcome to <span style="color: #ff4500;">L.I.C.H.</span>
                        </h1>
                        <p class="lead" style="font-size: 1.5rem;">
                            A card game of cunning strategy, fierce battles, and survival on a dynamic hex grid.
                        </p>
                        <div class="mt-4">
                            <a href="/quest-line/lich-arena" class="btn btn-primary btn-lg mx-2">Start Your Adventure</a>
                            <a href="/g/LICH" class="btn btn-outline-light btn-lg mx-2">View Rankings</a>
                        </div>
                    </div>
                </section>

                <!-- FEATURES SECTION -->
                <section class="container py-5">
                    <h2 class="text-center mb-5">The Lich awaits you...</h2>
                    <div class="row text-center">
                        <!-- Hero-Based Gameplay -->
                        <div class="col-md-4">
                            <img src="/assets/images/lich/hero.png" alt="Hero Icon" style="image-rendering: pixelated;">
                            <h3>Pick your Hero</h3>
                            <p>Choose your hero, each offering unique abilities and diverse strategies to master.</p>
                        </div>
                        
                        <!-- Deck-Building Mastery -->
                        <div class="col-md-4">
                            <img src="/assets/images/lich/build.png" alt="Deck Building" style="image-rendering: pixelated;">
                            <h3>Build a Deck</h3>
                            <p>Craft the perfect deck with synergy and explosive combos to outmaneuver your opponents.</p>
                        </div>

                        <!-- Arena-Style Battles -->
                        <div class="col-md-4">
                            <img src="/assets/images/lich/survive.png" alt="Arena Icon" style="image-rendering: pixelated;">
                            <h3>Survive the Arena</h3>
                            <p>Engage in epic 6-player battles on a dynamic hex-grid board, where every move matters.</p>
                        </div>
                    </div>
                </section>


                <!-- SHOWCASE SECTION FOR CARDS -->
                <section class="container py-5">
                    <h2 class="text-center mb-4">Unleash the Power of Your Cards</h2>
                    <p class="text-center mb-5">Craft unstoppable decks to conquer the battlefield. Discover a glimpse of the arsenal at your fingertips:</p>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <img src="\assets\media\lich\cards\The-Awakening\221.png" alt="Card 1" style="width: 100%; height: auto;">
                            <h5 class="mt-2">Shadow Hunter</h5>
                        </div>
                        <div class="col-md-3">
                            <img src="\assets\media\lich\cards\The-Awakening\536.png" alt="Card 2" style="width: 100%; height: auto;">
                            <h5 class="mt-2">Flame Summoner</h5>
                        </div>
                        <div class="col-md-3">
                            <img src="\assets\media\lich\cards\The-Awakening\561.png" alt="Card 3" style="width: 100%; height: auto;">
                            <h5 class="mt-2">Arcane Defender</h5>
                        </div>
                        <div class="col-md-3">
                            <img src="\assets\media\lich\cards\The-Awakening\555.png" alt="Card 4" style="width: 100%; height: auto;">
                            <h5 class="mt-2">Lich Commander</h5>
                        </div>
                    </div>
                    <div class="text-center mt-4 d-none">
                        <a href="/lich/card-search/" class="btn btn-primary btn-lg">View All Cards</a>
                    </div>
                </section>

                <!-- GAME BOARD ASSET SHOWCASE -->
                <section class="container py-5 bg-light">
                    <h2 class="text-center mb-4">Explore the Battlefield</h2>
                    <p class="text-center mb-5">Step onto the dynamic hex grid board where every move shapes your destiny. Sharpen your strategy and claim victory:</p>
                    <div class="text-center">
                        <img src="/assets/images/lich-table.png" alt="Hex Grid Board" style="max-width: 100%; height: auto; border-radius: 10px;">
                    </div>
                </section>

                <!-- TESTIMONIALS SECTION -->
                <section class="bg-light py-5">
                    <div class="container">
                        <h2 class="text-center mb-4">What Players Are Saying</h2>
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <blockquote class="blockquote">
                                    <p>"Lich's creative card game mechanics combined with an active board state scratched an itch for me I didn't even know was there!"</p>
                                    <footer class="blockquote-footer"><a href="/u/devon%20:)" class="username">devon :)</a></footer>
                                </blockquote>
                            </div>
                            <div class="col-md-4 text-center">
                                <blockquote class="blockquote">
                                    <p>"L.I.C.H. is a game with a lot of potential and can be a lot of fun with friends."</p>
                                    <footer class="blockquote-footer"><a href="/u/Kotojo" class="username">Kotojo</a></footer>
                                </blockquote>
                            </div>
                            <div class="col-md-4 text-center">
                                <blockquote class="blockquote">
                                    <p>"The synergy between your cards and abilities is endlessly satisfying, and the dynamic board interactivity keeps every match fresh and engaging."</p>
                                    <footer class="blockquote-footer"><a href="/u/LandoTheBarbarian" class="username">LandoTheBarbarian</a></footer>
                                </blockquote>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- CALL-TO-ACTION SECTION -->
                <section class="text-center bg-primary text-light py-5">
                    <h2>Think you can survive the Arena?</h2>
                    <p class="lead">Forge your destiny and take on the ultimate challenge.</p>
                    <a href="/quest-line/lich-arena" class="btn btn-light btn-lg">Start Playing</a>
                </section>

            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>

</body>

</html>
