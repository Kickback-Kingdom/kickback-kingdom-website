<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_DriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_Statement;
use Kickback\Common\Database\SQL\SQL_Statement;

/**
* @see \Kickback\Common\Database\SQL\SQL_ConnectionDetails
*
* @phpstan-import-type DRIVER_ID  from KKMySQLi_DriverMetadataTrait
*/
final class KKMySQLi_Connection extends KKMySQLi_ConnectionDetails
{
    /**
    * @param     ?SQL_Statement<DRIVER_ID>  $statement
    * @param-out KKMySQLi_Statement         $statement
    */
    public function prepare(?SQL_Statement &$statement, string $query) : void
    {
        assert(!$this->disposed());

        $mysqli_stmt_ = $this->mysqli_->prepare($query);
        if ( $mysqli_stmt_ === false ) {
            // TODO: Better error reporting!
            // (This should really log details about the query itself,
            // but also avoid sharing that in the exception.)
            throw new \Exception('Failed to prepare query.');
        }

        if ( !isset($statement) ) {
            $statement = new KKMySQLi_Statement($this, $mysqli_stmt_);
        } else {
            assert($statement instanceof KKMySQLi_Statement);
            $statement->initialize($this, $mysqli_stmt_);
        }

        //if ( !isset($statement) ) {
        //    $statement = KKMySQLi_Statement::something_something($this, $query);
        //} else {
        //    if ($statement->attach($this)) {
        //        // we need to give it a new \mysqli_stmt object.
        //    } else {
        //        // it already has the existing \mysqli_stmt object.
        //    }
        //    // bigger picture: where does the \mysqli_stmt object come from?
        //    // Even if the statement object already had one, wouldn't we still
        //    // replace it with a new one formed from the query?
        //    // So then wouldn't the assignment be unconditional? Hmmmm.
        //    // And since we might need to acquire \mysqli_stmt objects from
        //    // within the context of KKMySQLi_Connection, how do we
        //    // factor out any common code from the two different methods
        //    // of preparing a statement (ex: $conn->prepare vs $stmt->prepare)?
        //    // And other ways of generating statements? (ex: $conn->execute?)
        //    // Or skipping the statement step entirely ($conn->execute?)?
        //
        //    // This can work but ends up goofy:
        //    $statement->prepare($query);
        //    // ... because then the statement->prepare code needs to
        //    // use the connection backreference to obtain the \mysqli (connection)
        //    // object with which to call `prepare()`; not unless it has
        //    // a working \mysqli object to begin with (if we just attached
        //    // it to a different connection, then NO, it doesn't).
        //}

    }
}

?>
