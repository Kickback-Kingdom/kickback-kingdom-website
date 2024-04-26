<?php


function BlockGET()
{

    if (IsGET())
    {
        exit("API is working, however this is endpoint does not work with GET.");
    }
}
 
function OnlyPOST()
{

    if (!IsPOST())
    {
        exit("API is working, however this is a POST only endpoint.");
    }
}

function OnlyGET()
{

    if (!IsGET())
    {
        exit("API is working, however this is a GET only endpoint");
    }
}

function IsPOST()
{
    return ($_SERVER["REQUEST_METHOD"] === "POST");
}

function IsGET()
{
    return ($_SERVER["REQUEST_METHOD"] === "GET");
}

function StringIsValid($var, $minLength) {
    return !is_null($var) && strlen($var) >= $minLength;
}


function ContainsData($data, $name)
{
    if (!isset($data) || empty($data))
    {
        return new APIResponse(false, "'$name' contains no data",null);
    }
    else{
        return new APIResponse(true, "All data is present",null);
    }
}
 
function POSTContainsFields(...$fields)
{
    foreach($fields as $field){
        if (!isset($_POST[$field]))
        {
            //$resp = new APIResponse(false, "Request body is not formated correctly. Missing data '$field'",null);
            //exit();
            return new APIResponse(false, "Request body is not formated correctly. Missing data '$field'",null);
        }
    }

    return new APIResponse(true, "Request body is formatted correctly",null);
}

function SESSIONContainsFields(...$fields)
{
    foreach($fields as $field){
        if (!isset($_SESSION[$field]))
        {
            //$resp = new APIResponse(false, "Request body is not formated correctly. Missing data '$field'",null);
            //exit();
            return new APIResponse(false, "Session information not found. Missing data '$field'",null);
        }
    }

    return new APIResponse(true, "Session information was found",null);
}


function Validate($data)
{
    if (!isset($data))
    {
        return "";
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);

    return $data;
}

function GetSQLError()
{
    return $GLOBALS["conn"]->error;
}

function LocatorIsValidString($str) {
    // The regex checks for a string made up of only letters (a-z, A-Z), numbers (0-9), underscores, or hyphens.
    return preg_match('/^[a-zA-Z0-9_-]+$/', $str) === 1;
}

function GetRandomGreeting()
{
    $royalGreetingsPlural = array(
        "Your Majesties",
        "Your Highnesses",
        "Your Graces",
        "My Lieges",
        "My Lords"
    );
    
    // Get a random index
    $randomIndex = array_rand($royalGreetingsPlural);
    
    // Print the randomly selected greeting
    return $royalGreetingsPlural[$randomIndex];
}
class IDCrypt {
    private $key;
    private $method;

    public function __construct($key, $method = 'AES-256-CBC') {
        // Ensure the key length is suitable for AES-256-CBC
        $this->key = substr(hash('sha256', $key, true), 0, 32);
        $this->method = $method;
    }

    public function encrypt($id) {
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($id, $this->method, $this->key, 0, $iv);

        // Check for successful encryption
        if ($ciphertext === false) {
            return null;
        }

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt($input) {
        $data = base64_decode($input);
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($data, 0, $ivlen);
        $ciphertext = substr($data, $ivlen);

        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, 0, $iv);

        // Check for successful decryption
        if ($plaintext === false) {
            return null;
        }

        return $plaintext;
    }
}

/*
class IDCrypt {
    private $key;
    private $method;

    public function __construct($key, $method = 'AES-128-CTR') {
        $this->key = hash('sha256', $key, true);
        $this->method = $method;
    }

    public function encrypt($id) {
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($id, $this->method, $this->key, 0, $iv);
        return base64_encode($iv.$ciphertext);
    }

    public function decrypt($input) {
        $c = base64_decode($input);
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($c, 0, $ivlen);
        $ciphertext = substr($c, $ivlen);
        return openssl_decrypt($ciphertext, $this->method, $this->key, 0, $iv);
    }
}*/

function encode_id($id) {
    return rtrim(strtr(base64_encode($id), '+/', '-_'), '=');
}

function decode_id($str) {
    return base64_decode(str_pad(strtr($str, '-_', '+/'), strlen($str) % 4, '=', STR_PAD_RIGHT));
}

function timeElapsedString($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function free_mysqli_resources($mysqli) {
    while ($mysqli->more_results() && $mysqli->next_result()) {
        $dummyResult = $mysqli->use_result();
        if ($dummyResult instanceof mysqli_result) {
            $dummyResult->free();
        }
    }
}

function getRandomQuote() {

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
    ];
    


    $randomIndex = array_rand($quotes);
    return $quotes[$randomIndex];
}

function GenerateFormToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['form_token'])) {
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
    }
}

function UseFormToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_POST['form_token']) && isset($_SESSION['form_token']) && $_SESSION['form_token'] === $_POST['form_token']) {
        // Regenerate a new token
        $_SESSION['form_token'] = bin2hex(random_bytes(32));
        return new APIResponse(true, "Token valid.", null);
    } else {
        return new APIResponse(false, "Invalid or expired form submission token.", null);
    }
}

function GetNewcomerIntroduction($username) {
    $introductions = [
        "Esteemed members of the royal court, I am honored to present $username, a distinguished guest from distant lands, who comes bearing tales of far-off cultures and seeks the gracious hospitality of Kickback Kingdom.",
        "Your Majesties and Royal Highnesses, before you stands $username, a traveler of great wisdom, eager to share the richness of their heritage with the vibrant community of Kickback Kingdom.",
        "Noble rulers of our land, I bring to your esteemed presence $username, an individual of unique lineage from across the seas, aspiring to integrate into the illustrious fabric of Kickback Kingdom.",
        "Lords and Ladies of the realm, permit me to introduce $username, a sojourner whose journey from exotic shores has led them to seek solace and prosperity under the benevolent rule of Kickback Kingdom.",
        "Your Majesties, I have the distinct honor of presenting $username, an adventurer from realms unknown, drawn to the peace and unity that our beloved Kickback Kingdom is renowned for.",
        "Esteemed Sovereigns, I am delighted to introduce $username, a person of remarkable character, who has traversed vast distances to experience the wisdom and culture of Kickback Kingdom.",
        "Illustrious members of the royal family, before you is $username, a seeker of knowledge and harmony, who has chosen Kickback Kingdom as their new sanctuary.",
        "Respected Monarchs, I present to you $username, a voyager of noble intent, who has been captivated by the legacy and allure of Kickback Kingdom, and now wishes to contribute to its storied history.",
        "Distinguished rulers, it is my privilege to introduce $username, a traveler of diverse experiences, eager to embrace the traditions and opportunities of the prosperous Kickback Kingdom.",
        "Gracious members of the royal court, I have the pleasure of presenting $username, an esteemed guest from foreign lands, who seeks to weave their own story into the rich tapestry of Kickback Kingdom.",
        "Honorable leaders, I am thrilled to announce the arrival of $username, a visionary from across the world, seeking to join and enrich the cultural mosaic of Kickback Kingdom.",
        "Your Excellencies, I am proud to present $username, a person of great intellect and wisdom, who has come to learn from and contribute to the collective knowledge of Kickback Kingdom.",
        "Esteemed assembly of nobility, I bring before you $username, a beacon of hope and friendship from distant territories, eager to find a new home in the warmth of Kickback Kingdom.",
        "Distinguished members of our royal lineage, allow me to introduce $username, a herald of peace and collaboration from lands afar, seeking to become part of our cherished Kickback Kingdom.",
        "Your Highnesses, it is with great joy that I present $username, a seeker of truth and beauty, who has been drawn to the artistic and cultural renaissance flourishing in Kickback Kingdom.",
        "Noble custodians of our heritage, I am pleased to introduce $username, an ambassador of goodwill, who has come to experience and add to the legendary hospitality of Kickback Kingdom.",
        "Esteemed royalty, I have the honor of presenting $username, a scholar of ancient traditions, drawn to the historical richness and enduring legacy of Kickback Kingdom.",
        "Your Imperial Majesties, I present $username, a seeker of enlightenment and unity, inspired by the tales of harmony and prosperity in our beloved Kickback Kingdom.",
        "Royal dignitaries, I am delighted to introduce $username, a connoisseur of fine arts and culture, eager to explore and contribute to the vibrant cultural scene of Kickback Kingdom.",
        "Guardians of our realm, it is with great respect that I present $username, a visionary leader from distant lands, aspiring to collaborate with the wise and just rulers of Kickback Kingdom."
    ];

    $randomIndex = array_rand($introductions);
    return $introductions[$randomIndex];
}
function Redirect($localPath) {
    $basePath = rtrim($GLOBALS["urlPrefixBeta"], '/');
    header("Location: ".$basePath."/".ltrim($localPath, '/'), true, 302);
    exit;
}

function StringStartsWith($str, $startsWith) {
    return (strpos(strtolower($str), strtolower($startsWith)) === 0);
}

function GetRandomIntFromSeedString($seedString, $maxInt)
{
    // Generate a hash from the seed string
    $hash = md5($seedString);
    // Convert the hash into an integer via base conversion
    $hashToInt = base_convert(substr($hash, 0, 8), 16, 10);
    // Use modulo to ensure the integer falls within the desired range
    return $hashToInt % $maxInt;
}

