<?php
declare(strict_types=1);

namespace Kickback\Frontend\Components;

use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\TreasureHuntController;
use Kickback\Backend\Views\vTreasureHuntEvent;

class CarouselAd
{
    public string $imageDesktop;
    public string $imageMobile;
    public string $title;
    public string $description;
    public ?string $link;
    public ?string $videoEmbed;
    public ?string $videoMp4;
    public bool $isActive;
    public int $interval;
    public string $cta;

    public function __construct(
        string $imageDesktop,
        string $imageMobile,
        string $title,
        string $description,
        ?string $link = null,
        ?string $videoEmbed = null,
        ?string $videoMp4 = null,
        int $interval = 7000,
        string $cta = "Learn More"
    ) {
        $this->imageDesktop = $imageDesktop;
        $this->imageMobile = $imageMobile;
        $this->title = $title;
        $this->description = $description;
        $this->link = $link;
        $this->videoEmbed = $videoEmbed;
        $this->videoMp4 = $videoMp4;
        $this->isActive = false;
        $this->interval = $interval;
        $this->cta = $cta;
    }

    public function render(): string
    {
        $activeClass = $this->isActive ? "active" : "";
        $content = '';

        if ($this->videoEmbed) {
            $content .= "<div class='embed-responsive'>
                            <iframe src='{$this->videoEmbed}' allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>
                         </div>";
        } elseif ($this->videoMp4) {
            $content .= "<div class='embed-responsive'>
                            <video autoplay muted loop playsinline style='width: 100%;'>
                                <source src='{$this->videoMp4}' type='video/mp4'>
                                Your browser does not support the video tag.
                            </video>
                         </div>";
        } else {
            $content .= "<img src='{$this->imageDesktop}' class='d-none d-md-block w-100' style='aspect-ratio: 96/25;'>
                         <img src='{$this->imageMobile}' class='d-block d-md-none w-100'  style='aspect-ratio: 54/25;'>";
        }

        $caption = "<div class='carousel-caption d-block d-md-block text-shadow'>
                        <h5>{$this->title}</h5>
                        <p>{$this->description}</p>";

        if ($this->link) {
            $caption .= "<a href='{$this->link}' class='bg-ranked-1 btn btn-sm' style='text-shadow: none;'>{$this->cta}</a>";
        }

        $caption .= "</div>";

        return "<div class='carousel-item {$activeClass}' data-bs-interval='{$this->interval}'>{$content}{$caption}</div>";
    }
}

class AdCarousel
{
    private array $ads = [];

    public function __construct()
    {
        $this->populateAds();
    }

