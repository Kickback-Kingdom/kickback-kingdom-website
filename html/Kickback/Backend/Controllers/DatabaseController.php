<?php

declare(strict_types=1);

namespace Kickback\Backend\Controllers;

use \Kickback\Services\Database;

use \Kickback\Backend\Views\vRecordId;

use \Kickback\Backend\Models\Response;

use Exception;

use function PHPSTORM_META\map;

/**
* Inherited classes must implement `$instance_` as a nullable static member, like so:
* ```
* protected static ?self $instance_ = null;
* ```
*
* This allows the DatabaseController class to implement the `instance()` method
* on behalf of the derived class.
*/
abstract class DatabaseController
{
    protected abstract function allViewColumns();

    protected abstract function allTableColumns();

    protected abstract function rowToView(array $row);

    protected abstract function valuesToInsert(object $object);

    protected abstract function tableName();

    public static function getTableName() : string
    {

        $instance = static::instance();

        $tableName = $instance->tableName();

        return $tableName;
    }

    public abstract static function instance();

    /**
     * Executes a query on the database
     * @param string $stmt the statment to be executed with optionally parametezied variables
     * @param array $params optional parameterized variables in statement order
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function executeQuery(string $stmt, array $params = [], ?string $databaseName = null) : Response
    {
        $genericResp = new Response(false, "Unknown error in executing sql query", null);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $result = Database::executeSqlQuery($stmt, $params);    

            if(is_bool($result))
            {
                if($result == true)
                {
                    $genericResp->success = true;
                    $genericResp->message = "Query Executed; Bool returned";
                    $genericResp->data = $result;
                }
                else
                {
                    $genericResp->message = "Query Failed; False returned";
                }
            }
            else
            {
                if($result->num_rows > 0)
                {
                    $genericResp->success = true;
                    $genericResp->message = "Query Executed; Result returned";
                    $genericResp->data = $result;
                }
                else
                {
                    $genericResp->success = true;
                    $genericResp->message = "Query Executed; Result returned empty";
                    $genericResp->data = $result;
                }
            }

            
        }
        catch(Exception $e)
        {
            $genericResp->message = "Execption caught while executing query : ".$e;
        }

        return $genericResp;
    }

     /**
     * Returns an view object of the retrieved object from the database
     * @param object $object object that should be retrieved from the database
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function get(object $object, ?string $databaseName = null) : Response
    {
        $instance = static::instance();

        $stmt = "SELECT ".$instance->allViewColumns()." FROM v_".$instance->tableName()." WHERE ctime = ? AND crand = ? LIMIT 1;";

        if($databaseName != null)
        {
            Database::changeDatabase($databaseName);
        }

        $params = [$object->ctime, $object->crand];

        $genericResp = new Response(false, "Unkown Error In Retrieving ".$instance->tableName().".".DatabaseController::printIdDebugInfo([$instance->tableName()=>$object]), null);

        try
        {
            $result = Database::executeSqlQuery($stmt, $params);

            if($result->num_rows > 0)
            {
                $row = $result->fetch_assoc();

                $foundObjectAsView = $instance->rowToView($row);

                $genericResp->success = true;
                $genericResp->message = $instance->tableName() . " Successfully Found";
                $genericResp->data = $foundObjectAsView;

            }
            else
            {
                $genericResp->message = $instance->tableName()." Not Found".DatabaseController::printIdDebugInfo([$instance->tableName()=>$object]);
            }
        }
        catch(Exception $e)
        {
            $genericResp->message = "Error In Executing SQL Query. ".DatabaseController::printIdDebugInfo([$instance->tableName()=>$object], $e);
        }

        return $genericResp;
    }

    /**
     * Returns an array of all matching rows for the table
     * @param array $columnNameValuePairs Associative array of the column name and the value it should match
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function getWhere(array $columnNameValuePairs, ?string $databaseName = null) : Response
    {
        $genericResp = new Response(false, "Unkown error in getting an entry where criteria matches", null);

        $instance = static::instance();

        $tableName = $instance->tableName();

        $whereClause = DatabaseController::columnNameValuePairsToSQLWhereClause($columnNameValuePairs);

        $stmt = "SELECT ".$instance->allViewColumns()." FROM v_".$tableName." WHERE ".$whereClause.";";

        $params = array_values($columnNameValuePairs);

        try
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $result = Database::executeSqlQuery($stmt, $params);

            try
            {
                $genericResp->message = "No entry found where criteria is met : ".implode(',',$columnNameValuePairs);
            }
            catch(Exception $e)
            {
                $genericResp->message = "No entry found where criteria is met and column name value pairs could not be reported due to exception : ".$e;
            }

            if($result->num_rows != 0)
            {
                $foundEntries = [];

                while($row = $result->fetch_assoc())
                {
                    $foundObjectAsView = $instance->rowToView($row);
                    
                    $foundEntries[] = $foundObjectAsView;
                }

                $genericResp->success = true;
                $genericResp->message = "Entry retrieved where criteria is met";
                $genericResp->data = $foundEntries;
            }
            else
            {
                $genericResp->success = true;
                $genericResp->message = "No Entries found where criteria is met : ".json_encode($columnNameValuePairs);
                $genericResp->data = [];
            }
        }
        catch(Exception $e)
        {
            $genericResp->message = "error in executing sql query to get entry where criteria is met : $e";
        }

        return $genericResp;
    }

    /**
     * Returns a boolean of the existence of the object in the database
     * @param object $object object that should be retrieved from the database
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function exists(object $object, ?string $databaseName = null) : Response
    {
        $instance = static::instance();

        $tableName = $instance->tableName();

        $stmt = "SELECT ctime FROM ".$tableName." WHERE ctime = ? and crand = ? LIMIT 1";

        $params = [$object->ctime, $object->crand];

        $genericResp = new Response(false, "Unkown Error In Checking If Cart Exists. ".DatabaseController::printIdDebugInfo([$tableName=>$object]), null);

        try 
        {
            if($databaseName != null)
            {
                Database::changeDatabase($databaseName);
            }

            $result = Database::ExecuteSqlQuery($stmt, $params);
            
            if($result->num_rows > 0)
            {
                $genericResp->success = true;
                $genericResp->message = $tableName." Exists";
                $genericResp->data = true;
            
            }
            else
            {
                $genericResp->success = true;
                $genericResp->message = $tableName." Does Not Exist";
                $genericResp->data = false;
            }
        } 
        catch (Exception $e) 
        {
            $genericResp->message = "Error In Executing Sql Query. ".DatabaseController::printIdDebugInfo([$tableName=>$object], $e);
        }

        return $genericResp;
    }

    /**
     * Removes a row matching the object in the database
     * @param object $object object that should be removed from the database
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function remove(vRecordId $object, ?string $databaseName = null) : Response
    {
        $instance = static::instance();

        $tableName = $instance->tableName();

        $stmt = "DELETE FROM ".$tableName." WHERE ctime = ? AND crand = ?;";
        $params = [$object->ctime, $object->crand];

        if($databaseName != null)
        {
            Database::changeDatabase($databaseName);
        }

        $genericResp = new Response(false, "Unkown Error In Deleting ".$tableName.". ".DatabaseController::printIdDebugInfo([$tableName=>$object]), null);

        try
        {
            Database::executeSqlQuery($stmt, $params);

            $genericExistsResp = static::exists($object);

            if($genericExistsResp->data == false)
            {
                $genericResp->success = true;
                $genericResp->message = $tableName." Removed";
            }
            else
            {
                $genericResp->message = $tableName." Not Removed. ".DatabaseController::printIdDebugInfo([$tableName=>$object]);
            }
        }
        catch(Exception $e)
        {
            $genericResp->message = "Error In Executing Sql Query To Remove ".$tableName.". ".DatabaseController::printIdDebugInfo([$tableName=>$object],$e);
        }

        return $genericResp;
    }

    /**
     * Inserts the object into the database
     * @param object $object object that should be inserted into the database
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function insert(object $object, ?string $databaseName = null) : Response
    {
        $instance = static::instance();

        $tableName = $instance->tableName();

        $params = $instance->valuesToInsert($object);

        $stmt = "INSERT INTO ".$tableName." (".$instance->allTableColumns().") VALUES (".DatabaseController::returnInsertQuestionMarks($instance->allTableColumns()).")";

        if($databaseName != null)
        {
            Database::changeDatabase($databaseName);
        }
        

        $genericResp = new Response(false, "Unkown Error In Adding ".$tableName.". ".DatabaseController::printIdDebugInfo([$tableName=>$object]), null);

        try
        {
            Database::executeSqlQuery($stmt, $params);

            $genericId = new vRecordId($object->ctime, $object->crand);
            $genericExistsResp = static::exists($object);

            if($genericExistsResp->data)
            {
                $genericResp->success = true;
                $genericResp->message = "Successfully Added ".$tableName;
                $genericResp->data = $object;
            }
            else
            {
                $genericResp->message = $tableName." Not Added. ".DatabaseController::printIdDebugInfo([$tableName=>$object]);
            }
        }
        catch(Exception $e)
        {
            $genericResp->message = "Error In Executing Sql To Add ".$tableName.". ".DatabaseController::printIdDebugInfo([$tableName=>$object],$e);
        }

        return $genericResp;
    }

    /**
     * Executes the prepared batch insertion in the controller
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function executeBatchInsertion(?string $databaseName = null) : Response
    {   
        $response = new Response(false, "Unknown error in executing prepared queries", null);

        $instance = static::instance();

        $batchInsertionParams = $instance->batchInsertionParams;

        try 
        {
            
            $stmt = static::buildInsertStmt();
            
            Database::executeBatchInsertion($stmt, $batchInsertionParams, $databaseName);
            $instance->batchInsertionParams = [];
            
            $response->success = true;
            $response->message = "Succesfully executed prepared queries";
        } 
        catch (Exception $e) 
        {
            $response->message = "Error in executing transaction to execute prepared queries. Exception : ".$e;
        }

        return $response;
    }

    /**
     * Adds the object to the list of batch insertions being prepared for the controller
     * @param string $databaseName optional explicit database assignment
     * @return Response 
     */
    public static function prepInsertForBatch(object $object) : Response
    {
        $instance = static::instance();

        $response = new Response(false, "Unknown error in preparing insert statment", null);

        $params =  $instance->valuesToInsert($object);
        
        $instance->batchInsertionParams[] = $params;

        $response->success = true;
        $response->message = "Insertion statement prepared";

        return $response;
    }
  
