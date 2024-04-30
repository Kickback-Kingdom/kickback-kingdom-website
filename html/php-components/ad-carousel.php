<!--TOP AD-->
<?php
//echo "<!--".basename($_SERVER['SCRIPT_NAME'])."-->";

$currentPage = basename($_SERVER['SCRIPT_NAME']);
$adCarouselActivePage = "active";
$hadFirstPage = false;

if (!isset($_GET['borderless']))
{
    

?>

<div id="topCarouselAd" class="carousel slide" style="margin-top: 56px;"  data-bs-ride="carousel">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#topCarouselAd" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#topCarouselAd" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#topCarouselAd" data-bs-slide-to="2" aria-label="Slide 3"></button>
        <!--<button type="button" data-bs-target="#topCarouselAd" data-bs-slide-to="3" aria-label="Slide 4"></button>-->
    </div>
    <div class="carousel-inner">
        <?php if ($currentPage == "adventurers-guild.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_Banners_Adventure_Guild_1920-500_01.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_Banners_Adventure_Guild_1080-500_01.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Welcome to the Adventurers' Guild</h3>
                <p>where every quest counts and legends are born!</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "business-plan.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_BannersBusiness_Plan_1920-500_01.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_BannersBusiness_Plan_1080-500.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Kickback Kingdom's Business Plan</h3>
                <p>strategizing our future from vision to victory!</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "project-roadmaps.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_Banners_Roadmap_1920-500.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_Banners_Roadmap_1080-500.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>The Royal Roadmap</h3>
                <p>achieving our goals one step at a time!</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "merchants-guild.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_Banners_Merchant_Guild_1920-500_01.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_Banners_Merchant_Guild_1080-500_01.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Welcome to the Merchants' Guild</h3>
                <p>Empower the Kingdom and Prosper Together</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "craftsmens-guild.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_BannersCraftsmen_Guild_1920-500_02.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_BannersCraftsmen_Guild_1080-500_02.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Welcome to the Craftsmen's Guild</h3>
                <p>Take part in building the Kingdom one piece at a time!</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "apprentices-guild.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_Banners_Apprentice_Guild_1920-500_03.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_Banners_Apprentice_Guild_1080-500_03.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Welcome to the Apprentices' Guild</h3>
                <p>Unleashing the potential of limitless minds</p>
            </div>
        </div>
        <?php } ?>
        <?php if ($currentPage == "stewards-guild.php") { $hadFirstPage = true; ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/media/context/Kickback_Banners_Stewards_Guild_1920-500_01.png" class="d-none d-md-block w-100">
            <img src="/assets/media/context/Kickback_Banners_Stewards_Guild_1080-500_01.png" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow carousel-caption-top">
                <h3>Welcome to the Stewards' Guild</h3>
                <p>Shaping the Kingdom through service and dedication</p>
            </div>
        </div>
        <?php } ?>
        <?php if (!$hadFirstPage) { ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="7000">
            <img src="/assets/images/kk-1.jpg" class="d-none d-md-block w-100">
            <img src="/assets/images/kk-2.jpg" class="d-block d-md-none w-100">
            <div class="carousel-caption d-block d-md-block text-shadow">
                <h5>Welcome to Kickback Kingdom</h5>
                <p>The gaming realm where friendships are formed and scores are settled.</p>
            </div>
        </div>
        <?php } ?>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="45000">
            <div class="embed-responsive">
                <iframe src="https://www.youtube.com/embed/kywr54C369w?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=kywr54C369w&start=12" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="carousel-caption d-block d-md-block text-shadow">
                <h5>Twilight Racer</h5>
                <p>Space is your canvas, the car is your brush, speed is your art.</p>
            </div>
        </div>
        <div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="30000">
            <div class="embed-responsive">
                <iframe src="https://www.youtube.com/embed/W4ltkMt2njM?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=W4ltkMt2njM" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="carousel-caption d-block d-md-block text-shadow">
                <h5>Twilight Racer</h5>
                <p>Space is your canvas, the car is your brush, speed is your art.</p>
            </div>
        </div>
        <!--<div class="carousel-item <?php echo $adCarouselActivePage; $adCarouselActivePage = ""; ?>" data-bs-interval="30000">
            <div class="embed-responsive">
                <iframe src="https://www.youtube.com/embed/CuWYMe6eYaE?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=CuWYMe6eYaE" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="carousel-caption d-block d-md-block text-shadow">
                <h5>End of Empires</h5>
                <p> Conquer the ages, build your empire, rewrite history</p>
            </div>
        </div>-->
        <!--<div class="carousel-item" data-bs-interval="30000">
            <div class="embed-responsive">
                <iframe src="https://www.youtube.com/embed/BCr7y4SLhck?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=BCr7y4SLhck" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <div class="carousel-caption d-block d-md-block">
                <h5>L.I.C.H</h5>https://www.youtube.com/watch?v=kywr54C369w
                <p>Face the dark magic, hone your hunter's instinct, slay the Lich</p>
            </div>
        </div>-->
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#topCarouselAd" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#topCarouselAd" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<?php } ?>