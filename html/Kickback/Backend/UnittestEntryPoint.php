<?php
declare(strict_types=1);

namespace Kickback\Backend;

use Kickback\Common\Traits\StaticClassTrait;

/**
* This class shall run all unittests in the \Kickback\Backend namespace
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
        \Kickback\Backend\Services\RankedMatchCalculator::unittests();
        \Kickback\Backend\Views\vDateTime::unittests();
        // TODO: Move unittests from `\Kickback\Common\Unittesting\Tests\vDateTime` to `vDateTime` class and call from here.
        // TODO: Move unittests from `\Kickback\Common\Unittesting\Tests\vDecimal` to `vDecimal` class and call from here.
        echo("----- Finished ".__NAMESPACE__." unittests -----\n\n\n");
    }
}
?>
