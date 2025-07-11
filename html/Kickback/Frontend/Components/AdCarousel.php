<?php
declare(strict_types=1);

namespace Kickback\Frontend\Components;

use Kickback\Common\Primitives\Str;
use Kickback\Backend\Controllers\QuestController;
use Kickback\Backend\Controllers\TreasureHuntController;
use Kickback\Backend\Views\vTreasureHuntEvent;

class AdCarousel
{
    /** @var array<CarouselAd> */
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

        try
        {
            $treasureHunts = TreasureHuntController::queryCurrentEventsAndUpcoming();
            foreach ($treasureHunts as $treasureHunt)
            {
                if ( is_null($treasureHunt->banner)
                ||   is_null($treasureHunt->bannerMobile) ) {
                    continue;
                }

                array_push($this->ads, new CarouselAd(
                    $treasureHunt->banner->getFullPath(),
                    $treasureHunt->bannerMobile->getFullPath(),
                    $treasureHunt->name,
                    "",
                    $treasureHunt->url(),
                    null,
                    null,
                    7000,
                    "View Hunt"
                ));
            }
        }
        catch (\Exception $e) {;}


        try
        {
            $kickbackQuest = QuestController::queryQuestByKickbackUpcoming();
            if (!is_null($kickbackQuest->banner)
            &&  !is_null($kickbackQuest->bannerMobile) )
            {
                array_push($this->ads, new CarouselAd(
                    $kickbackQuest->banner->getFullPath(),
                    $kickbackQuest->bannerMobile->getFullPath(),
                    $kickbackQuest->title,
                    "",
                    $kickbackQuest->url(),
                    null,
                    null,
                    7000,
                    "View Quest"
                ));
            }
        }
        catch (\Exception $e) {;}

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

        // @phpstan-ignore smaller.alwaysTrue
        if (0 < count($this->ads)) {
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
