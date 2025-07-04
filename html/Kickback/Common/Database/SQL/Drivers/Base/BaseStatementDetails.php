<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\Base;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;
use Kickback\Common\Database\SQL\Drivers\Base\BaseDriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\Base\BaseConnectionDetails;
use Kickback\Common\Database\SQL\SQL_Statement;

/**
* @see \Kickback\Common\Database\SQL\SQL_StatementDetails
*
* @template DRIVER_ID of DriverID::*
* @implements SQL_Statement<DRIVER_ID>
*/
abstract class BaseStatementDetails implements SQL_Statement
{
    /** @use BaseDriverMetadataTrait<DRIVER_ID> */
    use BaseDriverMetadataTrait;

    /** @var ?BaseConnectionDetails<DRIVER_ID> */
    private ?BaseConnectionDetails $connection_;

    /**
    * @throws void
    * @return BaseConnectionDetails<DRIVER_ID>
    */
    #[KickbackGetter]
    public function connection() : BaseConnectionDetails {
        assert(!$this->disposed());
        return $this->connection_;
    }

    /**
    * @param BaseConnectionDetails<DRIVER_ID> $connection
    */
    public function __construct(BaseConnectionDetails $connection)
    {
        // This assignment is required for `BaseDriverMetadataTrait` to work.
        $this->driver_id_value = $this->driver_id_definition();

        // Although this line is similar in function to the `attach` method,
        // we don't use `attach` here because that method assumes that
        // the $this->connection_ variable has already been assigned
        // and is non-null. So the constructor has to be a bit special
        // and different because it is fulfilling the not-null guarantee
        // in $this->connection_'s type-signature.
        $this->connection_ = $connection;
    }

    /**
    * @param BaseConnectionDetails<DRIVER_ID> $new_conn
    */
    public function attach(BaseConnectionDetails $new_conn) : bool
    {
        if ( $this->connection_ === $new_conn ) {
            // Tell the caller that there were no changes because the
            // same Connection object was already attached.
            return false;
        }

        // // At this point, we have confirmed that the statement object is
        // // being moved from one connection to another.
        // //
        // // Now we check to ensure that both connections belong to the same
        // // SQL driver.
        // //
        // // This actually guarantees more than just what's literally stated above:
        // //
        // // The "both from same driver" relationship has transitive property!
        // //
        // // So as long as this is the only place we can move between connections,
        // // we'll know that EVERY previous driver is also the same driver as
        // // $new_conn's driver, all the way back to the original!
        // //
        // // And because the original driver is the only source of its own
        // // SQL_Statement objects, and because it can't (by contract) make
        // // different SQL_Statement objects, then any SQL_Statement+SQL_Connection
        // // combo that passes this check will be guaranteed to be a valid match.
        // // (And any that doesn't pass the check is guaranteed to be invalid,
        // // and we can throw an exception to prevent Bad Things from happening.)
        // //
        // // TODO: This is fragile in the hypothetical case where a driver
        // // has different types of connection objects, but they are interchangeable
        // // within the same driver. They would have different classnames and
        // // fail this check, even though they should pass because they came
        // // from the same driver. (Perhaps we should add a `driver_string()`
        // // property to every object so we can identify compatibility more
        // // easily? Or maybe an enum?)
        // //
        // $this_conn_driver_id = $this_conn->driver_id_value;
        // $new_conn_driver_id = $new_conn->driver_id_value;
        // if ( $this_conn_driver_id !== $new_conn_driver_id ) {
        //     $this_driver_id_fullname = DriverID::to_fullname($this->driver_id_value);
        //     $new_conn_driver_id_fullname  = DriverID::to_fullname($new_conn_driver_id);
        //     throw new ???Exception(
        //         "Invalid attaching of an SQL_Statement (with driver: $this_driver_id_fullname) ".
        //         "to an SQL_Connection from a different driver! ($new_conn_driver_id_fullname)  ".
        //         "Statement class: $this_conn_classname;  Connection class: $new_conn_classname;");
        // }
        //
        // // Now that we have our mathematical certainty,
        // // we just do a simple assignment.
        // $this->connection_ = $new_conn;

        // Ignore the above.
        // Driver IDs make the check much simpler!
        if ( $this->driver_id_value !== $new_conn->driver_id_value ) {
            $this->error__connection_has_wrong_driver($new_conn); // throws
        }

        // We have driver agreement; do the thing.
        $this->connection_ = $new_conn;

        // Ensure that the Connection is aware of this new association.
        // (This must go last, because `acquire` will call `attach`,
        // so we need $this->connection_ to already be assigned so
        // that the already-done guard will prevent infinite recursion.)
        $this->connection_->acquire($this); // TODO: implement

        // Tell the caller that we mutated the Statement object.
        return true;
    }

    /**
    * @param BaseConnectionDetails<DRIVER_ID> $conn
    * @return never
    */
    private function error__connection_has_wrong_driver(BaseConnectionDetails $conn) : void
    {
        $this_driver_id_fullname = DriverID::to_fullname($this->driver_id_value);
        $conn_driver_id_fullname = DriverID::to_fullname($conn->driver_id_value);
        $this_classname = get_class($this);
        $conn_classname = get_class($conn);
        throw new \InvalidArgumentException(
            "Invalid attaching of an SQL_Statement (with driver: $this_driver_id_fullname) ".
            "to an SQL_Connection from a different driver! ($conn_driver_id_fullname)  ".
            "Statement class: $this_classname;  Connection class: $conn_classname;");
    }

    /**
    * @see Kickback\Common\Database\SQL\SQL_StatementDetails::to_mysqli
    *
    * @throws void
    *
    * @param ?\mysqli_stmt $underlying_statement
    *
    * @phpstan-assert-if-true  \mysqli_stmt $underlying_statement
    * @phpstan-assert-if-false null         $underlying_statement
    */
    public function to_mysqli(?\mysqli_stmt &$underlying_statement) : bool {
        $underlying_statement = null;
        return false;
    }

    private bool  $disposed_ = false;
    public function dispose() : void
    {
        $this->disposed_ = true;
        $this->connection_ = null;
    }

    /**
    * @phpstan-assert-if-true  null                   $this->connection_
    * @phpstan-assert-if-false BaseConnectionDetails  $this->connection_
    */
    public function disposed() : bool
    {
        return $this->disposed_;
    }
}

?>
