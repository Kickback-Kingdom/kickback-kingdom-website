<?php
use Kickback\Frontend\Components\AdCarousel;

if (!isset($_GET['borderless'])) {
    $carousel = new AdCarousel();
    echo $carousel->render();
}

?>