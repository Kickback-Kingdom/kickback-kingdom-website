<?php
declare(strict_types=1);

namespace Kickback\Controllers;

use Kickback\Services\Session;
use Kickback\Services\Database;
use Kickback\Models\Response;
use Kickback\Models\RecordId;

class AnalyticController
{

    private static function getCurrentUserIP() {
        $ipAddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            // Check ip from share internet
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // To check ip is pass from proxy
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipAddress = 'IP Not Found';
        }
        return $ipAddress;
    }
    
    private static function getCurrentDevicePlatform() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Special Devices
        if (stripos($userAgent, 'googlebot') !== false) {
            return 'Googlebot';
        } elseif (stripos($userAgent, 'bingbot') !== false) {
            return 'Bingbot';
        } elseif (stripos($userAgent, 'slurp') !== false) {
            return 'Yahoo Bot';
        } elseif (stripos($userAgent, 'tesla') !== false) {
            return 'Car Browser';
        } elseif (stripos($userAgent, 'watch') !== false) {
            return 'Wearable';
        } elseif (stripos($userAgent, 'oculus') !== false) {
            return 'VR Headset';
        } elseif (stripos($userAgent, 'playstation') !== false) {
            return 'PlayStation';
        } elseif (stripos($userAgent, 'xbox') !== false) {
            return 'Xbox';
        } elseif (stripos($userAgent, 'nintendo') !== false) {
            return 'Nintendo';
        }
        
        
    
        // General devices
        if (stripos($userAgent, 'mobile') !== false) {
            return 'Mobile';
        } elseif (stripos($userAgent, 'tablet') !== false) {
            return 'Tablet';
        } elseif (stripos($userAgent, 'smart-tv') !== false || stripos($userAgent, 'tv') !== false) {
            return 'Smart TV';
        } else {
            return 'Desktop';
        }
    }
    
    
    private static function convertCountryCodeToContinent($countryCode) {
        // Define the mapping from country codes to continent codes
        $countryToContinent = [
            "BD" => "AS", "BE" => "EU", "BF" => "AF", "BG" => "EU", "BA" => "EU", "BB" => "NA", "WF" => "OC", "BL" => "NA", 
            "BM" => "NA", "BN" => "AS", "BO" => "SA", "BH" => "AS", "BI" => "AF", "BJ" => "AF", "BT" => "AS", "JM" => "NA", 
            "BV" => "AN", "BW" => "AF", "WS" => "OC", "BQ" => "NA", "BR" => "SA", "BS" => "NA", "JE" => "EU", "BY" => "EU", 
            "BZ" => "NA", "RU" => "EU", "RW" => "AF", "RS" => "EU", "TL" => "OC", "RE" => "AF", "TM" => "AS", "TJ" => "AS", 
            "RO" => "EU", "TK" => "OC", "GW" => "AF", "GU" => "OC", "GT" => "NA", "GS" => "AN", "GR" => "EU", "GQ" => "AF", 
            "GP" => "NA", "JP" => "AS", "GY" => "SA", "GG" => "EU", "GF" => "SA", "GE" => "AS", "GD" => "NA", "GB" => "EU", 
            "GA" => "AF", "SV" => "NA", "GN" => "AF", "GM" => "AF", "GL" => "NA", "GI" => "EU", "GH" => "AF", "OM" => "AS", 
            "TN" => "AF", "JO" => "AS", "HR" => "EU", "HT" => "NA", "HU" => "EU", "HK" => "AS", "HN" => "NA", "HM" => "AN", 
            "VE" => "SA", "PR" => "NA", "PS" => "AS", "PW" => "OC", "PT" => "EU", "SJ" => "EU", "PY" => "SA", "IQ" => "AS", 
            "PA" => "NA", "PF" => "OC", "PG" => "OC", "PE" => "SA", "PK" => "AS", "PH" => "AS", "PN" => "OC", "PL" => "EU", 
            "PM" => "NA", "ZM" => "AF", "EH" => "AF", "EE" => "EU", "EG" => "AF", "ZA" => "AF", "EC" => "SA", "IT" => "EU", 
            "VN" => "AS", "SB" => "OC", "ET" => "AF", "SO" => "AF", "ZW" => "AF", "SA" => "AS", "ES" => "EU", "ER" => "AF", 
            "ME" => "EU", "MD" => "EU", "MG" => "AF", "MF" => "NA", "MA" => "AF", "MC" => "EU", "UZ" => "AS", "MM" => "AS", 
            "ML" => "AF", "MO" => "AS", "MN" => "AS", "MH" => "OC", "MK" => "EU", "MU" => "AF", "MT" => "EU", "MW" => "AF", 
            "MV" => "AS", "MQ" => "NA", "MP" => "OC", "MS" => "NA", "MR" => "AF", "IM" => "EU", "UG" => "AF", "TZ" => "AF", 
            "MY" => "AS", "MX" => "NA", "IL" => "AS", "FR" => "EU", "IO" => "AS", "SH" => "AF", "FI" => "EU", "FJ" => "OC", 
            "FK" => "SA", "FM" => "OC", "FO" => "EU", "NI" => "NA", "NL" => "EU", "NO" => "EU", "NA" => "AF", "VU" => "OC", 
            "NC" => "OC", "NE" => "AF", "NF" => "OC", "NG" => "AF", "NZ" => "OC", "NP" => "AS", "NR" => "OC", "NU" => "OC", 
            "CK" => "OC", "XK" => "EU", "CI" => "AF", "CH" => "EU", "CO" => "SA", "CN" => "AS", "CM" => "AF", "CL" => "SA", 
            "CC" => "AS", "CA" => "NA", "CG" => "AF", "CF" => "AF", "CD" => "AF", "CZ" => "EU", "CY" => "EU", "CX" => "AS", 
            "CR" => "NA", "CW" => "NA", "CV" => "AF", "CU" => "NA", "SZ" => "AF", "SY" => "AS", "SX" => "NA", "KG" => "AS", 
            "KE" => "AF", "SS" => "AF", "SR" => "SA", "KI" => "OC", "KH" => "AS", "KN" => "NA", "KM" => "AF", "ST" => "AF", 
            "SK" => "EU", "KR" => "AS", "SI" => "EU", "KP" => "AS", "KW" => "AS", "SN" => "AF", "SM" => "EU", "SL" => "AF", 
            "SC" => "AF", "KZ" => "AS", "KY" => "NA", "SG" => "AS", "SE" => "EU", "SD" => "AF", "DO" => "NA", "DM" => "NA", 
            "DJ" => "AF", "DK" => "EU", "VG" => "NA", "DE" => "EU", "YE" => "AS", "DZ" => "AF", "US" => "NA", "UY" => "SA", 
            "YT" => "AF", "UM" => "OC", "LB" => "AS", "LC" => "NA", "LA" => "AS", "TV" => "OC", "TW" => "AS", "TT" => "NA", 
            "TR" => "AS", "LK" => "AS", "LI" => "EU", "LV" => "EU", "TO" => "OC", "LT" => "EU", "LU" => "EU", "LR" => "AF", 
            "LS" => "AF", "TH" => "AS", "TF" => "AN", "TG" => "AF", "TD" => "AF", "TC" => "NA", "LY" => "AF", "VA" => "EU", 
            "VC" => "NA", "AE" => "AS", "AD" => "EU", "AG" => "NA", "AF" => "AS", "AI" => "NA", "VI" => "NA", "IS" => "EU", 
            "IR" => "AS", "AM" => "AS", "AL" => "EU", "AO" => "AF", "AQ" => "AN", "AS" => "OC", "AR" => "SA", "AU" => "OC", 
            "AT" => "EU", "AW" => "NA", "IN" => "AS", "AX" => "EU", "AZ" => "AS", "IE" => "EU", "ID" => "AS", "UA" => "EU", 
            "QA" => "AS", "MZ" => "AF"
        ];
    
        // Look up the country code in the mapping
        if (array_key_exists($countryCode, $countryToContinent)) {
            return $countryToContinent[$countryCode];
        } else {
            return "??"; // Return "Unknown" if the country code is not found
        }
    }
    
    private static function fetchGeoData($ipAddress) {
        
        $ipinfo_api_key = \Kickback\Config\ServiceCredentials::get("ipinfo_api_key");
        $url = "https://ipinfo.io/{$ipAddress}?token={$ipinfo_api_key}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10-second timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $error = 'Curl error: ' . curl_error($ch);
            curl_close($ch);
            return new Response(false, $error, null);
        }
        if ($httpCode != 200) {
            $error = 'Failed to retrieve data, HTTP status code: ' . $httpCode;
            curl_close($ch);
            return new Response(false, $error, null);
        }
    
        curl_close($ch);
        return new Response(true, "Data fetched successfully", json_decode($response, true));
    }
    
    private static function getCurrentGeoLocation() {
        Session::ensureSessionStarted();
        $ipAddress = self::getCurrentUserIP();
    
        // Check if geolocation data for this IP is already cached in the session
        if (isset($_SESSION['geoLocation']) && $_SESSION['geoLocation']['ip'] === $ipAddress) {
            return new Response(true, "Returning cached data", (object)$_SESSION['geoLocation']['data']);
        }
    
        // Fetch new data if not cached
        $response = self::fetchGeoData($ipAddress);
        if (!$response->success) {
            // Prepare default geolocation data when fetch fails
            $defaultGeoData = (object) [
                'continent' => '??', 
                'country' => '??', 
                'city' => '??', 
                'region' => '??'
            ];
            $_SESSION['geoLocation'] = [
                'ip' => $ipAddress,
                'data' => $defaultGeoData
            ];
            return new Response(false, "Failed to fetch geolocation data, defaulting to placeholders", $defaultGeoData);
        }
    
        $geoData = $response->data;
    
        // Ensure all fields have a value, defaulting to "??" if missing
        $defaults = ['continent' => '??', 'country' => '??', 'city' => '??', 'region' => '??'];
        foreach ($defaults as $key => $defaultValue) {
            if (empty($geoData[$key])) {
                $geoData[$key] = $defaultValue;
            }
        }
    
        // Optionally parse the continent here, if not included and needed
        if ($geoData['continent'] === '??' && isset($geoData['country']) && $geoData['country'] !== '??') {
            $geoData['continent'] = self::convertCountryCodeToContinent($geoData['country']);
        }
    
        // Cache the geolocation data in the session
        $_SESSION['geoLocation'] = [
            'ip' => $ipAddress,
            'data' => $geoData
        ];
    
        return new Response(true, "GeoLocation data fetched and cached successfully", (object)$geoData);
    }
    
    
    private static function insertAnalytic($group, $action, $result, $description, $details) {
        
        $conn = Database::getConnection();
        
    
        $ctime = RecordId::getCTime();
    
        $ip_address = self::getCurrentUserIP();
        $session_id = Session::getCurrentSessionId();
        $device_platform = self::getCurrentDevicePlatform();
        if (Session::isLoggedIn())
        {
            $accountId = Session::getCurrentAccount()->crand;
        }
        else{
            $accountId = null;
        }
        $geoLocationResponse = self::getCurrentGeoLocation();
        if ($geoLocationResponse->success) {
            $geoLocation = $geoLocationResponse->data;
            $continent = $geoLocation->continent;
            $country = $geoLocation->country;
            $city = $geoLocation->city;
            $region = $geoLocation->region;
            // Now you can use these variables with the assurance that they have valid data or "??".
        }
    
        // Prepare the SQL statement
        $sql = "INSERT INTO analytic (ctime, crand, `group`, `action`, result, ip_address, account_id, session_id, device_platform, continent, country, city, region, description, details) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // Handle error (e.g., log the error, return an API response)
            return new Response(false, 'Failed to prepare statement', null);
        }
    
        while (true) {  // Infinite loop, will break on successful insertion
            $crand = RecordId::generateCRand();
    
            // Bind parameters
            mysqli_stmt_bind_param($stmt, 'sissssiisssssss',
                $ctime, $crand, $group, $action, $result,
                $ip_address, $accountId, $session_id, $device_platform,
                $continent, $country, $city, $region,
                $description, $details);
    
            // Execute the statement
            mysqli_stmt_execute($stmt);
    
            // Check for errors using MySQL error code 1062 for duplicate entry
            if ($stmt->errno == 1062) {
                // If it's a duplicate key error, retry with a new `crand`
                continue; 
            } elseif ($stmt->errno) {
                // If the error is not a duplicate key error, return the error message
                $message = 'Error: ' . $stmt->error;
                mysqli_stmt_close($stmt);
                return new Response(false, $message, null);
            } else {
                // If no error, exit the loop
                break;
            }
        }
    
        // Retrieve the newly inserted ID
        $newId = mysqli_insert_id($conn);
    
        // Close the statement
        mysqli_stmt_close($stmt);
    
        if ($newId) {
            return new Response(true, 'Insert successful', $newId);
        } else {
            return new Response(false, 'Insert failed, no ID generated', null);
        }
    }
    
    public static function getCurrentPage() {
    
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $queryString = $_SERVER['QUERY_STRING'];
        $fullUrl = $queryString ? $scriptName . '?' . $queryString : $scriptName;
    
        return $fullUrl;
    }
    
    public static function recordPageVisit($url = null) {
    
        $fullUrl = self::getCurrentPage();
        if ($url == null)
        {
            $pageId = $fullUrl;
        }
        else {
            $pageId = $url;
        }
        $prettyURL = $_SERVER['REQUEST_URI'];
    
        // Details for analytics tracking
        $details = json_encode([
            'url' => $fullUrl,
            'prettyUrl' => $prettyURL
        ]);
    
        // Insert the analytic data with the sanitized and correctly assembled URL
        self::insertAnalytic("Website Interaction", "Page Visit", $pageId, 'A page visit occurred', $details);
    }
    
    public static function getPageVisits($pageId) {
        
        $conn = Database::getConnection();
        
        // Prepare the SQL query to count visits for the current URL
        $sql = "SELECT COUNT(*) AS visit_count FROM analytic WHERE result = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            // Return an APIResponse indicating failure if the statement couldn't be prepared
            return new Response(false, "Error preparing statement: " . $conn->error, null);
        }
    
        // Bind the URL parameter to the prepared statement
        $stmt->bind_param("s", $pageId);
        $stmt->execute();
        
        // Get the result set from the prepared statement
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $visitCount = $row['visit_count'];
            $stmt->close();
            // Return an APIResponse indicating success along with the visit count
            return new Response(true, "Visit count retrieved successfully", $visitCount);
        } else {
            // Return an APIResponse indicating failure if there was an issue fetching the results
            $stmt->close();
            return new Response(false, "Error fetching results: " . $stmt->error, null);
        }
    }
    
    public static function getMonthlyGrowthStats() {
        
        $conn = Database::getConnection();
        
        $sql = "SELECT month, new_accounts, total_accounts, growth_percentage, active_accounts, retention_rate FROM kickbackdb.v_analytic_accounts_growth_retention_monthly;";
        $stmt = $conn->prepare($sql);
    
        if (!$stmt) {
            // Return an APIResponse indicating failure if the statement couldn't be prepared
            return new Response(false, "Error preparing statement: " . $conn->error, null);
        }
    
        // Execute the prepared statement
        if (!$stmt->execute()) {
            // Return an APIResponse indicating failure if the statement couldn't be executed
            return new Response(false, "Error executing statement: " . $stmt->error, null);
        }
    
        // Fetch results into an associative array
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    
        // Clean up statement
        $stmt->close();
    
        // Return an APIResponse with the fetched data
        return new Response(true, "Monthly growth stats", $rows);
    }
    
}

?>