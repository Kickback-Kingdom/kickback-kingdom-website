<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_DriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseStatement;

/**
* @see \Kickback\Common\Database\SQL\SQL_StatementDetails
*
* @phpstan-import-type DRIVER_ID  from KKMySQLi_DriverMetadataTrait
* @extends BaseStatement<DRIVER_ID>
*/
abstract class KKMySQLi_StatementDetails extends BaseStatement
{
    use KKMySQLi_DriverMetadataTrait;

    protected ?\mysqli_stmt $mysqli_stmt_;

    /**
    * @throws void
    * @return KKMySQLi_ConnectionDetails
    */
    #[KickbackGetter]
    public function connection() : KKMySQLi_ConnectionDetails {
        $conn = parent::connection();
        assert($conn instanceof KKMySQLi_ConnectionDetails);
        return $conn;
    }

    public function prepare(string  $query) : void
    {
        assert(!$this->disposed());
        if ( !$this->mysqli_stmt_->prepare($query) ) {
            // TODO: Better error reporting!
            // (This should really log details about the query itself,
            // but also avoid sharing that in the exception.)
            throw new \Exception('Failed to prepare query.');
        }
    }


    /**
    * @param KKMySQLi_ConnectionDetails $connection
    */
    public function __construct(KKMySQLi_ConnectionDetails $connection,  \mysqli_stmt $mysqli_stmt)
    {
        parent::__construct($connection);
        $this->common_init($mysqli_stmt);
    }

    public function initialize(KKMySQLi_ConnectionDetails $connection,  \mysqli_stmt $mysqli_stmt) : void
    {
        $this->attach($connection);
        $this->common_init($mysqli_stmt);
    }

    private function common_init(\mysqli_stmt $mysqli_stmt) : void
    {
        $this->mysqli_stmt_ = $mysqli_stmt;
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
        assert(!$this->disposed());
        $underlying_statement = $this->mysqli_stmt_;
        return true;
    }

    public function close() : bool
    {
        assert(!$this->disposed());
        // TODO: Invariant checking and stuff.
        return $this->mysqli_stmt_->close();
    }


    public function dispose() : void
    {
        // TODO: call `dispose()` on any currently acquired results object. Then nullify the reference.
        // TODO: What should this do exactly?

        $this->close();
        $this->mysqli_stmt_ = null;
        parent::dispose();
    }

    /**
    * @phpstan-assert-if-true  null          $this->mysqli_stmt_
    * @phpstan-assert-if-false \mysqli_stmt  $this->mysqli_stmt_
    */
    public function disposed() : bool
    {
        return parent::disposed();
    }
}

?>
