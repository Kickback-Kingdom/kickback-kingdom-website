<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Models\Response;
use Kickback\Services\Database;
use Kickback\Views\vRecordId;
use Kickback\Views\vQuote;
use Kickback\Views\vMedia;

class QuoteController
{
    public static function getRandomQuote() : vQuote {

        $quotes = [

            // Alexander The Great
            [
                "text" => "There is nothing impossible to him who will try.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "Let us conduct ourselves so that all men wish to be our friends and all fear to be our enemies.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "With the right attitude, self imposed limitations vanish",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "There is something noble in hearing myself ill spoken of, when I am doing well.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "I will not steal a victory. The end and perfection of our victories is to avoid the vices and infirmities of those whom we subdue.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],

            // Karl Marx
            [
                "text" => "The full man does not understand the wants of the hungry.",
                "image" => "quotes/people/120.png",
                "author" => "Karl Marx",
                "date" => "1800s AD"
            ],
            [
                "text" => "Follow your own path, no matter what people say.",
                "image" => "quotes/people/120.png",
                "author" => "Karl Marx",
                "date" => "1800s AD"
            ],

            // Genghis Khan        
            [
                "text" => "A leader can never be happy until his people are happy.",
                "image" => "quotes/people/121.png",
                "author" => "Genghis Khan",
                "date" => "1200s AD"
            ],  
            [
                "text" => "Even when a friend does something you do not like, he continues to be your friend.",
                "image" => "quotes/people/121.png",
                "author" => "Genghis Khan",
                "date" => "1200s AD"
            ],
            
            // Mingo Bomb
            [
                "text" => "Halo is an RPG",
                "image" => "quotes/people/122.png",
                "author" => "Eric Kiss",
                "date" => "2020s AD",
                "accountId" => 2
            ],

            //Alibaba
            [
                "text" => "Anything can be solved with a little patience and understading",
                "image" => "quotes/people/122.png",
                "author" => "Alexander Atlas",
                "date" => "2020s AD",
                "accountId" => 1
            ],

            // Socrates
            [
                "text" => "The best seasoning for food is hunger.",
                "image" => "quotes/people/123.png",
                "author" => "Socrates",
                "date" => "420 BC"
            ],

            // Albert Einstein
            [
                "text" => "A person who never made a mistake never tried anything new.",
                "image" => "quotes/people/124.png",
                "author" => "Albert Einstein",
                "date" => "1900s AD"
            ],
            [
                "text" => "We can't solve today's problems with the mentality that created them.",
                "image" => "quotes/people/124.png",
                "author" => "Albert Einstein",
                "date" => "1900s AD"
            ],
            [
                "text" => "Weak people revenge. Strong people forgive. Intelligent People Ignore.",
                "image" => "quotes/people/124.png",
                "author" => "Albert Einstein",
                "date" => "1900s AD"
            ],


            // Julius Caesar
            [
                "text" => "No music is so charming to my ear as the requests of my friends, and the supplications of those in want of my assistance.",
                "image" => "quotes/people/125.png",
                "author" => "Julius Caeser",
                "date" => "55 BC"
            ],

            // Carl Sagan
            [
                "text" => "Somewhere, something incredible is waiting to be known.",
                "image" => "quotes/people/126.png",
                "author" => "Carl Sagan",
                "date" => "1980s AD"
            ],

            // Hammurabi
            [
                "text" => "The first duty of government is to protect the powerless from the powerful.",
                "image" => "avatar/297.png",
                "author" => "Hammurabi",
                "date" => "1772 BC"
            ],
        ];
        


        $randomIndex = array_rand($quotes);
        $quoteData = $quotes[$randomIndex];

        $quote = new vQuote();
        $quote->text = $quoteData["text"];
        $quote->author = $quoteData["author"];
        $quote->date = $quoteData["date"];

        $media = new vMedia();
        $media->mediaPath = $quoteData["image"];
        $quote->icon = $media;
        return $quote;
    }
}
?>