function GetParticipationFlavorText($username, $questName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username.$questName;
    }
    // Assuming GetRandomIntFromSeedString's $maxInt is the count of $flavorTexts
    $flavorTexts = [
        "$username bravely steps into the dark, ready to face whatever challenges $questName holds.",
        "With a determined look, $username prepares their gear for the adventures that await in $questName.",
        "$username looks at the sky, takes a deep breath, and strides confidently toward $questName.",
        "Rumors of $username's courage have spread far and wide, making even the shadows of $questName quiver.",
        "As $username joins the quest, $questName seems to welcome a new hero into its lore.",
        "The air around $username crackles with anticipation as they embark on the legendary $questName.",
        "$username laughs in the face of danger, their spirit unbreakable as they dive into $questName.",
        "Legends will tell of the day $username stepped forth to challenge the mysteries of $questName.",
        "$username's eyes gleam with the promise of victory, ready to conquer the trials of $questName.",
        "Nothing can deter $username from their path, their resolve as firm as the ground beneath them, as they enter $questName.",
        "Armed with hope and steel, $username sets out to carve their name into the heart of $questName.",
        "$username's shadow stretches long as they stand before $questName, a testament to the looming adventure.",
        "With the winds of destiny at their back, $username approaches $questName, ready for the saga that awaits.",
        "The tales of yore pale in comparison to the epic awaiting $username in the depths of $questName.",
        "Today, $questName; tomorrow, legend. $username's journey begins with a single, determined step.",
        "Like a stone against the tide, $username stands resolute before the gates of $questName, unyielding.",
        "Echoes of the past whisper $username's name as they approach $questName, ready to forge their destiny.",
        "The path to $questName is fraught with peril, but $username walks it with a song in their heart and steel in their hand.",
        "$username gazes upon $questName, their resolve shining brighter than the sun. This is where legends are born.",
        "In the hush before the storm, $username and $questName stand, ready to dance to the tune of fate.",
        
        "Under a cloak of twilight, $username embarks on the journey into $questName, where legends awake.",
        "With a heart full of courage and a quiver full of arrows, $username steps into the saga of $questName.",
        "$username's boots leave prints on the sands of fate as they approach the gates of $questName.",
        "In the quiet before the clash, $username stands ready, the destiny of $questName but a heartbeat away.",
        "The echo of $username's vow to conquer $questName reverberates through the halls of time.",
        "Bound by honor, driven by valor, $username sets their sights on the unfathomable depths of $questName.",
        "The stars align as $username draws closer to $questName, their spirit as luminous as the constellations.",
        "$username's laughter rings out, a sound of defiance in the face of $questName's looming shadows.",
        "As dawn breaks, $username's journey to $questName begins, a new chapter in an age-old tale.",
        "The lore of $questName whispers on the wind, calling $username to claim their place in history.",
        "With every step towards $questName, $username's legend grows, destined to echo through the ages.",
        "Against the backdrop of $questName, $username stands a solitary figure, poised to become myth.",
        "Fate whispers to $username of the glory and trials that await in $questName, and they answer with a roar.",
        "The challenge of $questName looms large, but $username's resolve is ironclad, their purpose clear.",
        "$username's journey to $questName is a beacon for all who dare to dream of greatness.",
        "Amidst the whispers of foes and the cheers of allies, $username's path to $questName is illuminated by courage.",
        "The annals of history will speak of $username and $questName, a tale of courage against the impossible.",
        "As $username faces the maw of $questName, they know that this is where heroes are made.",
        "The thrill of the unknown propels $username forward, into the heart of $questName, ready to etch their story in stone.",
        "Before $username, $questName stretches vast and mysterious, a canvas for their epic tale of valor.",
    
        "Amidst a chorus of destiny, $username takes the first step into $questName, where every shadow tells a story.",
        "The whisper of adventure turns into a roar as $username confronts the heart of $questName, ready to make history.",
        "With a steady hand and a will of iron, $username charts a course into the belly of $questName, where fate awaits.",
        "The legend of $questName calls out, and $username answers with a bold heart, stepping into the realm of the unknown.",
        "Foretold by the ancients, $username's journey into $questName is a dance with destiny, written in the stars.",
        "With every heartbeat, $username draws closer to the core of $questName, where dreams and reality collide.",
        "As the gates of $questName open, $username stands ready, a beacon of hope in the swirling mists of time.",
        "The saga of $questName unfolds, with $username at its center, a hero born from whispers and destined for greatness.",
        "In the silence of anticipation, $username's courage shines, casting light into the darkness of $questName.",
        "The tales of $questName grow with each step $username takes, a story of courage woven into the fabric of time.",
        "With a glance towards the heavens, $username embarks on $questName, where every star is a guide on the path to glory.",
        "The echo of $username's footsteps in $questName becomes a symphony of adventure, a melody of the bold.",
        "Bound to the heart of $questName by destiny, $username strides forward, a testament to the enduring spirit of heroes.",
        "In the face of the untold mysteries of $questName, $username stands undaunted, a force of nature itself.",
        "The chronicles of $questName will forever echo the name of $username, a hero who turned the tide of destiny.",
        "With the resolve of the ancients, $username ventures into $questName, a journey etched into the annals of time.",
        "The shadows of $questName beckon, and $username responds, a light against the darkness, a beacon of hope.",
        "As $questName looms large, $username rises to the challenge, a hero sculpted by the hands of fate.",
        "The whispers of $questName become a call to arms for $username, a summons to the adventure of a lifetime.",
        "In the labyrinth of $questName, $username walks unafraid, guided by the light of their unwavering courage.",
        
        "As the chapters of $questName unfold, $username pens their own legend, a tale written in courage and resolve.",
        "$username's march towards $questName is a testament to their indomitable spirit, leaving echoes of valor in their wake.",
        "With the dawn casting golden light, $username's silhouette against $questName speaks of impending tales yet to be told.",
        "In the symphony of the ancients, $username's deeds in $questName will be remembered as the crescendo, resonating through eternity.",
        "The sands of time shift beneath $username's feet as they step into $questName, ready to carve out destiny's heart.",
        "With a resolve as unbreakable as diamond, $username faces the trials of $questName, their spirit a beacon in the tumultuous night.",
        "As $username traverses the threshold into $questName, they stitch the fabric of legend with threads of their own valor.",
        "Beneath the banner of fate, $username charges into $questName, their name destined to be sung by bards of future ages.",
        "The oath of a hero is silent but strong, and $username's pledge echoes through the valleys of $questName, a promise of triumph.",
        "In the ledger of legends, $username's saga in $questName is inscribed with golden ink, a testament to their unwavering courage.",
        "Through the mists of $questName, $username emerges not just as a participant, but as the architect of their own epic.",
        "With every challenge in $questName surmounted, $username forges their legacy, as enduring as the stars that light the night sky.",
        "The mantle of heroism rests lightly on $username's shoulders as they navigate the intricacies of $questName, a beacon for all who follow.",
        "$username's journey through $questName is a tapestry of courage, woven with the threads of countless trials and triumphs.",
        "As $username stands before $questName, the air thrums with the power of untold stories, each waiting for their unwavering heart to unfold.",
        "In the heart of $questName, amidst trials and tribulations, $username finds their true strength, a beacon that outshines the darkest night.",
        "With a will as firm as the bedrock, $username delves into the depths of $questName, each step a stride towards immortality.",
        "The echoes of $username's valor in the halls of $questName will resonate for eternity, a chorus of heroism and heart.",
        "Amidst the storm of $questName, $username stands unshaken, a testament to the enduring power of hope and heroism.",
        "As $username embarks upon $questName, they weave their spirit into the fabric of the universe, a constellation of courage burning bright."
    ];
    
    
    

    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}

