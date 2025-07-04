<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_DriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\Base\BaseConnection;


/**
* @see \Kickback\Common\Database\SQL\SQL_ConnectionDetails
*
* @phpstan-import-type DRIVER_ID  from KKMySQLi_DriverMetadataTrait
* @extends BaseConnection<DRIVER_ID>
*/
abstract class KKMySQLi_ConnectionDetails extends BaseConnection
{
    use KKMySQLi_DriverMetadataTrait;

    protected ?\mysqli $mysqli_;

    public function __construct(\mysqli $mysqli_conn) {
        $this->mysqli_ = $mysqli_conn;
        parent::__construct();
    }

    /**
    * @see Kickback\Common\Database\SQL\SQL_ConnectionDetails::to_mysqli
    *
    * @throws void
    *
    * @param ?\mysqli $underlying_connection
    *
    * @phpstan-assert-if-true  \mysqli $underlying_connection
    * @phpstan-assert-if-false null    $underlying_connection
    */
    public function to_mysqli(?\mysqli &$underlying_connection) : bool {
        assert(!$this->disposed());
        $underlying_connection = $this->mysqli_;
        return true;
    }

    public function close() : bool
    {
        assert(!$this->disposed());

        // TODO: Invariant checking and stuff.
        return $this->mysqli_->close();
    }

    public function dispose() : void
    {
        // TODO: What should this do exactly?
        $this->close();
        $this->mysqli_ = null;

        // foreach over all statements that this connection "owns"
        // call `dispose()`/detach on all of them. Then nullify the references.
        parent::dispose();
    }

    /**
    * @phpstan-assert-if-true  null     $this->mysqli_
    * @phpstan-assert-if-false \mysqli  $this->mysqli_
    */
    public function disposed() : bool
    {
        return parent::disposed();
    }
}

?>