    private function populateAds(): void
    {
        $currentPage = basename($_SERVER['SCRIPT_NAME']);
        $this->ads = [];

        $pageAds = [
            "adventurers-guild.php" => new CarouselAd(
                "/assets/media/context/Kickback_Banners_Adventure_Guild_1920-500_01.png",
                "/assets/media/context/Kickback_Banners_Adventure_Guild_1080-500_01.png",
                "Welcome to the Adventurers' Guild",
                "Where every quest counts and legends are born!"
            ),
            "business-plan.php" => new CarouselAd(
                "/assets/media/context/Kickback_BannersBusiness_Plan_1920-500_01.png",
                "/assets/media/context/Kickback_BannersBusiness_Plan_1080-500.png",
                "Kickback Kingdom's Business Plan",
                "Strategizing our future from vision to victory!"
            ),
            "project-roadmaps.php" => new CarouselAd(
                "/assets/media/context/Kickback_Banners_Roadmap_1920-500.png",
                "/assets/media/context/Kickback_Banners_Roadmap_1080-500.png",
                "The Royal Roadmap",
                "Achieving our goals one step at a time!"
            ),
            "merchants-guild.php" => new CarouselAd(
                "/assets/media/context/Kickback_Banners_Merchant_Guild_1920-500_01.png",
                "/assets/media/context/Kickback_Banners_Merchant_Guild_1080-500_01.png",
                "Welcome to the Merchants' Guild",
                "Empower the Kingdom and Prosper Together"
            ),
            "craftsmens-guild.php" => new CarouselAd(
                "/assets/media/context/Kickback_BannersCraftsmen_Guild_1920-500_02.png",
                "/assets/media/context/Kickback_BannersCraftsmen_Guild_1080-500_02.png",
                "Welcome to the Craftsmen's Guild",
                "Take part in building the Kingdom one piece at a time!"
            ),
            "apprentices-guild.php" => new CarouselAd(
                "/assets/media/context/Kickback_Banners_Apprentice_Guild_1920-500_03.png",
                "/assets/media/context/Kickback_Banners_Apprentice_Guild_1080-500_03.png",
                "Welcome to the Apprentices' Guild",
                "Unleashing the potential of limitless minds"
            ),
            "stewards-guild.php" => new CarouselAd(
                "/assets/media/context/Kickback_Banners_Stewards_Guild_1920-500_01.png",
                "/assets/media/context/Kickback_Banners_Stewards_Guild_1080-500_01.png",
                "Welcome to the Stewards' Guild",
                "Shaping the Kingdom through service and dedication"
            ),
        ];

        // Only load the ad for the current page
        if (isset($pageAds[$currentPage])) {
            array_push($this->ads, $pageAds[$currentPage]);
        }

        $treasureHuntResp = TreasureHuntController::getCurrentEventsAndUpcoming();
        if ($treasureHuntResp->success) {
            $treasureHunts = $treasureHuntResp->data; // Assuming this is an array
            foreach ($treasureHunts as $treasureHunt) {
                array_push($this->ads, new CarouselAd(
                    $treasureHunt->banner->getFullPath(),
                    $treasureHunt->bannerMobile->getFullPath(),
                    $treasureHunt->name,
                    "",
                    $treasureHunt->getURL(),
                    null,
                    null,
                    7000,
                    "View Hunt"
                ));
            }
        }


        $kickbackQuestResp = QuestController::getQuestByKickbackUpcoming();
        if ($kickbackQuestResp->success) {
            array_push($this->ads, new CarouselAd(
                $kickbackQuestResp->data->banner->getFullPath(),
                $kickbackQuestResp->data->bannerMobile->getFullPath(),
                $kickbackQuestResp->data->title,
                "",
                $kickbackQuestResp->data->getURL(),
                null,
                null,
                7000,
                "View Quest"
            ));
        }


        // Push missing ads into the array dynamically
        array_push($this->ads, new CarouselAd(
                "/assets/images/lich-banner.jpg",
                "/assets/images/lich-banner-mobile.jpg",
                "L.I.C.H.",
                "A dark force awakens and seeks to add you to its realm.",
                "/lich",
                null,
                "/assets/media/videos/lich3.mp4",
                45000,
                "Enter the Realm"),
            /*new CarouselAd(
                "",
                "",
                "Twilight Racer",
                "Space is your canvas, the car is your brush, speed is your art.",
                null,
                "https://www.youtube.com/embed/kywr54C369w?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=kywr54C369w&start=12",
                null,
                45000),
            new CarouselAd(
                "",
                "",
                "Twilight Racer",
                "Space is your canvas, the car is your brush, speed is your art.",
                null,
                "https://www.youtube.com/embed/W4ltkMt2njM?modestbranding=1&showinfo=0&rel=0&autoplay=1&mute=1&controls=0&loop=1&playlist=W4ltkMt2njM",
                null,
                30000)*/
        );

        if (empty($this->ads)) {

            $this->addDefaultAd();
        }
        $this->ads[0]->isActive = true;
    }

    private function addDefaultAd(): void
    {
        
        array_push($this->ads, new CarouselAd(
            "/assets/images/kk-1.jpg",
            "/assets/images/kk-2.jpg",
            "Welcome to Kickback Kingdom",
            "The gaming realm where friendships are formed and scores are settled."
        ));
    }

    public function render(): string
    {
        $indicators = "";
        $slides = "";
        $i = 0;

        foreach ($this->ads as $ad) {
            $indicators .= "<button type='button' data-bs-target='#topCarouselAd' data-bs-slide-to='{$i}' class='" . ($i === 0 ? "active" : "") . "' aria-label='Slide " . ($i + 1) . "'></button>";
            $slides .= $ad->render();
            $i++;
        }

        return "
        <div id='topCarouselAd' class='carousel slide' style='margin-top: 56px;' data-bs-ride='carousel'>
            <div class='carousel-indicators'>{$indicators}</div>
            <div class='carousel-inner'>{$slides}</div>
            <button class='carousel-control-prev' type='button' data-bs-target='#topCarouselAd' data-bs-slide='prev'>
                <span class='carousel-control-prev-icon' aria-hidden='true'></span>
                <span class='visually-hidden'>Previous</span>
            </button>
            <button class='carousel-control-next' type='button' data-bs-target='#topCarouselAd' data-bs-slide='next'>
                <span class='carousel-control-next-icon' aria-hidden='true'></span>
                <span class='visually-hidden'>Next</span>
            </button>
        </div>";
    }
}
?>
