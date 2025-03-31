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
        return new Kickback\Backend\Models\Response(false, "'$name' contains no data",null);
    }
    else{
        return new Kickback\Backend\Models\Response(true, "All data is present",null);
    }
}
 
function POSTContainsFields(...$fields)
{
    foreach($fields as $field){
        if (!isset($_POST[$field]))
        {
            //$resp = new Kickback\Backend\Models\Response(false, "Request body is not formated correctly. Missing data '$field'",null);
            //exit();
            return new Kickback\Backend\Models\Response(false, "Request body is not formated correctly. Missing data '$field'",null);
        }
    }

    return new Kickback\Backend\Models\Response(true, "Request body is formatted correctly",null);
}

function SESSIONContainsFields(...$fields)
{
    foreach($fields as $field){
        if (!isset($_SESSION[$field]))
        {
            //$resp = new Kickback\Backend\Models\Response(false, "Request body is not formated correctly. Missing data '$field'",null);
            //exit();
            return new Kickback\Backend\Models\Response(false, "Session information not found. Missing data '$field'",null);
        }
    }

    return new Kickback\Backend\Models\Response(true, "Session information was found",null);
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

function ValidateCTime($data)
{
    if (!isset($data)) {
        return "";
    }

    return trim($data);
}


function GetSQLError()
{
    return $GLOBALS["conn"]->error;
}


function encode_id($id) {
    return rtrim(strtr(base64_encode($id), '+/', '-_'), '=');
}

function decode_id($str) {
    return base64_decode(str_pad(strtr($str, '-_', '+/'), strlen($str) % 4, '=', STR_PAD_RIGHT));
}

function free_mysqli_resources($mysqli) {
    while ($mysqli->more_results() && $mysqli->next_result()) {
        $dummyResult = $mysqli->use_result();
        if ($dummyResult instanceof mysqli_result) {
            $dummyResult->free();
        }
    }
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

?>