function GetBailedFlavorText($username, $questName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $questName;
    }
    
    $flavorTexts = [
        "As the sun sets, $username decides that $questName is a story for another day.",
        "$username took one look at what lay ahead and whispered, 'Not today, $questName. Not today.'",
        "With a mysterious smile, $username steps back from the threshold of $questName, choosing the path less traveled.",
        "$username pauses, considers, and with a sigh, turns away from $questName. 'Maybe next time,' they mutter.",
        "Legend has it $username got lost on the way to $questName. Or was it a tactical retreat?",
        "Rumor spreads that $username was last seen heading in the opposite direction of $questName, citing an urgent appointment with destiny elsewhere.",
        "$username's courage is undisputed, but even heroes have their limits. $questName will have to wait.",
        "In a surprising twist, $username decides that $questName is better left to the tales of old.",
        "They say wisdom is knowing when to retreat. By that measure, $username is the wisest of us all as they reconsider $questName.",
        "$username's disappearance from $questName has sparked countless tavern tales. The most popular? A sudden, irresistible craving for pie.",

        "Under the guise of night, $username decides the legends of $questName can wait for a hero with more pressing schedules.",
        "With a shrug of their shoulders, $username turns away from $questName, citing a prior engagement with their bed.",
        "$username gives $questName a long, hard stare and concludes, 'It's not you, it's me.' The quest remains unconquered.",
        "A sudden recollection of an unfinished novel sends $username hurrying away from $questName, promising to return...someday.",
        "As $username steps away from $questName, they whisper to the wind, 'Another time, for I have other dragons to slay.'",
        "$username's footsteps falter, then turn. 'I left the oven on,' they remember, leaving $questName for a more domestic adventure.",
        "The call of $questName is strong, but the call of lunch is stronger. $username heeds the latter, vowing to return.",
        "$username peers into the abyss of $questName and decides, 'I think I'm just going to stay in tonight.'",
        "Faced with the daunting expanse of $questName, $username opts for a strategic withdrawal to the nearest tavern.",
        "‘To fight another day,' $username mutters, deciding that $questName's reward isn't worth the risk...for now.",
        "As $username retreats from $questName, they console themselves with thoughts of quests less perilous and more profitable.",
        "With a casual glance back at $questName, $username decides some tales are best left untold, at least by them.",
        "$username, after careful consideration, delegates the daring deeds of $questName to a more enthusiastic hero.",
        "'Adventure is out there,' $username muses, 'but so is a comfy chair and a good book.' $questName will have to wait.",
        "A bittersweet smile on their lips, $username retreats from $questName, promising the winds they'll return... perhaps.",
        "With a dramatic flourish, $username bows out from $questName, citing creative differences with the storyline.",
        "$username's gaze lingers on $questName before turning away, their heart yearning for adventures of a different kind.",
        "‘Next time, for sure,' $username pledges, their exit from $questName shadowed by the promise of future endeavors.",
        "$username pauses at the edge of $questName, deciding some paths are best walked when the time is right.",
        "A twinkle in their eye, $username backs away from $questName, their story to be continued at a more opportune time.",

        "With a wistful look towards $questName, $username decides their destiny lies along a less perilous path, at least for today.",
        "'Adventure awaits,' $username says, 'but so does my favorite show.' Priorities set, $questName fades into the background.",
        "Casting one last glance at $questName, $username turns to leave, muttering about an overdue library book demanding their attention.",
        "$username's journey towards $questName halts abruptly as they remember their plant needs watering. 'Duty calls,' they sigh.",
        "The prospect of $questName dims as $username recalls the comfort of their warm bed. 'Tomorrow,' they promise the awaiting adventure.",
        "Faced with the daunting task of $questName, $username suddenly recalls a pressing appointment with their cat. The quest must wait.",
        "As $questName looms ahead, $username decides they're not quite dressed for the occasion. 'Next time,' they vow, turning back.",
        "The lure of $questName pales beside the call of freshly baked cookies. $username follows their stomach, vowing to return.",
        "Staring into the depths of $questName, $username suddenly declares, 'I think I left my adventure spirit at home.'",
        "$username ponders the risks of $questName and concludes, 'I'm really more of an indoor person.' The quest is left for another day.",
        "A sudden reminder of a tea kettle left on the stove sends $username scurrying away from $questName, with promises of a rain check.",
        "The thrill of $questName is tempting, but $username remembers they're on a strict schedule of doing absolutely nothing today.",
        "With a skip and a hop, $username decides that $questName can wait. 'There are other quests to not do,' they cheerfully proclaim.",
        "The call of $questName fades as $username is distracted by a peculiarly shaped cloud. 'Looks like a quest for another day,' they muse.",
        "The path to $questName is clear, but $username's path veers towards a newly discovered coffee shop. Adventure can wait for caffeine.",
        "A bout of sudden, selective amnesia strikes $username as they near $questName, convincing them they had other plans all along.",
        "The daunting aura of $questName prompts $username to reconsider. 'I'm more of a consultant,' they decide, retreating to safety.",
        "Eyes wide at the challenges of $questName, $username opts for a strategic regroup at the local inn, indefinitely.",
        "The call of $questName competes with the allure of a sunny day and a hammock. $username chooses the latter, for now.",
        "$username approaches $questName, then pauses. 'Wait, is that a squirrel?' Adventure postponed for unexpected wildlife encounters.",

        "The legend of $questName beckons, but $username suddenly remembers an ancient proverb: 'There's always tomorrow for bravery.'",
        "As $username stands before the gates of $questName, a sudden philosophical doubt arises: 'To quest or not to quest?'",
        "The call of $questName grows faint as $username is sidetracked by an intriguing offer of a free trial at the local mage's guild.",
        "$username, about to step into the lore of $questName, decides their heroic saga is due for a plot twist: 'Brunch time,' they declare.",
        "In the shadow of $questName, $username's resolve wavers, not from fear, but the undeniable lure of a nap in the meadow.",
        "$username's approach to $questName is halted by a rogue thought: 'Did I turn off the cauldron?' Safety first, quest later.",
        "The epic tale of $questName awaits, but $username is suddenly gripped by the need to reorganize their potion collection. 'Priorities,' they nod.",
        "Just steps from $questName, $username is visited by an epiphany — the real quest is the friends made along the way. Especially those with pie.",
        "Gazing upon $questName, $username concludes that some mysteries are best left unsolved, like the enigma of the disappearing socks.",
        "As $questName looms, $username is struck by a sudden, insatiable craving for that one tavern's stew. 'Adventure can wait,' their stomach decides.",
        "The path to $questName diverges, and $username takes the road home, reasoning, 'Even heroes need a day off.'",
        "Confronted with the trials of $questName, $username opts for a tactical diversion to the nearest festival. 'For reconnaissance,' of course.",
        "$username pauses at the brink of $questName, struck by a sudden inspiration for a new song. 'Art calls,' they muse, turning away.",
        "With $questName ahead, $username heeds the unspoken call of the untouched pages of their journal. 'Tonight, I write,' they vow.",
        "The allure of $questName dims as $username spies a rare butterfly, leading them on a different kind of quest altogether.",
        "Faced with the entrance to $questName, $username's adventurous spirit is momentarily eclipsed by the memory of their cozy study. 'Next time,' they promise.",
        "As $questName beckons, $username's attention is captured by a lost puppy. 'Heroes help in many ways,' they smile, quest forgotten.",
        "$questName waits, but $username is waylaid by an impromptu game of cards with locals. 'Learning the lore,' they justify.",
        "Before the mighty $questName, $username recalls the advice of an old friend: 'Never quest on an empty stomach.' Dinner takes precedence.",
        "The epic challenge of $questName fades as $username is reminded of the joy of simple pleasures, like watching clouds pass by."
    ];
    
    
    
    

    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetWonMatchFlavorText($username, $gameName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $gameName;
    }
    
    $flavorTexts = [
        "In a stunning display of skill, $username triumphs over their foes in $gameName, securing their place among the legends.",
        "$username, through cunning and might, claims victory in $gameName. The halls of fame echo their name.",
        "Like a true champion, $username dominates the $gameName arena, leaving no doubt of their prowess.",
        "The battle was fierce, but $username's strategy in $gameName was unbeatable. Victory is theirs!",
        "With a final, decisive move, $username seals their win in $gameName, their name written in the stars.",
        "Against all odds, $username emerges victorious in $gameName, a testament to their undying spirit.",
        "$username has done the impossible in $gameName, turning the tide of battle to claim their well-deserved win.",
        "Victory for $username in $gameName was not just a win; it was a statement. The throne is theirs.",
        "$username's mastery of $gameName shines bright as they outplay their opponent, securing a glorious victory.",
        "In the realm of $gameName, $username stands victorious. Their legacy is unmatched, their triumph absolute.",
        "The echoes of $username's triumph in $gameName will resonate forever. Such skill, such finesse – truly a master.",
        "$username's victory in $gameName is a symphony of strategy and strength, played to perfection.",
        "As the dust settles on the $gameName battlefield, $username stands alone, the epitome of victory.",
        "The annals of $gameName will forever recount the day $username turned the impossible into their triumph.",
        "In the heart of battle, $username's resolve in $gameName was unbreakable, their victory, inevitable.",
        "With grace and power, $username claims their crown in $gameName. A victory well-earned and never to be forgotten.",
        "Today, $gameName witnessed the rise of a champion. $username, through sheer will and talent, claims the top spot.",
        "A new legend is born in $gameName. $username, with unmatched skill, ascends to victory.",
        "The story of $gameName will always speak of $username, the undaunted, whose victory was forged in the heat of battle.",
        "In a league of their own, $username secures a breathtaking win in $gameName, a true master of the game.",

        "The arena of $gameName bears witness to $username's strategic genius, heralding a victory for the ages.",
        "With unparalleled skill, $username dances through the challenge of $gameName, clinching a win that will be remembered forever.",
        "$username's triumph in $gameName is a masterclass in precision and timing, a performance that will echo through the hall of fame.",
        "In the fierce competition of $gameName, $username emerges not just as a winner, but as a legend in their own right.",
        "Victory is sweet, but for $username in $gameName, it's just another day at the top. The crown fits perfectly.",
        "The chronicles of $gameName will glow brighter with the story of $username's triumph, a beacon of victory and valor.",
        "Against the backdrop of challenge, $username's victory in $gameName shines as a testament to their relentless pursuit of greatness.",
        "In a display of sheer dominance, $username secures their win in $gameName, leaving spectators in awe and opponents in dust.",
        "The tale of $username's victory in $gameName is one for the ages, a blend of tactical brilliance and unyielding determination.",
        "$username, with a heart of a champion, carves their name into the $gameName hall of fame, their victory a beacon for aspiring legends.",
        "As $username claims their win in $gameName, the gods of the game nod in approval, a new hero ascended.",
        "In the annals of $gameName, the day will be marked as the moment $username transcended from player to legend.",
        "$username's journey to victory in $gameName was nothing short of epic, a saga of skill that will inspire generations.",
        "With every move in $gameName calculated to perfection, $username's victory is not just a win, but a masterpiece.",
        "The world of $gameName has found its champion in $username, whose victory today is just the beginning of a legendary saga.",
        "Today, $username didn't just win a match in $gameName; they captured the essence of triumph itself.",
        "The echoes of $username's victory in $gameName ripple through time, a testament to their unparalleled prowess.",
        "Victory in $gameName for $username is not measured by the win alone but by the journey of relentless dedication and skill.",
        "In $gameName, $username has not only won the match but also the admiration of all who witness their ascent to glory.",
        "The legacy of $username in $gameName is etched in the annals of victory, a narrative of perseverance, skill, and unmatched talent.",

        "$username's strategy in $gameName was a spectacle of brilliance, a symphony where every move was a note of victory.",
        "With poise and grace under pressure, $username secures a monumental win in $gameName, crafting a moment of pure triumph.",
        "Today, $username didn't just play $gameName; they redefined it, turning strategy into art and competition into conquest.",
        "The lore of $gameName enriches with $username's victory, a saga not of battle, but of unparalleled strategic mastery.",
        "In the tapestry of $gameName's history, $username embroiders their win with golden threads, a testament to their excellence.",
        "Victory for $username in $gameName was foretold in the stars, yet it was their skill that charted the course.",
        "The throne of $gameName beckons, and $username ascends, not as a player, but as a sovereign of strategy.",
        "Amidst the clash of titans in $gameName, $username emerges not just victorious but transcendent, setting a new standard.",
        "The annals of $gameName will forever celebrate this day, when $username turned the impossible into the inevitable.",
        "In $gameName, where legends are forged, $username crafts their epic, a narrative woven with victory and valor.",
        "As $username claims their rightful victory in $gameName, the echo of their success resonates across realms, immortalized.",
        "In the pantheon of $gameName heroes, $username carves their niche, a beacon of brilliance in a sea of competition.",
        "The odyssey of $username through $gameName culminates not in a mere win, but in a legendary triumph.",
        "Victory in $gameName was not handed to $username; it was seized with a confluence of skill, wit, and indomitable will.",
        "The constellation of champions in $gameName shines brighter tonight, guided by the luminescent triumph of $username.",
        "Through the crucible of $gameName, $username emerges not just unscathed but adorned with the laurels of victory.",
        "In the lexicon of $gameName, 'victory' now bears a synonym: $username, a title earned through sheer dominance.",
        "The arena of $gameName, once a battleground, now serves as a pedestal for $username's unmatched prowess.",
        "With a blend of tactical ingenuity and raw talent, $username secures their place in the pantheon of $gameName victors.",
        "The chronicle of $gameName is richer today, adorned with the tale of $username's ascent to the zenith of competition.",

        "In the realm of $gameName, $username's victory resonates like a thunderclap, heralding the rise of a new champion.",
        "With every adversary bested, $username weaves their legend into the fabric of $gameName, a tapestry of triumph.",
        "The chronicles of $gameName will mark this day as the moment $username transcended from competitor to legend.",
        "Victory's sweet chorus sings for $username, a melody carried on the winds of $gameName, heralding their unmatched prowess.",
        "In the annals of $gameName, $username etches their name with the ink of champions, a victory bold and indelible.",
        "As $username stands victorious in $gameName, the heavens themselves pause to bear witness to such unparalleled glory.",
        "The story of $gameName is forever altered, imbued with the tale of $username's conquest, a saga of determination and skill.",
        "With a victory in $gameName, $username does not merely win; they ascend to the realm of legends, where only the greatest dwell.",
        "The echoes of victory for $username in $gameName will be sung by bards and remembered in the hearts of all who dare to dream.",
        "$gameName's fierce battlefields bear witness to $username's triumph, a beacon of victory illuminating the path for others.",
        "In $gameName, where every match is a story, $username pens a chapter of victory, a narrative rich with the spoils of conquest.",
        "Victory in $gameName is $username's testament, a declaration written in the language of strategic mastery and bold heart.",
        "With the dust of $gameName settling, $username emerges not just as a victor but as the architect of their own destiny.",
        "The crown of $gameName finds its rightful bearer in $username, a victor whose strategy and courage are unmatched.",
        "As the final move is played in $gameName, $username's strategy unfolds like a masterstroke, securing a victory for the ages.",
        "In the theater of $gameName, $username takes center stage, their victory a performance that will echo through eternity.",
        "Against the backdrop of $gameName, $username's victory shines as a beacon, guiding future champions towards greatness.",
        "The legacy of $gameName is richer for $username's triumph, a victory that stands as a monument to their unyielding spirit.",
        "In the saga of $gameName, $username carves out their victory, a jewel in the crown of their competitive journey.",
        "The halls of $gameName are hallowed with $username's victory, a testament to their strategic acumen and indomitable will."
    ];
    
    
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetLostMatchFlavorText($username, $gameName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $gameName;
    }
    
    $flavorTexts = [
        "Though the winds of $gameName did not favor $username today, the fire of determination burns ever brighter for next time.",
        "$username may have stumbled in $gameName, but every champion knows: it's the rise after the fall that truly defines greatness.",
        "In the grand tapestry of $gameName, $username's setback is but a dark stroke in a masterpiece of victories yet to come.",
        "The arena of $gameName is unforgiving, but $username's resolve is unyielding. This loss is merely the prelude to future triumphs.",
        "$username's journey in $gameName saw a detour today, but the path to glory is filled with lessons learned from every defeat.",
        "Today, $gameName presented $username with a challenge insurmountable, yet it's these moments that forge the mightiest heroes.",
        "Though $username faced defeat in $gameName, the echoes of their bravery resound, a promise of comeback in the making.",
        "In $gameName, not every battle favors the bold. $username's time to shine is just beyond the horizon, waiting for its moment.",
        "A harsh lesson from the fields of $gameName for $username, but one that plants the seeds of future victories.",
        "Defeat in $gameName is but a shadow on $username's path, dispelled by the light of their perseverance and courage.",
        "The chapters of $gameName are filled with tales of comebacks. $username's story of redemption has just begun.",
        "Every legend in $gameName has its lows, and today, $username writes theirs, setting the stage for their ascent.",
        "$username's dance with $gameName ended in a stumble today, but the music plays on, and the next step could be a leap.",
        "Though $gameName delivered $username a bitter potion today, it's but a momentary taste on the journey to greatness.",
        "In the crucible of $gameName, $username has been tempered by defeat, emerging stronger, ready to face the next challenge.",
        "The wheel of fortune in $gameName turns for $username, and though today it descends, tomorrow it will rise.",
        "A fall in $gameName is nothing but a pause in $username's ascent, a momentary breath before the climb resumes.",
        "Today's defeat in $gameName is $username's canvas, upon which they'll paint their masterpiece of resilience.",
        "$username's echo in $gameName may have faded today, but echoes are born from great forces, and they will roar back louder.",
        "In the saga of $gameName, today's loss for $username is but a cliffhanger, the story of triumph yet to be written.",

        "While today's match in $gameName didn't end in victory for $username, each step back is a setup for a grander leap forward.",
        "The battlefields of $gameName are harsh, yet $username's spirit remains unbroken, ready to rise and fight another day.",
        "In the grand chessboard of $gameName, $username's loss is merely a sacrificial move, setting the stage for future triumph.",
        "Though $gameName has bested $username today, it's but a single brushstroke in the larger portrait of their gaming odyssey.",
        "Defeat may have found $username in $gameName today, but so too does the opportunity to grow, learn, and come back stronger.",
        "$username's tale in $gameName is far from over. Today's loss is merely the plot twist that precedes a victorious climax.",
        "A setback in $gameName challenges $username to stand up, dust off, and show the world the true heart of a warrior.",
        "For $username, today's defeat in $gameName is not the end but a new beginning, a chance to forge a path to redemption.",
        "In the shadow of defeat in $gameName, $username finds a lesson, a spark that ignites the flame of resolve for the battles ahead.",
        "Though $gameName has dealt $username a harsh blow, it's in the crucible of defeat that champions are forged.",
        "$username's journey through $gameName is dotted with battles, some lost, some won, but all teaching the art of resilience.",
        "Defeat in $gameName is merely a detour for $username, not a dead end. The road to victory begins with the next step forward.",
        "Today, $gameName may not have favored $username, but the seeds of a future triumph have been sown in the soil of perseverance.",
        "The echoes of defeat in $gameName will not define $username, but fuel their drive to achieve what once seemed unreachable.",
        "In the dance of victory and defeat in $gameName, $username learns the steps to success are often paced through setbacks.",
        "As $gameName closes one door for $username with defeat, their spirit finds a window, gazing out towards the horizon of comeback.",
        "A stumble in $gameName cannot deter $username's stride; every fall is followed by the rise, every end by a new beginning.",
        "While today's $gameName match leaves $username with lessons, not laurels, each lesson is a stepping stone to future laurels.",
        "Defeat for $username in $gameName is but a whisper in the wind, a fleeting moment that precedes the roar of comeback.",
        "$username's resolve is tested by $gameName, but from the ashes of today's defeat, a phoenix of ambition prepares to soar.",
        
        "Though $gameName's challenge bested $username today, the lessons learned light the way to victory's dawn.",
        "In the echoes of defeat, $username hears the call to rise, a reminder that every end in $gameName is a new beginning.",
        "$username's setback in $gameName is but a prelude to their comeback story, a tale of determination and resolve.",
        "The scoreboard of $gameName does not reflect $username's spirit, which, undeterred, soars beyond the realm of win and loss.",
        "Today, $gameName offered $username a harsh tutor, but in adversity, the most enduring lessons are learned.",
        "While $gameName's verdict may sting, $username's resolve is steeled, forging a path through the forge of defeat.",
        "$username's odyssey in $gameName encounters a storm, but it's through storms that navigators are born.",
        "Defeat is $username's canvas in $gameName; with each loss, the palette of experience deepens, promising a masterpiece in the making.",
        "$username finds in loss not despair but motivation, a spark that ignites the fire of comeback in the heart of $gameName.",
        "The tale of $username and $gameName weaves through valleys of defeat, each valley a lesson leading to the peaks of victory.",
        "For $username, today's defeat in $gameName is not a wall but a gate, challenging them to unlock their true potential.",
        "In the grand theater of $gameName, $username plays the hero, not vanquished by defeat but strengthened, ready for the next act.",
        "Defeat in $gameName teaches $username the rhythm of resilience, where every setback is a beat in the march to triumph.",
        "$username, tested by $gameName, finds not failure but fuel, a burning drive to rise, learn, and conquer anew.",
        "The journey of $username in $gameName is a testament to perseverance, where defeat is merely the teacher of champions.",
        "Amidst the trials of $gameName, $username discovers the true grit of heroes — the courage to continue despite the odds.",
        "Today's loss in $gameName is $username's milestone, marking the moment where resolve is forged and futures are redefined.",
        "As $gameName's challenge unfolds, $username's story is written not in wins or losses but in the courage to persevere.",
        "The arena of $gameName may tally a loss for $username, but their spirit counts a lesson, a step towards inevitable victory.",
        "In $gameName, $username learns that the strength of a warrior is measured not by falls, but by the times they rise after falling.",

        "Each setback in $gameName is a new verse in $username's epic, where the true victory lies in the journey, not the destination.",
        "$username's path in $gameName, marked by today's loss, is but a detour on their road to greatness, filled with lessons and resolve.",
        "In the arena of $gameName, $username's loss today carves the outline of a future champion, honed by experience and undimmed ambition.",
        "The lore of $gameName grows richer with $username's tale, a saga not of defeat, but of resilience, learning, and the quest for mastery.",
        "Defeat in $gameName offers $username a mirror, reflecting not failure but the spark of undying will that promises future victories.",
        "Today, $gameName may have bested $username, but it also lit the forge of their determination, tempering spirit with the fire of resolve.",
        "For $username, the shadow of loss in $gameName casts the light of potential — a beacon guiding them towards their next triumph.",
        "The tapestry of $gameName is vast, and though today's thread is somber for $username, it adds depth to their vibrant mosaic of battles.",
        "Amid the trials of $gameName, $username finds not defeat but a challenge: to rise, to learn, and to return stronger.",
        "The narrative of $gameName and $username is punctuated by today's setback, a comma in their story, pausing before the next great chapter.",
        "In the chronicle of $gameName, $username's momentary defeat is merely the preface to their story of comeback and redemption.",
        "The lessons of $gameName, harsh yet fair, teach $username that true strength is forged in the furnace of adversity.",
        "As $username regroups from today's loss in $gameName, they find the seeds of tomorrow's strategies, ready to sprout and flourish.",
        "In the dance of victory and defeat, $gameName leads $username through a step back today, to leap forward tomorrow.",
        "The echoes of today's loss in $gameName for $username whisper not of end but of renewal, a call to rise anew with dawn's first light.",
        "Each fall in $gameName teaches $username the art of standing up again, a lesson in balance, resolve, and the pursuit of excellence.",
        "For $username, today's defeat in $gameName is a stone in their mosaic of growth, each piece essential to the masterpiece of their journey.",
        "In $gameName's vast expanse, $username's setback today marks not a dead end but a crossroads, offering myriad paths to victory.",
        "The rhythm of $gameName is unpredictable, and though $username stumbles today, it's but a step in their dance towards triumph.",
        "Today's loss in $gameName for $username is not a closing curtain but an intermission, promising a second act filled with potential and victory."
    ];
    
    
    
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}

