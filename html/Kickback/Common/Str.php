<?php
declare(strict_types=1);

namespace Kickback\Common;

/**
* As of this writing, this class just exists to silence the PHPStan error about `empty` being not allowed.
*/
final class Str
{
    /**
    * A type-safe alternative to the `empty` builtin.
    *
    * This can be used to make PHPStan stop complaining about
    * `empty($some_string)` being "not allowed" and telling us
    * to "use a more strict comparison".
    */
    public static function empty(?string $x) : bool
    {
        return !isset($x) || (0 === strlen($x));
    }

    // Prevent instantiation/construction of the (static/constant) class.
    /** @return never */
    private function __construct() {
        throw new \Exception("Instantiation of static class " . get_class($this) . "is not allowed.");
    }
}
?>