    private static function buildInsertStmt() : string
    {
        $instance = static::instance();

        $stmt = "INSERT INTO ".$instance->tableName().
            " ("
                .$instance->allTableColumns().
            ") VALUES ("
                .static::tableColumnsToQuestionMarks($instance->allTableColumns()).
            "); ";

        return $stmt;
    }

    private static function tableColumnsToQuestionMarks(string $tableColumnsString) : string
    {
        $tableColumnArray = static::tableColumnStringToArray($tableColumnsString);

        $questionMarkString = '?';

        for($i = 1; $i < count($tableColumnArray); $i++ )
        {
            $questionMarkString .= ',?';
        }

        return $questionMarkString;
    }

    private static function returnInsertQuestionMarks( string $columnsInTable) : string
    {
        $columnsInTable = explode(',',$columnsInTable);
        $return =  substr(str_repeat("?,",count($columnsInTable)),0,count($columnsInTable)*2-1);
        return $return;
    }

    private static function tableColumnStringToArray(string $tableColumnsString) :  array
    {
        return explode(',',$tableColumnsString);
    }

    private static function columnNameValuePairsToSQLWhereClause(array $columnNameValuePairs) : string
    {
        $sqlString = '';

        $columnNames = array_keys($columnNameValuePairs);

        for($i = 0; $i < count($columnNames); $i ++)
        {
            $sqlString = $sqlString." ".$columnNames[$i]." = ?";

            if($i != count($columnNames) - 1)
            {
                $sqlString = $sqlString." AND";
            }
        }

        return $sqlString;
    }

    /**
     * Returns a debug descriptive string of a supplied object, given its associated name ["{object name}"=>{object inheriting vRecordId}]
     * @param array $debugDict object to create the descriptive string
     * @param Exception $e optional exception to print with the description
     * @return string 
     */
    public static function printIdDebugInfo(array $debugDict, ?Exception $e = null) : string
    {
        $string = "";

        foreach($debugDict as $key => $value)
        {
            $ctime = $value->ctime;
            $crand = $value->crand;
        
            $string = $string." | ".$key."_ctime : ".$ctime." ".$key."_crand : ".$crand;
        }

        if(isset($e))
        {
            $string = $string." | With Exception : ".$e;
        }

        return $string;
    }
}
?>