function GetCommendedSomeoneFlavorText($usernameFrom, $usernameTo, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $usernameFrom . $usernameTo;
    }
    
    $flavorTexts = [
        "With a gesture of true camaraderie, $usernameFrom highlights $usernameTo's spirit, shining a light on the path of unity.",
        "$usernameFrom to $usernameTo: 'Your deeds forge the legends we all aspire to. Together, we are unstoppable.'",
        "In the annals of our game, $usernameFrom commends $usernameTo, etching their mutual respect into the fabric of history.",
        "$usernameFrom has marked $usernameTo as a beacon of hope and integrity, inspiring all who follow in their wake.",
        "Through the voice of $usernameFrom, the virtues of $usernameTo are sung, a melody of respect and shared triumph.",
        "In the chronicle of heroes, $usernameFrom pens a chapter for $usernameTo, a saga of valor and unwavering support.",
        "$usernameFrom to $usernameTo: 'In the art of war and peace, your contributions stand as a masterpiece of teamwork.'",
        "Like stars in the firmament, $usernameFrom and $usernameTo shine together, illuminating the path for others.",
        "$usernameFrom commends $usernameTo, a testament to the power of unity, proving that together, no challenge is insurmountable.",
        "From $usernameFrom to $usernameTo: 'Your strength is the tide that raises all ships, guiding us to victory.'",
        "$usernameFrom recognizes $usernameTo as the cornerstone of their success, a foundation upon which victories are built.",
        "In the dance of competition, $usernameFrom and $usernameTo move in harmony, each step a symphony of collaboration.",
        "$usernameFrom's commendation of $usernameTo is a bridge between legends, a bond forged in the fires of mutual admiration.",
        "The tale of $usernameFrom and $usernameTo becomes legend, a duo whose combined might and spirit conquer all adversities.",
        "$usernameFrom sees in $usernameTo not just an ally, but a mirror reflecting the best of what they aspire to be.",
        "Together, $usernameFrom and $usernameTo craft a narrative of triumph, each commendation a verse in their shared epic.",
        "$usernameFrom's salute to $usernameTo echoes across the realms, a clarion call celebrating unity over individual glory.",
        "The bond between $usernameFrom and $usernameTo is a beacon to others, demonstrating that true strength lies in togetherness.",
        "$usernameFrom commends $usernameTo, weaving their names into the tapestry of camaraderie that blankets the world.",
        "In the realm where $usernameFrom and $usernameTo tread, every commendation is a cornerstone of a legacy built on respect and teamwork.",
        
        "$usernameFrom has witnessed the valor of $usernameTo, commending them for deeds that echo the true essence of heroism.",
        "For courage that lights the darkest paths, $usernameFrom honors $usernameTo, a beacon in the night of challenge.",
        "$usernameFrom applauds $usernameTo for their unwavering spirit, a testament to the resilience found in the heart of a champion.",
        "In the halls of valor, $usernameFrom raises a toast to $usernameTo, whose deeds have woven threads of hope through the tapestry of battle.",
        "$usernameFrom recognizes the wisdom of $usernameTo, whose strategies have turned the tides of conflict into rivers of victory.",
        "The compassion of $usernameTo has not gone unnoticed by $usernameFrom, who commends their ally for acts of kindness amidst turmoil.",
        "$usernameFrom lauds $usernameTo for their leadership, a guiding light that has steered their team through storms to harbor.",
        "With a nod of respect, $usernameFrom celebrates the ingenuity of $usernameTo, whose innovations have redefined the landscape of challenge.",
        "$usernameFrom to $usernameTo: 'Your bravery has not just led to victories, but has inspired all of us to rise higher.'",
        "For generosity unbounded, $usernameFrom commends $usernameTo, whose sharing of knowledge has enriched the community.",
        "$usernameFrom honors $usernameTo for their tireless dedication, a beacon that burns brightly, guiding others to greatness.",
        "The humility of $usernameTo, observed by $usernameFrom, shines as a rare jewel, illuminating the true value of victory.",
        "$usernameFrom marks the camaraderie of $usernameTo as legendary, a bond that strengthens not just a team, but the spirit of the game.",
        "In the annals of their shared battles, $usernameFrom pens a tribute to $usernameTo, whose perseverance has been the drumbeat of their march.",
        "$usernameFrom hails the tactical mastery of $usernameTo, whose strategies have become the legends from which epics are born.",
        "The altruism of $usernameTo, celebrated by $usernameFrom, has sown seeds of unity, growing an orchard from the fields of competition.",
        "$usernameFrom commends $usernameTo for their undaunted courage, facing the gales of challenge with a resolve as steadfast as the mountains.",
        "For the wisdom that turns adversaries into allies, $usernameFrom offers $usernameTo the highest praise, a fellowship forged in respect.",
        "$usernameFrom admires $usernameTo for the grace in victory and humility in defeat, a balance that marks the truest form of mastery.",
        "The echoes of $usernameTo's deeds have reached $usernameFrom, inspiring songs of fellowship that transcend the bounds of the game.",

        "$usernameFrom salutes $usernameTo for their unmatched valor in the face of daunting odds, a true warrior's spirit.",
        "The strategic acumen of $usernameTo, acknowledged by $usernameFrom, has turned the tide of many a battle, crafting victory from the jaws of defeat.",
        "$usernameFrom admires $usernameTo's relentless pursuit of excellence, a journey marked by both triumph and humility.",
        "For bravery beyond measure, $usernameFrom honors $usernameTo, whose deeds stand as a beacon for all aspiring heroes.",
        "The selflessness of $usernameTo, commended by $usernameFrom, has illuminated the path of unity and collective triumph.",
        "$usernameFrom recognizes the inventive genius of $usernameTo, whose creative strategies have rewritten the rules of engagement.",
        "In a realm where challenges abound, $usernameFrom commends $usernameTo for their resilience, a pillar of strength in tumultuous times.",
        "The grace under pressure exhibited by $usernameTo earns the admiration of $usernameFrom, a testament to true leadership.",
        "$usernameFrom to $usernameTo: 'Your ability to rally the spirits of those around you has forged legends out of mere moments.'",
        "For the wisdom that guides and the strength that protects, $usernameFrom lauds $usernameTo, a guardian of their shared values.",
        "$usernameFrom cherishes the camaraderie and support of $usernameTo, whose presence turns every challenge into an opportunity.",
        "The foresight of $usernameTo, praised by $usernameFrom, has been the compass guiding their team through uncharted territories.",
        "In the echo of $usernameTo's achievements, $usernameFrom finds a melody of inspiration, driving them to reach new heights.",
        "$usernameFrom recognizes in $usernameTo a kindred spirit, whose dedication to the game mirrors their own passion and commitment.",
        "The humility with which $usernameTo accepts both victory and defeat resonates deeply with $usernameFrom, a rare quality that defines true champions.",
        "$usernameFrom sees $usernameTo not just as a competitor but as a beacon of integrity and honor in the competitive landscape.",
        "The creativity and innovation of $usernameTo, celebrated by $usernameFrom, have opened new vistas of possibility, challenging all to dream bigger.",
        "$usernameFrom acknowledges the quiet strength of $usernameTo, a force as formidable as the roaring wind, yet as gentle as a whisper.",
        "For $usernameTo's unyielding commitment to fairness and sportsmanship, $usernameFrom offers their highest commendation, a symbol of mutual respect.",
        "$usernameFrom and $usernameTo, through shared trials and triumphs, have woven a tale not just of competition, but of profound alliance and friendship."
    ];
    
    
    
    
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetDenouncedSomeoneFlavorText($usernameFrom, $usernameTo, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $usernameFrom . $usernameTo;
    }
    
    $flavorTexts = [
        "$usernameFrom calls upon $usernameTo to reflect on their actions, reminding them that true strength is found in honor.",
        "In the realm of competition, $usernameFrom denounces $usernameTo's recent choices, urging a return to the path of integrity.",
        "$usernameFrom to $usernameTo: 'The shadows of dishonor may cloud your path, but it's never too late to seek the light.'",
        "$usernameFrom challenges $usernameTo to rise above the fray, to cast aside the chains of dishonor and embrace fair play.",
        "A message from $usernameFrom to $usernameTo: 'Let not the dark taint of poor sportsmanship define your legacy. Choose honor.'",
        "$usernameFrom warns $usernameTo that victory without honor is a hollow triumph, urging them to compete with integrity.",
        "The echoes of the arena whisper of $usernameTo's deeds. $usernameFrom stands in disappointment, hopeful for change.",
        "$usernameFrom sees $usernameTo's potential shadowed by misdeeds and calls for a renewal of respect and sportsmanship.",
        "$usernameFrom reminds $usernameTo that the greatest warriors are remembered not just for their victories, but for their honor.",
        "$usernameFrom marks a solemn note to $usernameTo, emphasizing that the respect of one's peers outweighs any ill-gotten win.",
        "Disheartened by $usernameTo's recent actions, $usernameFrom implores a return to the honorable paths once walked.",
        "$usernameFrom voices a stark admonishment to $usernameTo, stressing that the spirit of the game transcends mere victories.",
        "To $usernameTo, from $usernameFrom: 'May the echoes of dishonor spur a journey back to the esteem of your comrades.'",
        "A somber warning from $usernameFrom to $usernameTo: 'The shadows cast by dishonor are long; step back into the light.'",
        "$usernameFrom, troubled by $usernameTo's path, extends a challenge to reclaim the honor that once defined them.",
        "$usernameFrom confronts $usernameTo, their disappointment a mirror reflecting the fall from grace, yet holding hope for redemption.",
        "In the court of honor, $usernameFrom finds $usernameTo wanting. The call is clear: forsake deception for the valor of fair combat.",
        "$usernameFrom's words to $usernameTo carry the weight of disillusionment, yet also the promise of redemption through better deeds.",
        "Seeing $usernameTo stray, $usernameFrom declares their actions unworthy of the game's spirit, beckoning a return to respect.",
        "$usernameFrom beseeches $usernameTo, 'Let not the allure of victory blind you to the nobility of competing with honor.'",

        "$usernameFrom expresses deep concern over $usernameTo's recent conduct, urging them to remember the virtues of fair play.",
        "Disappointed by $usernameTo's actions, $usernameFrom calls for a return to the honorable principles that define true competitors.",
        "$usernameFrom sends a stern message to $usernameTo: 'Victories tarnished by dishonor only lead to empty triumphs.'",
        "Witnessing $usernameTo stray from the path, $usernameFrom implores, 'Let integrity be your compass and honor your guide.'",
        "$usernameFrom to $usernameTo: 'Reflect on the shadow cast by deceit. Only in truth does the spirit of the game flourish.'",
        "$usernameFrom challenges $usernameTo to mend the rift caused by their actions, advocating for a return to respect and dignity.",
        "In the wake of $usernameTo's missteps, $usernameFrom emphasizes, 'Greatness lies not in bending the rules, but in uplifting the spirit of the game.'",
        "$usernameFrom, troubled by $usernameTo's choices, invites them to rebuild the bridge of trust, one honorable deed at a time.",
        "Seeing $usernameTo falter, $usernameFrom offers a guiding hand back to the path of sportsmanship, where true victory awaits.",
        "$usernameFrom rebukes $usernameTo for actions unbecoming of a champion, urging them to reclaim their honor on the field of fair play.",
        "The spirit of competition weeps at $usernameTo's deeds. $usernameFrom stands ready to witness their journey back to honor.",
        "$usernameFrom reminds $usernameTo that the echo of dishonor is long and dark, but the road to redemption is lit by the torch of integrity.",
        "To $usernameTo, from $usernameFrom: 'Your potential is vast, yet unfulfilled. Cast aside the veil of deceit and let your true skill shine.'",
        "$usernameFrom marks $usernameTo's actions as a cautionary tale, a reminder that the price of dishonor is the loss of respect.",
        "In the heart of conflict, $usernameFrom sees $usernameTo losing their way, extending an olive branch to guide them back to honor.",
        "$usernameFrom declares to $usernameTo, 'The shadows of dishonor can be dispelled, but only by the light of truthful deeds.'",
        "Faced with $usernameTo's departure from virtue, $usernameFrom asserts, 'True champions are measured by their honor, not their trophies.'",
        "$usernameFrom laments $usernameTo's fall from grace, yet holds out hope for their resurgence as a paragon of fairness and respect.",
        "$usernameFrom denounces $usernameTo's recent path, yet in their words lies a beacon, calling for a return to the noble ways of old.",
        "The tale of $usernameTo's misdeeds reaches $usernameFrom, who responds not with scorn, but with a hopeful plea for change and growth.",

        "$usernameFrom observes $usernameTo's choices with concern, whispering a reminder that every action casts a shadow or light upon their legacy.",
        "The breach of honor by $usernameTo summons $usernameFrom to voice disappointment, yet also to extend a hand in hopes of a noble return.",
        "$usernameFrom marks $usernameTo's fall not with glee but with a somber hope for their realization and return to the path of virtue.",
        "To $usernameTo, $usernameFrom sends a missive, not of ire, but of encouragement: 'Let today's misstep be the seed for tomorrow's honor.'",
        "$usernameFrom, bearing witness to $usernameTo's misdeeds, proclaims, 'True victory lies in the strength of one's character, not in fleeting triumphs.'",
        "In the wake of $usernameTo's dishonor, $usernameFrom stands, a beacon of hope, believing in their potential for redemption.",
        "$usernameFrom to $usernameTo: 'The arena of competition is unforgiving, yet it offers redemption. Seize it, and forge a legacy of integrity.'",
        "Seeing $usernameTo veer off course, $usernameFrom offers a map back to honor, their words a compass pointing towards integrity.",
        "$usernameFrom, disheartened by $usernameTo's actions, still holds a torch aloft, illuminating the road back to respect and sportsmanship.",
        "‘Every champion stumbles,' $usernameFrom tells $usernameTo, ‘but the greatest of heroes are those who rise with lessons learned.'",
        "‘The shadows you cast today can be outshone by the light of tomorrow's deeds,' $usernameFrom advises $usernameTo, a guidepost back to honor.",
        "In a world where actions echo, $usernameFrom reminds $usernameTo that echoes of dishonor can be silenced by acts of redemption.",
        "$usernameFrom sees in $usernameTo's falter a crossroads, with one path leading back to the light of respect and mutual admiration.",
        "‘Your journey need not end in darkness,' declares $usernameFrom to $usernameTo, ‘for every end heralds a new beginning of honor.'",
        "$usernameFrom, watching $usernameTo's decline, signals a beacon of hope, a testament that faith in redemption burns eternal.",
        "$usernameFrom casts a somber glance at $usernameTo, their rebuke soft yet firm, a call to shed the cloak of dishonor for garments of integrity.",
        "To $usernameTo, ensnared by folly, $usernameFrom offers a key to liberation: ‘Embrace humility, seek forgiveness, and rediscover honor.'",
        "The missteps of $usernameTo draw a sigh from $usernameFrom, not of resignation, but of resolve to mentor them back to the light.",
        "‘Let the ink of today's dishonor dry,' suggests $usernameFrom to $usernameTo, ‘and tomorrow, pen a new chapter of valor and respect.'",
        "$usernameFrom, once dismayed by $usernameTo's deeds, now fosters hope for their awakening to the dawn of renewed honor and dignity."
    ];
    
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetHostedQuestFlavorText($username, $questName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $questName;
    }
    
    $flavorTexts = [
        "Urgent call: $username urgently seeks valiant souls for $questName. Step forth and claim your destiny in this tale of valor.",
        "Hear ye, hear ye: $username's desperate plea for the bold and the brave resounds, setting the stage for $questName's harrowing journey.",
        "$username's urgent summons echoes across the lands for $questName, calling forth champions to band together in this crucial hour.",
        "Dire need: $username, creator of $questName, faces impending peril. Every corner holds untold dangers awaiting brave hearts to conquer.",
        "Historic moment: $username's urgent hosting of $questName marks a turning point, beckoning heroes to etch their names in legend.",
        "A clarion call from $username to undertake $questName shakes the very foundations of the land, rallying the courageous to rise.",
        "$username ignites the signal fires for $questName, guiding the bold towards a destiny fraught with danger and unparalleled treasure.",
        "The time is now: $username declares $questName a crucial test of valor and strength. Gather, heroes, and forge your legacies.",
        "Valor's call: The call to $questName by $username promises glory to those who answer, a beacon for the brave at this dire time.",
        "Under $username's dire need, $questName stands as the ultimate trial, challenging the brave to display their true strength.",
        "$username's urgent call to $questName is a pact with destiny, beckoning the chosen to alter the course of the world.",
        "Whispers of necessity: $questName by $username speaks of a critical adventure where only the most heroic will prevail and legends are forged.",
        "The time for action is now: $username's call for $questName is an undeniable summons to adventure for the valorous and the brave.",
        "$username has sparked the urgent flame of $questName in the wilderness, a beacon calling to those willing to face the unknown.",
        "The saga of $questName awaits, penned in urgency by $username. Heroes, your moment of valor and resolve has come.",
        "By $username's vision, $questName is more than a quest; it's a plea for heroes willing to shape destiny through courage and decision.",
        "An immediate summons from $username to $questName stands as a testament to unity's strength in times of dire need.",
        "The legend of $questName now urgently calls under $username's guidance, promising a tale of courage that will echo through eternity.",
        "As $username unveils $questName, the realm teeters on the brink of an epochal adventure, beckoning the brave to make history.",
        "The urgent call of $questName by $username is not just an invitation but a necessity for the daring, promising peril and the chance to become legendary.",

        "The hour is dire: $username calls upon the bravest for $questName, a quest where every second counts and every action could turn the tide.",
        "Emergency beckons: $username's plea for heroes to confront $questName cannot be ignored. The fate of many hangs in balance.",
        "Critical call: $username needs heroes for $questName now more than ever. A journey fraught with peril awaits those who dare respond.",
        "$username's urgent request: $questName teeters on the brink of catastrophe. Only the bold can steer it back from the abyss.",
        "The realm's plea: Under $username's guidance, $questName has become a beacon of hope. Brave souls, your time to act is now.",
        "Destiny's demand: $username and $questName are at a crossroads. Heroes, your intervention is critical to the saga's outcome.",
        "Immediate action required: $username's call for $questName signals a pivotal moment. Only the courageous will make a difference.",
        "The clarion call of $questName by $username resonates with urgency. It's a summons to battle, to bravery, and to history-making.",
        "$username's beacon of hope: $questName is a plea for help, a challenge that demands heroes to step forward without delay.",
        "Now or never: $username's $questName is in peril, and only a swift gathering of adventurers can avert disaster.",
        "Urgency in the air: $username declares $questName a mission of critical importance. Heroes, your swift response is needed.",
        "$username's desperate bid for $questName seeks immediate heroes. This is a call to arms in a moment of unprecedented need.",
        "The fate of $questName, under $username's stewardship, hangs by a thread. Brave adventurers, your hour to shine is upon us.",
        "Critical juncture: $username's $questName faces its darkest hour. Heroes, your valor and quick action can save the day.",
        "A plea for bravery: $username's $questName is on the verge of despair. Only the heartiest of adventurers can turn the tide.",
        "In $username's most desperate hour, $questName stands as a testament to the need for heroes. Will you answer the call?",
        "Time-sensitive mission: $username's call to $questName is not just an invitation, but a summons to save a world in crisis.",
        "The rallying cry for $questName by $username pierces the silence. Heroes, your swift actions are the realm's last hope.",
        "$username's urgent summons to $questName heralds a trial where only the most resolute will triumph against impending doom.",
        "This is it: $username's urgent mobilization for $questName is a call to arms against the darkness. Heroes, to your destiny!",

        "The call to arms rings clear: $username's urgent need for $questName heralds a time of dire straits. Heroes, rise to this unprecedented call.",
        "Time of need: $username's beckoning for $questName is a clarion call to those who dare defy the odds and emerge as legends.",
        "Urgent summons: The shadow over $questName grows deeper by the moment. $username seeks champions to dispel the darkness.",
        "$username's call pierces the veil of complacency: $questName is a quest on the brink, and only the daring can shift its fate.",
        "A cry for help: $questName, under $username's watch, faces imminent danger. This is a summon for the brave to act swiftly.",
        "The moment of truth: $username and $questName stand at a precipice. Heroes, your deeds now will echo through eternity.",
        "A desperate plea: $username's $questName is a battleground where the line between triumph and despair is thin. Answer the call.",
        "Urgent adventure awaits: $username's plea for $questName carries the weight of fate. The time to act is now, or never.",
        "$username's urgent beacon for $questName shines amidst the storm. Heroes, navigate through the tempest to salvation.",
        "Crisis point: The tale of $questName is at a critical juncture. $username calls for heroes to turn the tide of destiny.",
        "The urgent quest of $questName, heralded by $username, is a siren's call to those with courage enough to face the unknown.",
        "In $username's darkest hour, the echo of $questName calls for saviors. This urgent plea cannot go unanswered by the brave.",
        "$questName stands as a testament to $username's urgent need for heroes. The bell tolls for those ready to answer its call.",
        "The call of destiny: $username's $questName is a beacon in the night, urging heroes to embark on a crucial journey.",
        "Now, more than ever, $username's summons to $questName is a dire need, not just a request. Heroes, your action is vital.",
        "$username's rallying cry for $questName cuts through the despair. This urgent mission is the forge upon which legends are made.",
        "As peril looms, $username's $questName becomes a clarion call to those who can turn the tide against overwhelming odds.",
        "Urgency and fate collide in $username's call for $questName. This moment defines heroes - will you be among them?",
        "$username's desperate call for $questName signals a pivotal moment in history. Heroes, your bravery is needed now more than ever.",
        "In the face of looming disaster, $username's $questName is an urgent plea for heroes. This is the call to action that cannot be ignored.",

        "The final hour approaches: $username's urgent appeal for $questName seeks heroes with the courage to face the ultimate challenge.",
        "At the edge of despair, $username's $questName cries out for salvation. Only the bravest can lift the shadow threatening the land.",
        "The beacon burns brightest in dark times: $username's call for $questName seeks lights in the darkness, heroes ready to defy fate.",
        "Echoes of urgency: $username's $questName is not merely a call, but a thunderous demand for heroes to forge a new destiny.",
        "The gauntlet is thrown: $username's desperate plea for $questName challenges the valiant to rise above the storm and lead the way.",
        "In the wake of peril, $username's $questName emerges as the ultimate test of valor. The time to act is now, heroes of legend.",
        "A world in balance: $username's $questName signals a crucial turning point. Heroes, your deeds today will shape tomorrow.",
        "The urgent call of $questName, issued by $username, is a rallying cry to those who would stand as the realm's last hope.",
        "A plea from the heart: $username's $questName is a cry for help that resonates through the souls of the courageous and the bold.",
        "The sands of time dwindle for $questName. $username's urgent summons seeks those who can change the course of history.",
        "As the shadow looms, $username's urgent invitation to $questName is a beacon for those who can bring dawn to the darkest night.",
        "The drumbeat of destiny: $username's $questName calls forth warriors to stand against a tide that threatens to engulf the world.",
        "A clarion call for bravery: $username's $questName is an urgent summons to those who would pen their names in the annals of history.",
        "The tide of fate awaits: $username's $questName is a desperate bid for heroes to seize control of destiny's helm.",
        "The last stand: $username's call to $questName is a desperate battle cry, summoning the few, the brave, to turn the tide.",
        "Under a crimson sky, $username's urgent need for $questName heralds a call to arms. The brave shall answer, the bold will rise.",
        "A desperate beacon in the night: $username's call for $questName seeks heroes to guide the realm through its darkest hour.",
        "The whisper of destiny grows loud: $username's $questName is a plea for the brave to forge a path where none dare tread.",
        "In $username's most desperate moment, $questName becomes more than a quest; it's a cry for heroes to emerge and save the day.",
        "Against the encroaching darkness, $username's $questName stands as a final bastion. Heroes, your time to shine is now."
    

    
    ];
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetEarnedBadgeFlavorText($username, $badgeName, $seedString = null)
{
    $badgeName .= " badge";
    if ($seedString == null)
    {
        $seedString = $username . $badgeName;
    }
    
    $flavorTexts = [
        "In a realm of legends, $username stands taller today, having secured the $badgeName, a symbol of unparalleled achievement.",
        "With courage and perseverance, $username has etched their name in the halls of glory, earning the $badgeName as a testament to their deeds.",
        "The $badgeName now shines as a beacon of $username's unwavering resolve and unmatched skill. A true hero's accolade.",
        "Amidst cheers and awe, $username claims the $badgeName, a reward for a journey marked by persistence and excellence.",
        "The chronicles of valor are rich today, for $username has been bestowed with the $badgeName, an honor reserved for the few.",
        "Let it be known that $username has achieved what many dream of but few attain: the illustrious $badgeName, symbol of true mastery.",
        "In the annals of history, the day $username earned the $badgeName will be remembered as a milestone of heroic feats.",
        "The $badgeName is not given, but earned, and $username has shown the strength, wisdom, and heart worthy of this accolade.",
        "Today, $username stands as a beacon to all aspirants, having been awarded the $badgeName for their extraordinary accomplishments.",
        "A new chapter in legend is penned as $username receives the $badgeName, a tribute to their indomitable spirit.",
        "The $badgeName, a symbol of high honor and achievement, now adorns $username, marking their place among the stars.",
        "With the earning of the $badgeName, $username has transcended the ordinary, embodying excellence and inspiring awe.",
        "The journey of $username culminates in the earning of the $badgeName, a testament to their relentless pursuit of greatness.",
        "As the $badgeName is bestowed upon $username, let it be a reminder of what determination, skill, and courage can achieve.",
        "$username, by securing the $badgeName, has turned dreams into reality, setting a benchmark for excellence.",
        "The $badgeName, now linked forever with $username, is not just a badge but a legend, a story of triumph over trials.",
        "For $username, the $badgeName is a harbinger of glory, symbolizing a journey filled with challenges overcome and victories won.",
        "In achieving the $badgeName, $username has not only proven their mettle but has also become a guiding light for others.",
        "The tale of $username and the $badgeName is one of perseverance, skill, and the power of belief. A true testament to greatness.",
        "With pride and honor, $username takes their rightful place among legends, the $badgeName a shining emblem of their journey.",

        "Under the gaze of the ancients, $username has achieved the remarkable, securing the $badgeName as proof of their legendary prowess.",
        "The wind whispers the tale of $username's triumph, carrying news of their earning the $badgeName across lands and seas.",
        "By the light of the stars, $username's feat shines brightly, the $badgeName a symbol of their journey through challenges to greatness.",
        "The $badgeName, a beacon of $username's unwavering determination and exceptional skill, now heralds their name to all corners of the world.",
        "Let the echoes of victory resound: $username has claimed the $badgeName, etching their name in the eternal tapestry of heroes.",
        "In the dance of destiny, $username has stepped boldly, earning the $badgeName through courage, wisdom, and an unbreakable will.",
        "The $badgeName, now borne by $username, is a testament to their journey beyond the ordinary, into the realm of legends.",
        "With the acquisition of the $badgeName, $username has woven their story into the fabric of history, a tale of triumph and valor.",
        "The realm celebrates as $username is adorned with the $badgeName, a mark of distinction earned through sheer perseverance and skill.",
        "Amidst the trials of the age, $username emerges victorious, the $badgeName a symbol of their indomitable spirit and achievement.",
        "Across the annals of time, the story of $username and the $badgeName will be told as a beacon of hope and a testament to human spirit.",
        "The $badgeName rests now with $username, a crown of achievement forged in the fires of determination and hard-fought battles.",
        "As $username clasps the $badgeName, it's not just an award that they earn, but an immortal legacy they begin to weave.",
        "In the heart of the fray, it was $username who emerged victorious, the $badgeName a jewel of their crown, hard-earned and well-deserved.",
        "The $badgeName stands not just for what $username has achieved, but for the journey, the battles fought, and the challenges overcome.",
        "With the grace of the victorious, $username has rightfully claimed the $badgeName, setting a standard for excellence and determination.",
        "The saga of $username's ascent to earning the $badgeName will be sung by bards, a melody of resilience, talent, and unwavering resolve.",
        "The $badgeName, now linked to $username, shines as a testament to the power of ambition, the spirit of adventure, and the essence of triumph.",
        "For $username, the $badgeName is not just an accolade, but a symbol of the roads traveled, the adversities faced, and the victories secured.",
        "In a universe of endless tales, the story of $username earning the $badgeName shines bright, a narrative of perseverance, strength, and glory.",


        "The stars align for $username as they embrace the $badgeName, a luminary symbol of extraordinary feats and unwavering perseverance.",
        "$username's journey reaches a pinnacle with the acquisition of the $badgeName, marking a saga of determination, skill, and indelible spirit.",
        "Let history record this moment: $username has been deemed worthy of the $badgeName, a testament to their unparalleled achievements.",
        "The $badgeName finds its rightful bearer in $username, a beacon of excellence in a sea of challengers and trials.",
        "Against all odds, $username has risen to claim the $badgeName, etching their legacy into the bedrock of legends.",
        "Today, $username stands not just as a participant, but as a champion, heralded by the honor of the $badgeName.",
        "As $username secures the $badgeName, they set a new horizon for all who follow, a pinnacle of achievement and glory.",
        "The echo of $username's triumph in earning the $badgeName will resonate through eternity, a symphony of resilience and mastery.",
        "With the earning of the $badgeName, $username has not just achieved greatness; they have redefined it for generations to come.",
        "The $badgeName, a lodestar of ambition and success, now proudly claimed by $username, illuminates the path for all seekers of excellence.",
        "In the lexicon of legends, $username has authored a new chapter with the earning of the $badgeName, a narrative of victory and valor.",
        "Upon the mantle of eternity, the $badgeName shines as a jewel of $username's undying legacy, a crown of unfathomable accomplishments.",
        "The horizon expands as $username claims the $badgeName, a harbinger of the boundless potential that lies within the heart of the brave.",
        "$username, now synonymous with the $badgeName, stands as a testament to the power of dreams fortified by grit and grace.",
        "The annals of time will forever celebrate $username's conquest of the $badgeName, a beacon of human potential and triumph.",
        "With the grace of a sovereign, $username assumes their rightful place among the elite with the $badgeName, a symbol of their sovereign journey.",
        "The $badgeName, now a part of $username's legacy, serves as a shining emblem of courage, insight, and an unrelenting pursuit of excellence.",
        "In the gallery of greatness, $username's name is illuminated beside the $badgeName, an everlasting tribute to their journey of achievement.",
        "The saga of $username and the $badgeName unfolds as a tale of perseverance, ingenuity, and the relentless pursuit of the extraordinary.",
        "As $username clasps the $badgeName, they do not merely hold a token of achievement but a key to the pantheon of legends.",

        "The quest for greatness culminates as $username is adorned with the $badgeName, a symbol not just of victory, but of the journey and the battles fought.",
        "Through the tempest of trials, $username emerges, the $badgeName in hand, a beacon of their unwavering determination and indomitable spirit.",
        "As $username is bestowed the $badgeName, it marks not the end, but a new beginning in their saga of perseverance and triumph over adversity.",
        "The $badgeName, a testament to $username's relentless pursuit of excellence, now stands as a beacon for all who dare to dream.",
        "In the theater of valor, $username takes center stage, the $badgeName shining brightly as a testament to their heroic deeds and spirit.",
        "With the awarding of the $badgeName, $username's legacy is cemented in the annals of time, a hero for the ages, an inspiration for all.",
        "The $badgeName is not just an award; it's a declaration. Today, $username has been declared a paragon of virtue and strength.",
        "$username's grasp on the $badgeName symbolizes more than achievement; it's a grasp on history, on a moment that will be remembered forever.",
        "As $username dons the $badgeName, they stand not only as a beacon of achievement but as a guiding light for future generations.",
        "The path was arduous, the journey fraught with peril, but in the end, $username stands victorious, the $badgeName theirs to claim.",
        "The $badgeName, now linked to $username, becomes a symbol of unyielding courage, a testament to the spirit of adventure and conquest.",
        "For $username, the $badgeName is not merely a prize but a symbol of the countless hours of dedication, determination, and discipline.",
        "With the acquisition of the $badgeName, $username has not only reached a pinnacle of achievement but has set a new standard for excellence.",
        "In the tapestry of legends, the thread of $username's story weaves a vivid pattern, highlighted by the earning of the $badgeName.",
        "The $badgeName, awarded to $username, stands as a monument to their journey, a journey of perseverance, skill, and indomitable will.",
        "Today, as $username claims the $badgeName, they reaffirm the age-old adage: Through perseverance and tenacity, all is achievable.",
        "The $badgeName, now a part of $username's identity, shines as a testament to their journey, their battles, and their ultimate triumph.",
        "Let the halls of history echo with the news: $username has earned the $badgeName, a feat of such rarity and significance.",
        "As the $badgeName joins $username's collection, it serves as a beacon of their unparalleled commitment to pursuing and achieving greatness.",
        "With the $badgeName now in $username's possession, they have etched their name among the stars, a celestial testament to their achievement."



    ];
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}

