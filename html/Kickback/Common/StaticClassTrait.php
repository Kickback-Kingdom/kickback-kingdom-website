<?php
declare(strict_types=1);

namespace Kickback\Common;

/**
* Use this trait in classes that aren't supposed to be instantiated.
*
* Such classes are usually used to define static functions (like free-functions,
* but with better scoping behavior), or are used to define lists of constants
* or flags.
*
* Don't forget to also mark such classes as `final`. ;)
*/
trait StaticClassTrait
{
    // Prevent instantiation/construction of the (static/constant) class.
    /** @return never */
    private function __construct() {
        throw new \Exception("Instantiation of static class " . get_class($this) . "is not allowed.");
    }
}
?>
