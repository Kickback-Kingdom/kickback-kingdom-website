<?php
declare(strict_types=1);

namespace Kickback\Frontend\Components;

use Kickback\Common\Str;

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

        if (!Str::empty($this->videoEmbed)) {
            $content .= "<div class='embed-responsive'>
                            <iframe src='{$this->videoEmbed}' allow='accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture' allowfullscreen></iframe>
                         </div>";
        } elseif (!Str::empty($this->videoMp4)) {
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

        if (!Str::empty($this->link)) {
            $caption .= "<a href='{$this->link}' class='bg-ranked-1 btn btn-sm' style='text-shadow: none;'>{$this->cta}</a>";
        }

        $caption .= "</div>";

        return "<div class='carousel-item {$activeClass}' data-bs-interval='{$this->interval}'>{$content}{$caption}</div>";
    }
}

?>