function GetLostTournamentFlavorText($username, $tournamentName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $tournamentName;
    }
    
    $flavorTexts = [
        "Though $username did not claim victory in $tournamentName, their courage and spirit blaze a trail for future triumphs.",
        "In the saga of $tournamentName, $username's chapter may not have ended in victory, but the tale of their valor is far from over.",
        "The battle of $tournamentName was fierce, and while $username did not emerge victorious, their determination remains unbroken.",
        "$username's journey in $tournamentName ends not with a trophy, but with invaluable lessons and the resolve to rise again.",
        "Victory in $tournamentName eluded $username, but the fires of ambition and the thirst for challenge burn brighter than ever.",
        "Though $username faced defeat in $tournamentName, it's but a momentary setback in the grand adventure that awaits.",
        "Every champion faces defeat, and $tournamentName was $username's crucible. From these ashes, a phoenix will rise.",
        "The echoes of $tournamentName will remind $username that every loss is a step towards greatness. The journey continues.",
        "$tournamentName was a stern teacher for $username, but the lessons learned in defeat are stepping stones to victory.",
        "$username may have stumbled in $tournamentName, but even legends have faced their trials before reaching the stars.",
        "Let not the outcome of $tournamentName define $username, for it is in the striving, not just the triumph, that heroes are made.",
        "$tournamentName's challenge was met with courage by $username. This chapter closes, but the story of their triumph is yet to be written.",
        "In the heart of $username, the spirit remains undimmed by the outcome of $tournamentName. The quest for glory goes on.",
        "$username's resolve is tested by $tournamentName, yet it's in these moments that the true strength of a champion is forged.",
        "The tale of $tournamentName is but a prelude to $username's epic comeback. Watch this space, for a star is on the rise.",
        "Defeat in $tournamentName is merely a shadow on $username's path to greatness. With each step, the light grows brighter.",
        "$tournamentName offered $username a tough battle, and though they didn't win, their spirit and tenacity shine as a beacon for all.",
        "Though $tournamentName saw $username not as the victor, their journey is far from over. The best is yet to come.",
        "$username's venture in $tournamentName has ended, not in victory, but in valuable experience and renewed determination.",
        "The winds of $tournamentName have shifted against $username, but they set sail again, undeterred, towards the horizon of success.",
    
        "Though the tides of $tournamentName did not turn in $username's favor, their voyage towards victory has only just begun.",
        "The arena of $tournamentName was unforgiving, but $username's resolve has only been steeled for the battles that lie ahead.",
        "In the echoes of $tournamentName, let $username find not despair but the clarion call to rise, learn, and conquer anew.",
        "The story of $tournamentName may not herald $username as the victor, but it marks the birth of a resilience that time will honor.",
        "$username's aspirations in $tournamentName were lofty, and though not met, the journey has only honed their spirit for future glory.",
        "While $tournamentName's chapter ends without a crown for $username, their saga of growth and perseverance is far from conclusion.",
        "$tournamentName has taught $username that every end is but a new beginning, and the quest for excellence is eternal.",
        "The shadows cast by $tournamentName cannot eclipse the light of $username's determination. A brighter dawn awaits.",
        "Let $tournamentName be a lesson to $username: In the crucible of defeat, the strongest warriors are forged.",
        "$username's journey through $tournamentName has ended, but the path to greatness is littered with lessons from every fall.",
        "Though $tournamentName has slipped from $username's grasp, their eyes remain fixed on the horizon, where victory lies in wait.",
        "$tournamentName was merely a battle, not the war. $username's spirit remains unvanquished, their eyes set on future victories.",
        "In the aftermath of $tournamentName, $username's resolve is tested, but within them burns a flame that defeat cannot extinguish.",
        "The ledger of $tournamentName closes with $username on the challenging side of victory, yet their story of triumph is just beginning.",
        "$tournamentName has added a chapter of strife to $username's tale, but it is through adversity that heroes truly rise.",
        "Though $tournamentName saw $username fall, it's in the getting back up that their character is shown, bright and indomitable.",
        "$tournamentName's outcome is but a single note in the symphony of $username's journey. The melody of success is yet to reach its crescendo.",
        "In the reflection of $tournamentName's trials, $username will find not just loss, but the seeds of future triumphs.",
        "The chapter of $tournamentName may close without accolades for $username, but within its lines lie invaluable wisdom for the next victory.",
        "$username's story in $tournamentName is not of defeat, but of preparing a foundation upon which victories are built.",

        "Though $tournamentName has concluded without triumph for $username, their journey towards mastery and glory continues unabated.",
        "Within the halls of $tournamentName, $username faced a trial by fire. Though not victorious, the flames have only tempered their resolve.",
        "$username's venture into $tournamentName has ended not with laurels, but with a resolve that burns ever brighter towards the next challenge.",
        "The outcome of $tournamentName is but a footnote in $username's epic quest for greatness. Forward they march, undeterred and more determined.",
        "While $tournamentName did not see $username emerge as the victor, the experience carves the path for a future filled with success.",
        "$tournamentName's lessons are harsh, yet $username emerges not weakened, but wiser and ready to conquer anew.",
        "The shadows of $tournamentName will not long hang over $username. In defeat, they find the strength to rise to new heights.",
        "$username's aspirations in $tournamentName may have been dashed, but their spirit and determination remain unshaken.",
        "In the aftermath of $tournamentName, $username finds not defeat, but the fuel to ignite their journey to triumph.",
        "$tournamentName was a battle, not the war. $username's resolve to win, to learn, and to grow, remains unbroken.",
        "Though $tournamentName has ended, for $username, it's a new dawn of determination and a renewed quest for victory.",
        "The challenge of $tournamentName was met with bravery by $username. Each setback, a stepping stone to their eventual success.",
        "$tournamentName's trials have prepared $username for the victories to come. The journey to greatness is paved with lessons learned.",
        "Though the winds of $tournamentName did not favor $username, their sails are set towards new horizons, undeterred by the storm.",
        "$tournamentName has taught $username that true strength lies not in winning, but in the courage to continue despite the odds.",
        "Let $tournamentName be remembered not for $username's loss, but for the indomitable spirit they displayed in the face of adversity.",
        "$username's path through $tournamentName was strewn with challenges, each one a lesson leading them closer to their ultimate victory.",
        "Though $tournamentName marks a momentary setback for $username, their journey is far from over. Onwards they go, with eyes on the prize.",
        "$tournamentName's outcome does not define $username; it is but a chapter in their story, pushing them towards their destiny.",
        "In the echoes of $tournamentName, $username hears not defeat, but a call to rise, rebuild, and return stronger than ever.",

        "The curtain falls on $tournamentName for $username, but in the grand theatre of competition, their encore is eagerly awaited.",
        "Though $tournamentName did not crown $username, the forge of competition has tempered them for future battles and victories.",
        "The road through $tournamentName was rugged, and while $username did not reach the end as victor, their resolve is unscathed and stronger.",
        "$username's tale in $tournamentName may not have ended in victory, yet it's a vital chapter in their ongoing saga of resilience and ambition.",
        "Defeat in $tournamentName is merely a detour on $username's journey to greatness. Their spirit, undeterred, charts a course to new challenges.",
        "In the aftermath of $tournamentName, $username gathers the lessons of the fray, armor for the battles that lie ahead.",
        "The echoes of $tournamentName will not define $username but serve as a beacon guiding them to their next conquest.",
        "$tournamentName has ended, but for $username, it's merely a prologue to a story of redemption, perseverance, and ultimate victory.",
        "While $tournamentName saw $username fall, it's from the ashes of defeat that the phoenix of their ambition will rise.",
        "Though the spoils of $tournamentName eluded $username, their journey is far from over. The quest for glory never ends.",
        "$username's expedition through $tournamentName may have ended in setback, but the map to victory is drawn with the ink of persistence.",
        "In the chronicle of $tournamentName, $username's story is marked by courage. This chapter closes, but their legend continues to unfold.",
        "Though $tournamentName was not $username's triumph, the battle scars are badges of honor, each a lesson leading to victory.",
        "$tournamentName has provided $username with the crucible needed to forge a champion. Their time to shine is just over the horizon.",
        "The tale of $tournamentName for $username ends not with victory, but with the invaluable treasure of experience and renewed fervor.",
        "$tournamentName's outcome for $username is but a momentary shadow in the brilliance of their enduring quest for excellence.",
        "Though $tournamentName did not end in victory for $username, the fires of determination are fueled, readying them for the next challenge.",
        "$username's venture into $tournamentName has ended, yet it's the resilience in defeat that will herald their future successes.",
        "The lessons of $tournamentName are etched in $username's journey, each one a stepping stone on the path to their ultimate triumph.",
        "While $tournamentName did not see $username emerge victorious, it's the spirit of perseverance that paves their road to success."

    ];
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}

