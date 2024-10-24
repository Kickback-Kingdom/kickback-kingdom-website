<?php
declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use Kickback\Backend\Models\Response;
use Kickback\Services\Database;
use Kickback\Backend\Views\vRecordId;
use Kickback\Backend\Views\vQuote;
use Kickback\Backend\Views\vMedia;

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
            [
                "text" => "I had rather excel others in the knowledge of what is excellent, than in the extent of my power and dominion.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "Through every generation of the human race there has been a constant war, a war with fear. Those who have the courage to conquer it are made free and those who are conquered by it are made to suffer until they have the courage to defeat it, or death takes them.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "Whatever possession we gain by our sword cannot be sure or lasting, but the love gained by kindness and moderation is certain and durable.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "Remember, upon the conduct of each depends the fate of all. In the end, when it's over, all that matters is what you've done.",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "How should a man be capable of grooming his own horse, or of furbishing his own spear and helmet, if he allows himself to become unaccustomed to tending even his own person, which is his most treasured belonging?",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "For my own part, I would rather excel in the knowledge of the highest secrets of philosophy than in arms. Who does not desire such a victory by which we shall join places in our kingdom so far divided by nature and for which we shall set up trophies in another conquered world?",
                "image" => "quotes/people/119.png",
                "author" => "Alexander The Great",
                "date" => "334 BC"
            ],
            [
                "text" => "No man can point to my riches, only the things I hold in trust for you all.",
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
                "text" => "Anything can be solved with a little patience and understanding",
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
                "text" => "Then Anu and Bel called by name me, Hammurabi, the exalted prince, who feared God, to bring about the rule of righteousness in the land, to destroy the wicked and the evil-doers; so that the strong should not harm the weak.",
                "image" => "avatar/297.png",
                "author" => "Hammurabi",
                "date" => "1772 BC"
            ],

            //Steve Jobs
            [
                "text" => "Ideas are worth nothing unless executed. They are just a multiplier. Execution is worth millions.",
                "image" => "quotes/people/434.png",
                "author" => "Steve Jobs",
                "date" => "~2000s AD"
            ],

            //Plato quotes/people/435.png
            [
                "text" => "The beginning is the most important part of the work.",
                "image" => "quotes/people/434.png",
                "author" => "Plato",
                "date" => "~340 BC"
            ],
            [
                "text" => "The one who learns and learns and doesn't practice is like the one who plows and plows and never plants.",
                "image" => "quotes/people/434.png",
                "author" => "Plato",
                "date" => "~340 BC"
            ],
        ];
        


        $randomIndex = array_rand($quotes);
        //$randomIndex = count($quotes)-1;
        $quoteData = $quotes[$randomIndex];

        $quote = new vQuote();
        $quote->text = $quoteData["text"];
        $quote->author = $quoteData["author"];
        $quote->date = $quoteData["date"];

        $media = new vMedia();
        $media->setMediaPath($quoteData["image"]);
        $quote->icon = $media;
        return $quote;
    }
}
?>
