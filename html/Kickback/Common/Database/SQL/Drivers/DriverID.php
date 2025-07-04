<?php
declare(strict_types=1);

namespace Kickback\Common\Database\SQL\Drivers;

use Kickback\Common\StaticClassTrait;

final class DriverID
{
    use StaticClassTrait;

    public const PDO        = 0;
    public const ODBC       = 1;
    public const MySQLi     = 2;
    public const PgSql      = 3;
    public const INVALID    = \PHP_INT_MAX;

    public static function to_prefix(int $id) : string
    {
        switch($id)
        {
            case self::PDO:     return 'PDO';
            case self::ODBC:    return 'ODBC';
            case self::MySQLi:  return 'MySQLi';
            case self::PgSql:   return 'PgSql';

            case self::INVALID:
            default:
                return 'INVALID';
        }
    }

    public static function to_name(int $id) : string
    {
        switch($id)
        {
            case self::PDO:    return 'PHP Data Objects';
            case self::ODBC:   return 'Open Database Connectivity';
            case self::MySQLi: return 'MySQL Improved';
            case self::PgSql:  return 'PostrgeSQL';

            case self::INVALID:
            default:
                return 'INVALID';
        }
    }

    public static function to_fullname(int $id) : string
    {
        if ($id === self::INVALID) {
            return 'INVALID';
        }

        $prefix = self::to_prefix($id);
        $name = self::to_name($id);
        return "$prefix - $name";
    }
}

?>
