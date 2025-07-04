<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers\MySQLi;

use Kickback\Common\Attributes\KickbackGetter;

use Kickback\Common\Database\SQL\Drivers\DriverID;

use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_DriverMetadataTrait;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ConnectionDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_StatementDetails;
use Kickback\Common\Database\SQL\Drivers\MySQLi\KKMySQLi_ResultDetails;
use Kickback\Common\Database\SQL\Drivers\Base\BaseRow;

use Kickback\Common\Database\SQL\Internal\SQL_ColumnAccessorSet;

// NOTE: regarding the `kkmysqli_column_types` list...
//
// MySQLi doesn't document its array type, so as of this writing,
// looking at `mysqli_result::fetch_assoc(...)` is not fruitful.
//
// However, MySQLi DOES document what it returns for _individual columns_,
// and that can be seen in the documentation for `mysqli_result::fetch_column(...)`:
//   https://www.php.net/manual/en/mysqli-result.fetch-column.
//
// So we assume that the `mysqli_result::fetch_column(...)` spans all
// possible PHP types returned from the `mysqli` driver,
// and list them in `kkmysqli_column_types`.
//
// (That function also returns `false`, but that's not
// a column type, that's just what it returns when it encounters an error.
// Hence, we omit that from our below list of possible array element types.)
//
/**
* @see \Kickback\Common\Database\SQL\SQL_Row
*
* @phpstan-import-type kksql_any_supported_type   from SQL_ColumnAccessorSet
*
* @phpstan-type kkmysqli_column_types   int|float|string
* @phpstan-type kkmysqli_ncolumn_types  null|kkmysqli_column_types
* @phpstan-type kkmysqli_native_row     array<int|string, kkmysqli_column_types>
* @phpstan-type kkmysqli_native_nrow    array<int|string, kkmysqli_ncolumn_types>
*
* @phpstan-import-type DRIVER_ID  from KKMySQLi_DriverMetadataTrait
* @extends BaseRow<DRIVER_ID>
*/
final class KKMySQLi_Row extends BaseRow
{
    use KKMySQLi_DriverMetadataTrait;

    /** @var ?kkmysqli_native_nrow */
    private ?array $row_;

    /**
    * @throws void
    * @return KKMySQLi_ConnectionDetails
    */
    #[KickbackGetter]
    public function connection() : KKMySQLi_ConnectionDetails {
        return $this->result()->connection();
    }

    /**
    * @throws void
    * @return KKMySQLi_StatementDetails
    */
    #[KickbackGetter]
    public function statement() : KKMySQLi_StatementDetails {
        return $this->result()->statement();
    }

    /**
    * @throws void
    * @return KKMySQLi_ResultDetails
    */
    #[KickbackGetter]
    public function result() : KKMySQLi_ResultDetails {
        $res = parent::result();
        assert($res instanceof KKMySQLi_ResultDetails);
        return $res;
    }

    public function __construct(KKMySQLi_ResultDetails $result)
    {
        $this->row_ = [];
        //$this->result_ = $result; // Assignment done in BaseRow
        //parent::__construct($result, new MySQLi_ColumnAccessorCommon($this->result()->to_mysqli())); // TODO! Need to write that class.
        parent::__construct($result, null);
    }

    /**
    * Avoid using this unless it is really necessary. This function has poorer
    * type-safety than accessing data directly from the `SQL_Row` object,
    * and this function requires an unnecessary memory allocation.
    *
    * This function converts the row into a PHP numeric and associative array.
    * In other words: the resulting array is indexed both by column number
    * and by column name.
    *
    * This is important in at least these 2 situations:
    * * When compatibility with other SQL APIs or abstraction layers is needed.
    * * When the row's contents must be stored past the current iteration of the result set.
    *     (e.g. before calling `$results->next()`)
    *
    * @throws void
    *
    * @return array<int|string, kksql_any_supported_type|null>
    */
    public function to_array() : array
    {
        assert(!$this->disposed());
        return $this->row_;
    }

    /**
    * Removes all data from the `SQL_Row` object without deallocating memory.
    */
    public function clear() : void
    {
        $this->row_ = [];
        // TODO: manage the common column accessor?
    }

    public function __toString() : string
    {
        if ( !$this->disposed() ) {
            $row_str = \implode($this->row_);
            return "MySQLi_RowImplementation$row_str";
        } else {
            return 'MySQLi_RowImplementation::DISPOSED';
        }
    }

    public function dispose() : void
    {
        $this->row_ = null;
        parent::dispose();
    }

    /**
    * @phpstan-assert-if-true  null                  $this->row_
    * @phpstan-assert-if-false kkmysqli_native_nrow  $this->row_
    */
    public function disposed() : bool
    {
        return parent::disposed();
    }
}


?>
