<?php
declare(strict_types=1);

namespace Kickback\Controllers;


class FlavorTextController
{

    public static function getRaffleWinnerAnnouncement(string $raffleName,string $winnerUsername) {
        return "🎉 Exciting Announcement! 🎉 The $raffleName has come to a thrilling conclusion. Congratulations to $winnerUsername, the lucky winner! We thank everyone who participated and encourage you to stay tuned for more exciting events and opportunities in the future.";
    }
}
?>