function GetWinTournamentFlavorText($username, $tournamentName, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $tournamentName;
    }
    
    $flavorTexts = [
        "$username stands victorious in $tournamentName, a testament to unmatched skill and unwavering determination.",
        "Amidst the echoes of triumph, $username claims the crown of $tournamentName, their name etched in the annals of glory.",
        "The champion of $tournamentName, $username, basks in the glory of victory, their prowess undisputed and spirit unyielded.",
        "With a display of sheer brilliance, $username has conquered $tournamentName, securing a place among the legends.",
        "$tournamentName will forever remember the day $username rose above all, a beacon of excellence in the competitive fray.",
        "In a stunning display of skill and strategy, $username has claimed the title of $tournamentName champion, a true master of the game.",
        "Victory in $tournamentName is $username's! A performance that will be remembered through the ages, a blend of talent and tenacity.",
        "The journey through $tournamentName ends with $username on top, a hero celebrated for their courage, skill, and heart.",
        "As the victor of $tournamentName, $username has demonstrated that through perseverance and skill, greatness is within reach.",
        "The saga of $tournamentName culminates with $username as the victor, their story one of triumph, resilience, and undying spirit.",
        "$username's mastery has been crowned in $tournamentName, etching their victory in the eternal flame of legend.",
        "Through the trials of $tournamentName, $username emerged not just a participant, but a champion, their legacy sealed in victory.",
        "In the arena of $tournamentName, $username has triumphed, their strategy and strength heralding a new era of champions.",
        "The echoes of victory resound as $username clinches the title in $tournamentName, a feat of skill that will inspire generations.",
        "$tournamentName has been claimed by $username, a beacon of triumph in the tumultuous sea of competition.",
        "With the dust of $tournamentName settled, $username stands triumphant, a testament to the power of ambition and hard work.",
        "The crown of $tournamentName rests upon $username, a symbol of their unparalleled skill and the heart of a champion.",
        "Victory in $tournamentName is not just a win for $username but a celebration of the spirit of competition and excellence.",
        "$username's victory in $tournamentName is a dazzling display of prowess, setting them apart as a titan in the arena of competition.",
        "As the champion of $tournamentName, $username has not only won the tournament but also the admiration and awe of all who witnessed.",

        "The legacy of $tournamentName is forever enriched by $username, whose triumph stands as a pinnacle of competition and excellence.",
        "$username's ascendancy in $tournamentName is a tale of grit, strategy, and indomitable will, culminating in a well-deserved victory.",
        "In the annals of $tournamentName, the name $username is now synonymous with greatness, their victory a beacon of unmatched prowess.",
        "Against all odds, $username has emerged as the undisputed champion of $tournamentName, a legend in their own right.",
        "The throne of $tournamentName has been claimed by $username, a ruler crowned through skill, determination, and strategic mastery.",
        "Let it be known across realms that $username has conquered the $tournamentName, setting a new standard for excellence and courage.",
        "With valor and skill, $username has navigated the challenges of $tournamentName to emerge victorious, a hero for the ages.",
        "The triumph of $username in $tournamentName is a symphony of strategic brilliance and competitive spirit, a masterpiece of victory.",
        "Through the storm of competition, $username has emerged as the lighthouse of $tournamentName, guiding the way with their triumph.",
        "$username's victory in $tournamentName is a testament to their relentless pursuit of excellence and their indomitable champion's spirit.",
        "As the dust settles on $tournamentName, $username stands alone at the summit, their victory echoing through the halls of fame.",
        "The story of $tournamentName is forever changed by $username, whose victory writes a new chapter of inspiration and achievement.",
        "In a display of unparalleled talent, $username has clinched the title of $tournamentName champion, a testament to their dedication.",
        "The crown of $tournamentName rests on the head of $username, worn with the dignity and grace of a true champion.",
        "$tournamentName has found its champion in $username, a warrior whose battle to the top will be recounted for generations.",
        "With the heart of a champion, $username has turned dreams into reality by winning $tournamentName, inspiring all who dare to compete.",
        "$username's triumph in $tournamentName marks not the end of a journey, but the beginning of a legacy of excellence and victory.",
        "The echo of $username's victory in $tournamentName will resonate as a melody of dedication, skill, and undying perseverance.",
        "Victory in $tournamentName has immortalized $username in the pantheon of greats, their achievement a beacon of eternal glory.",
        "The odyssey of $tournamentName concludes with $username as its champion, their saga a beacon of hope and a testament to the warrior's spirit.",

        "$username's victory in $tournamentName shines as a beacon of determination, illuminating the path for future champions.",
        "In the fierce competition of $tournamentName, $username emerged not just victorious but legendary, their name etched in history.",
        "The triumph of $username in $tournamentName is a testament to their unyielding spirit and mastery, a crowning glory of their journey.",
        "With skill and valor, $username has claimed the zenith of $tournamentName, their victory a landmark in the chronicles of the contest.",
        "The tale of $tournamentName is forever adorned with $username's triumph, a saga of perseverance, strategy, and unparalleled skill.",
        "$username, through the crucible of $tournamentName, has achieved the sublime, etching their victory in the annals of the ages.",
        "In the grand theatre of $tournamentName, $username has taken center stage, their victory a performance for the ages.",
        "The odyssey through $tournamentName ends with $username as the victor, their journey a testament to the power of ambition and resolve.",
        "$username's ascent to victory in $tournamentName is a masterpiece of competitive spirit, celebrated across all realms of the game.",
        "The legacy of $tournamentName will be forever marked by the extraordinary achievement of $username, a champion among champions.",
        "Victory in $tournamentName is $username's ode to excellence, a symphony of skill, perseverance, and strategic genius.",
        "The realm of $tournamentName has found its sovereign in $username, a ruler whose reign is founded on valor, wisdom, and strength.",
        "In the annals of $tournamentName, the chapter on $username's victory is a beacon of inspiration, echoing through time.",
        "The crown of $tournamentName, now worn by $username, is a testament to their journey from contender to undisputed champion.",
        "By conquering $tournamentName, $username has woven their narrative into the fabric of legend, a tale of victory against all odds.",
        "With the conquest of $tournamentName, $username stands as a colossus, their triumph a beacon of excellence and indomitable will.",
        "The echoes of $username's victory in $tournamentName reverberate as a call to greatness, heralding a new era of competition.",
        "Amidst the clash of titans in $tournamentName, $username emerged supreme, their triumph a testament to the art of victory.",
        "The saga of $username and $tournamentName is one of triumph over trial, a narrative of enduring spirit and unmatched skill.",
        "As $username claims victory in $tournamentName, their story becomes a legend, inspiring all who seek to turn dreams into reality.",

        "The annals of $tournamentName now glow with the tale of $username, whose brilliance in battle has forged a legacy of victory.",
        "As $username stands atop the podium of $tournamentName, their triumph not only defines this moment but lights the way for future champions.",
        "In the realm of $tournamentName, $username has risen as a beacon of excellence, their victory a testament to relentless pursuit and skill.",
        "Through the trials of $tournamentName, $username emerged as the epitome of greatness, setting a new pinnacle of achievement.",
        "$username's conquest of $tournamentName is a saga of resilience, a chronicle of a champion who dared to reach beyond the stars.",
        "With the triumph in $tournamentName, $username has inscribed their name in the eternal flame of champions, a beacon for all seekers of glory.",
        "The tapestry of $tournamentName is forever enriched by the valorous saga of $username, a narrative woven with the threads of triumph.",
        "$username's mastery has turned the tides of $tournamentName, etching their victory in the stone of time, unerasable and immortal.",
        "In the chorus of $tournamentName champions, the story of $username resounds the loudest, a melody of perseverance and victory.",
        "The legend of $tournamentName has a new hero: $username, whose journey to victory is a testament to the power of courage and conviction.",
        "$username has transformed $tournamentName into a theater of their glory, their triumph a spectacle of unparalleled prowess and determination.",
        "Within the halls of $tournamentName, $username's name echoes as a synonym for victory, a reminder that heroes are forged in the heart of battle.",
        "By claiming victory in $tournamentName, $username has not just won a tournament but has become a beacon of hope and inspiration.",
        "The victory of $username in $tournamentName is a masterpiece painted with strokes of genius, strategy, and indomitable will.",
        "As the dust settles on $tournamentName, it's $username who stands victorious, their triumph a beacon in the odyssey of champions.",
        "The crown of $tournamentName now belongs to $username, worn not as a symbol of power, but as a testament to their journey of perseverance.",
        "$username's victory in $tournamentName is a beacon of excellence, illuminating the path for all who follow in their footsteps.",
        "In the legacy of $tournamentName, the triumph of $username shines as a guiding star, symbolizing that no dream is too distant, no victory out of reach.",
        "With the echoes of victory in $tournamentName, $username has etched their saga into the bedrock of history, a champion for the ages.",
        "The story of $tournamentName is now inseparable from the triumph of $username, a tale of victory that will inspire generations to come."



    ];
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}


