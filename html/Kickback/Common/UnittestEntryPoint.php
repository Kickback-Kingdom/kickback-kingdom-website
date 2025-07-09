<?php
declare(strict_types=1);

namespace Kickback\Common;

use Kickback\Common\Traits\StaticClassTrait;

/**
* This class shall run all unittests in the \Kickback\Common namespace
* (including all sub-namespaces).
*/
class UnittestEntryPoint
{
    use StaticClassTrait;

    public static function unittests() : void
    {
        // Sort order:
        // * Dependency order as a priority
        // * Alphabetic when packages are peers

        echo("===== Running all ".__NAMESPACE__." unittests =====\n\n");
        \Kickback\Common\Primitives\Meta::unittests();
        \Kickback\Common\Primitives\Mixed_::unittests(); // Depends on Common\Primitives\Meta
        \Kickback\Common\Traits\ClassOfConstantIntegers::unittests(); // Depends on Common\Primitives\Mixed_ and/or Int_.
        echo("----- Finished ".__NAMESPACE__." unittests -----\n\n\n");
    }
}
?>
