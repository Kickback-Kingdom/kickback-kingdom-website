<?php
declare(strict_types=1);

namespace Kickback;

use Kickback\Common\Traits\StaticClassTrait;

/**
* This class shall run ALL unittests in the \Kickback namespace.
*
* To run the unittests:
* * Make this file %PROJECT_ROOT%/html/scratch-pad/unittest.php
* * Put these contents into it:
* ```
* require_once(($_SERVER["DOCUMENT_ROOT"] ?: (__DIR__ . "/..")) . "/Kickback/init.php");
* \Kickback\UnittestEntryPoint::unittests();
* ```
* * Then run commands like these (Linux commands shown) :
* ```
* cd %PROJECT_ROOT%/html
* php -d zend.assertions=1 scratch-pad/unittest.php
* ```
*/
class UnittestEntryPoint
{
    use StaticClassTrait;

    /**
    * @return never
    */
    public static function unittests() : void
    {
        // Sort order:
        // * Dependency order as a priority
        // * Alphabetic when packages are peers

        // Dependencies:
        // NONE (please keep it that way!)
        // (Well, technically the init bootstrap might be a dependency,
        // but that would require a separate unittesting methodology regardless.)
        \Kickback\Common\UnittestEntryPoint::unittests();

        // Dependencies:
        // * \Kickback\Services
        \Kickback\Services\UnittestEntryPoint::unittests();

        // Dependencies:
        // * \Kickback\Common
        // * \Kickback\Services
        \Kickback\Backend\UnittestEntryPoint::unittests();

        // Dependencies:
        // * \Kickback\Common
        // * \Kickback\Services
        // * \Kickback\Backend (maybe? Ideally: NO. Because they'd both depend on a separate API package. But we don't have that right now.)
        \Kickback\Frontend\UnittestEntryPoint::unittests();

        // Dependencies:
        // * \Kickback\Common
        // * \Kickback\Services
        // * \Kickback\Backend (maybe? Ideally: NO. Because they'd both depend on a separate API package. But we don't have that right now.)
        \Kickback\AtlasOdyssey\UnittestEntryPoint::unittests();

        echo "\n";
        echo "==================================================\n";
        echo "-------- Finished running ALL unittests. ---------\n";
        echo "--------------------------------------------------\n";
        echo "\n";
        echo "Now we will trigger an assertion just to make sure assertions are turned on.\n";
        echo "(Or throw an exception if they aren't.)\n";
        echo "...\n";
        echo "\n";
        assert(false, "\n".
            "------------------------------------------------------------\n".
            "--->   GOOD! Your `zend.assertions` is set correctly!   <---\n".
            "------------------------------------------------------------\n".
            "\n");

        throw new \Exception( "\n".
            "------------------------------------------------------------\n".
            "!!!!!!!       BAD! ^o^  Unittests DID NOT RUN!       !!!!!!!\n".
            "!!!!!!!  (`zend.assertions` may be set incorrectly.) !!!!!!!\n".
            "------------------------------------------------------------\n".
            ":                                                          :\n".
            ": You can ensure that unittests are enabled                :\n".
            ": by passing `-d zend.assertions=1` to your php command.   :\n".
            ": Example:                                                 :\n".
            ":   cd [YOUR_KICKBACK_ROOT]/html                           :\n".
            ":   php -d zend.assertions=1 scratch-pad/unittest.php      :\n".
            ":                                                          :\n".
            "------------------------------------------------------------\n".
            "\n");
    }
}
?>