function GetWroteBlogPostFlavorText($username, $blogPostTitle, $seedString = null)
{
    if ($seedString == null)
    {
        $seedString = $username . $blogPostTitle;
    }
    
    $flavorTexts = [
        "By the quill's might, $username has inscribed '$blogPostTitle' into the grand archive, illuminating the kingdom of minds with newfound lore.",
        "Within the realm's vast library, $username has unfurled the scroll of '$blogPostTitle', charting unexplored territories of thought and wonder.",
        "'$blogPostTitle' by $username now stands etched upon the stone of knowledge, a beacon of wisdom in the digital kingdom's heart.",
        "From the depths of the scholarly tower, $username unveils '$blogPostTitle', a beacon guiding seekers through the sea of boundless information.",
        "With '$blogPostTitle', $username has woven a new legend into the tapestry of discourse, enriching the kingdom with tales of insight and enlightenment.",
        "The halls of wisdom are adorned anew with '$blogPostTitle' by $username, a tome that challenges, delights, and awakens the curious.",
        "Through the echoing chambers of the digital realm, '$blogPostTitle' by $username emerges as a monument to the quest for understanding.",
        "In the grand tradition of the web's scribes, $username pens '$blogPostTitle', a saga that captivates and emboldens the spirit.",
        "With the launch of '$blogPostTitle', $username casts a stone of wisdom into the pond of thought, creating ripples that stretch beyond horizons.",
        "By $username's hand, '$blogPostTitle' emerges, a beacon of ideas adorning the vast halls of our shared digital kingdom.",
        "In the intellect's enchanted garden, $username plants the seed of '$blogPostTitle', blossoming into a source of endless inspiration and dialogue.",
        "Under $username's banner, '$blogPostTitle' shines as a guiding light for the intellectually brave, a testament to the enduring quest for truth.",
        "With craftsmanship and fervor, $username forges '$blogPostTitle', embedding their insights into the infinite tapestry that cloaks the digital ether.",
        "Announcing the arrival of '$blogPostTitle', $username embarks on a bold expedition into the untamed wilds of knowledge and expression.",
        "As a bridge architected by $username, '$blogPostTitle' spans the divide between minds, a rendezvous for ideas to converge in pursuit of enlightenment.",
        "Through the mist of obscurity, $username sends forth '$blogPostTitle', a whisper turning into a chorus of discourse and discovery.",
        "In the cosmos of contemplation, '$blogPostTitle' by $username emerges as a newly kindled star, guiding us through the twilight of ignorance.",
        "With the unfurling of '$blogPostTitle', $username sets sail on the vast seas of dialogue, charting a course through waves of discourse.",
        "Crafting '$blogPostTitle', $username etches their name into the citadel of thought leaders, inspiring legions with their profound revelations.",
        "In the hands of $username, the digital quill crafts '$blogPostTitle', a testament to the timeless dominion of the written word over the minds of many.",
        
        "With the debut of '$blogPostTitle', $username beckons us from the ramparts of the digital fortress, illuminating paths yet untrodden in the realm of ideas.",
        "The scribe $username brings forth '$blogPostTitle', a compendium of thoughts that kindles the torches of inspiration and reflection.",
        "As $username paints the canvas of the web with '$blogPostTitle', they summon a tapestry of wisdom that spans the walls of our collective citadel.",
        "In the nocturnal hours, '$blogPostTitle' by $username acts as a beacon, guiding the lost back to the shores of clarity and comprehension.",
        "The coronation of '$blogPostTitle' in the domain of thought marks $username as a sage, their intellect a jewel in the crown of shared knowledge.",
        "On the quest for enlightenment, '$blogPostTitle' by $username stands as a pivotal relic, turning the wheels of inquiry towards answers unseen.",
        "As the age of information soldiers on, '$blogPostTitle' by $username stands as a fortress of introspection and thoughtful articulation.",
        "$username, through the script of '$blogPostTitle', charts a voyage across the uncharted waters of exploration, in relentless pursuit of verity.",
        "With the revelation of '$blogPostTitle', $username invites us to the crossroads of past wisdom and future insight, where thoughts converge and set forth anew.",
        "From the phoenix quill of $username arises '$blogPostTitle', a rebirth of ideas in the vast landscape of digital dialogue.",
        "In the crafting of '$blogPostTitle', $username weaves a rich tapestry within the grand hall of discourse, inviting all who pass to gaze and wonder."
        


    ];
    
    $index = GetRandomIntFromSeedString($seedString, count($flavorTexts));
    return $flavorTexts[$index];
}

// Get a system-based salt using server IP and process ID
function GetSystemSalt() {
    $localIP = gethostbyname(gethostname());
    $processId = getmypid();
    return crc32($localIP . $processId);
}

// Get high-resolution time in nanoseconds
function GetHighResTime() {
    $time = hrtime(true); // Return as a number of nanoseconds
    return $time;
}

// Generate a random integer based on a seed
function GetSeededRandomInt($seed) {
    mt_srand($seed);
    return mt_rand();
}

// Combine system salt and high-resolution time to generate a unique crand
function GenerateCRand() {
    $salt = GetSystemSalt();
    $time = GetHighResTime();
    $seed = $time + $salt;
    return GetSeededRandomInt($seed);
}

function EnsureSessionStarted() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

?>