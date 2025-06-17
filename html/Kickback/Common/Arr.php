<?php
declare(strict_types=1);

namespace Kickback\Common;

/**
* This class originally existed to silence the PHPStan error about `empty` being not allowed.
*/
final class Arr
{
    /**
    * A type-safe alternative to the `empty` builtin.
    *
    * This can be used to make PHPStan stop complaining about
    * `empty($some_array)` being "not allowed" and telling us
    * to "use a more strict comparison".
    *
    * @param array<mixed>|array<string,mixed> $x
    */
    public static function empty(?array $x) : bool
    {
        return !isset($x) || (0 === count($x));
    }

    /**
    * @param array<mixed>|array<string,mixed> $var
    */
    public static function is_longer_than(?array $var, int $minLength) : bool
    {
        return !is_null($var) && count($var) >= $minLength;
    }

    // Prevent instantiation/construction of the (static/constant) class.
    /** @return never */
    private function __construct() {
        throw new \Exception("Instantiation of static class " . get_class($this) . "is not allowed.");
    }
}
?>
