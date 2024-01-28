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

?>