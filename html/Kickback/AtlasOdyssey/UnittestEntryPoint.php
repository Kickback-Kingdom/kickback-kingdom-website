<?php
declare(strict_types=1);

namespace Kickback\AtlasOdyssey;

use Kickback\Common\Traits\StaticClassTrait;

/**
* This class shall run all unittests in the \Kickback\AtlasOdyssey namespace
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

        echo ("STUB: ".__METHOD__." is missing unittest implementation(s).\n");
        // echo("===== Running all ".__NAMESPACE__." unittests =====\n\n");
        // \Kickback\AtlasOdyssey\AtlasDateTime::unittests();
        // \Kickback\AtlasOdyssey\Emberwood\EmberwoodTradingCargoship::unittests();
        // echo("----- Finished ".__NAMESPACE__." unittests -----\n\n\n");
    }
}
?>
