<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_StatementDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseResult;
use Kickback\Common\Database\SQL\Drivers\Base\BaseRow;

/**
* @see \Kickback\Common\Database\SQL\SQL_ResultDetails
*
* @phpstan-type DRIVER_ID  DriverID::MySQLi
* @extends BaseResult<DRIVER_ID>
*/
abstract class KKMySQLi_ResultDetails extends BaseResult
{
    /**  @return DRIVER_ID  **/
    protected final function driver_id_definition() : int {
        return DriverID::MySQLi;
    }

    protected ?\mysqli_result $mysqli_result_;

    /**
    * @throws void
    * @return KKMySQLi_ConnectionDetails
    */
    #[KickbackGetter]
    public function connection() : KKMySQLi_ConnectionDetails {
        return $this->statement()->connection();
    }

    /**
    * @throws void
    * @return KKMySQLi_StatementDetails
    */
    #[KickbackGetter]
    public function statement() : KKMySQLi_StatementDetails {
        $stmt = parent::statement();
        assert($stmt instanceof KKMySQLi_StatementDetails);
        return $stmt;
    }

    /**
    * @param KKMySQLi_StatementDetails $statement
    */
    public function __construct(KKMySQLi_StatementDetails $statement, \mysqli_result $result)
    {
        $this->mysqli_result_ = $result;
        parent::__construct($statement);
    }

    /**
    * @see Kickback\Common\Database\SQL\SQL_ResultDetails::to_mysqli
    *
    * @throws void
    *
    * @param ?\mysqli_result $underlying_results_object
    *
    * @phpstan-assert-if-true  \mysqli_result $underlying_results_object
    * @phpstan-assert-if-false null           $underlying_results_object
    */
    public function to_mysqli(?\mysqli_result &$underlying_results_object) : bool {
        assert(!$this->disposed());
        $underlying_results_object = $this->mysqli_result_;
        return true;
    }

    public function free_memory() : void {
        assert(!$this->disposed());
        $this->mysqli_result_->free();
    }

    public function free_memory_supported() : bool {
        return true;
    }

    public function dispose() : void
    {
        $this->mysqli_result_ = null;
        parent::dispose();
    }

    /**
    * @phpstan-assert-if-true  null            $this->mysqli_result_
    * @phpstan-assert-if-false \mysqli_result  $this->mysqli_result_
    */
    public function disposed() : bool
    {
        return parent::disposed();
    }
}

?>
