<?php

namespace Kickback\Services;

use mysqli;
use Kickback\Config\ServiceCredentials; 

class Database {
    private static $conn = null;

    public static function getConnection(): mysqli {
        if (self::$conn === null) {
            // Fetching credentials
            $servername = ServiceCredentials::get("sql_server_host");
            $username = ServiceCredentials::get("sql_username");
            $password = ServiceCredentials::get("sql_password");
            $database = ServiceCredentials::get("sql_server_db_name");

            // Attempting to establish a database connection
            self::$conn = new mysqli($servername, $username, $password, $database);

            // Error handling
            if (self::$conn->connect_error) {
                throw new \Exception("Connection failed: " . self::$conn->connect_error);
            }
        }

        return self::$conn;
    }
}
?>
