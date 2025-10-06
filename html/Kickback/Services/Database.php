<?php

namespace Kickback\Services;

use Exception;

use \mysqli;
use Kickback\Backend\Config\ServiceCredentials;

class Database {
    private static ?\mysqli $conn = null;

    public static function getConnection(): \mysqli {
        if (self::$conn === null) {
            // Fetching credentials
            $servername = ServiceCredentials::get("sql_server_host");
            $username = ServiceCredentials::get("sql_username");
            $password = ServiceCredentials::get("sql_password");
            $database = ServiceCredentials::get("sql_server_db_name");
            
            // This documents the types of the variables, and also makes PHPStan happy.
            assert(is_string($servername));
            assert(is_string($username));
            assert(is_string($password));
            assert(is_string($database));

            // Attempting to establish a database connection
            self::$conn = new \mysqli($servername, $username, $password, $database);

            // Error handling
            if (!is_null(self::$conn->connect_error)) {
                throw new \Exception("Connection failed: " . self::$conn->connect_error);
            }
            
             // Set charset and collation to ensure consistency
             if (!self::$conn->set_charset("utf8mb4")) {
                throw new \Exception("Error setting charset: " . self::$conn->error);
            }
            
            // Set the collation to utf8mb4_unicode_ci for consistency
            if (!self::$conn->query("SET collation_connection = 'utf8mb4_unicode_ci'")) {
                throw new \Exception("Error setting collation: " . self::$conn->error);
            }
        }

        return self::$conn;
    }

    public static function executeSqlQuery(string $stmt, array $params)
    { 
        try
        {
            $connection = Database::getConnection();

            if(count($params) > 0)
            {
                $result = mysqli_execute_query($connection, $stmt, $params);
            }
            else
            {
                mysqli_options($connection, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1); //to ensure type casting occurs properly; without this, everything returns as a string
                $result = mysqli_query($connection, $stmt);
            }
        }   
        catch(Exception $e)
        {
            throw new Exception("Exception caught while executing sql query : $e");
        }    

        return $result;
    }

    public static function changeDatabase(string $databaseName)
    {
        Database::getConnection()->select_db($databaseName);

        if(Database::$conn->error)
        {
            throw new Exception("Error in changing databases : ".Database::$conn->error);
        }
    }

    /*
    //  this function may only be used when batch inserting in the same table
    //  the function takes a single insertion statment which is used to insert
    //  each set of parameters defined in the paramSets variable
    *//*
    public static function executeBatchInsertion(string $stmt, array $paramSets, ?string $databaseName = null)
    {
        $conn = self::getConnection();

        //throw new Exception($stmt);
        //throw new Exception(json_encode($paramSets));

        if($databaseName != null)
        {
            Database::changeDatabase($databaseName);
        }

        mysqli_autocommit($conn, false);
        $conn->begin_transaction();

        foreach($paramSets as $paramSet)
        {    
            self::prepareAndExecuteQuery($stmt, $paramSet);
        }

        $conn->commit();
        mysqli_autocommit($conn, true);
    }*/

    private static function prepareAndExecuteQuery($stmt, $params)
    {
        $conn = self::getConnection();

        $mysqliStmt = mysqli_prepare($conn, $stmt);

        mysqli_stmt_bind_param($mysqliStmt, self::returnMysqliDatatypes($params), ...$params);

        try
        {
            mysqli_stmt_execute($mysqliStmt);
        }
        catch(Exception $e)
        {
            throw new Exception("stmt : '".$stmt."'   params : '".json_encode($params)."'"." with exception : ".$e);
        }
        
    }

    /*
    //  "b" for blob is excluded as this is for single params for insertion; 
    //  if larger params are desired, the statment should be manually 
    //  prepared, binded, and executed.
    //  A large file bool flag could be added but not without the cost
    //  of a significant loss of usability and readability.
    */
    public static function returnMysqliDatatypes(array $params) : string
    {
        $datatypeString = "";

        foreach($params AS $param)
        {
            
            if(is_int($param))
            {
                $datatypeString .= "i";
            }
            elseif(is_float($param))
            {
                $datatypeString .= "d";
            }
            else
            {
                $datatypeString .= "s";
            }
        }

        return $datatypeString;
    }

    // Dead code?
    // private static function handleStmtError(\mysqli_stmt $stmt, string $message): Response {
    //     error_log($stmt->error);
    //     $stmt->close();
    //     return new Response(false, $message, null);
    // }
    
}
?>
