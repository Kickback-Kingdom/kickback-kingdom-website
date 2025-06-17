<?php
declare(strict_types=1);

namespace Kickback\Common\Database;
/*
*/

// /**
// * @method bool    bool(string $column_name)
// * @method bool    bool(string $column_name, bool $new_value)
// * @method int     int(string $column_name)
// * @method int     int(string $column_name, int $new_value)
// * @method string  str(string $column_name)
// * @method string  str(string $column_name, string $new_value)
// * @method string  string(string $column_name)
// * @method string  string(string $column_name, string $new_value)
// * @method float   float(string $column_name)
// * @method float   float(string $column_name, float $new_value)
// */
interface RowInterface
{
    public function bool(  string $column_name, bool ...$newValue  ) : bool;
    public function int(   string $column_name, int ...$newValue   ) : int;
    public function str(   string $column_name, string ...$newValue) : string;
    public function string(string $column_name, string ...$newValue) : string;
    public function float( string $column_name, float ...$newValue ) : float;

    public function nbool(  string $column_name, ?bool ...$newValue  ) : ?bool;
    public function nint(   string $column_name, ?int ...$newValue   ) : ?int;
    public function nstr(   string $column_name, ?string ...$newValue) : ?string;
    public function nstring(string $column_name, ?string ...$newValue) : ?string;
    public function nfloat( string $column_name, ?float ...$newValue ) : ?float;
}

?>
