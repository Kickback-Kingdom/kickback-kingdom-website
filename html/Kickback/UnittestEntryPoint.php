<?php
declare(strict_types=1);

namespace Kickback;

use Kickback\Common\Traits\StaticClassTrait;

use Kickback\Common\IO\Terminal\ANSI;
use Kickback\Common\Primitives\Str;

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

    public static function unittests() : void
    {
        $gry = ANSI::color(ANSI::FG, ANSI::dWHITE);
        //$red = ANSI::color(ANSI::FG, ANSI::bRED);
        $grn = ANSI::color(ANSI::FG, ANSI::bGREEN);
        //$blu = ANSI::color(ANSI::FG, ANSI::bBLUE);
        //$cyn = ANSI::color(ANSI::FG, ANSI::bCYAN);
        $mag = ANSI::color(ANSI::FG, ANSI::bMAGENTA);
        $dmg = ANSI::color(ANSI::FG, ANSI::dMAGENTA);
        $ylw = ANSI::color(ANSI::FG, ANSI::bYELLOW);
        $wht = ANSI::color(ANSI::FG, ANSI::bWHITE);
        $res = ANSI::color(ANSI::RESET);

        echo "$res\n";

        // Check to make sure `zend.assertions=1`.
        // If it's anything else, then the assertions are turned off,
        // and running the unittests won't do anything.
        //
        // We'll use a more definitive test later where we actually
        // fail an assertion intentionally. That will be an even better
        // measurement of whether `assert` is working or not.
        //
        // But for now, we want to try and catch a known problem early,
        // so that we don't mislead ourselves with "running unittests"
        // messages when nothing is actually happening.
        // (Which is undesirable, even if we later print an error
        // explaining in retrospect that the results are invalid.)
        //
        $zend_assertions = null;
        $zend_assertions_str = ini_get('zend.assertions');
        $zend_assertions_str = filter_var($zend_assertions_str, FILTER_VALIDATE_INT);
        if ( $zend_assertions_str !== false ) {
            $zend_assertions = intval($zend_assertions_str);
            if ( $zend_assertions !== 1 ) {
                self::on_unittests_cant_run($zend_assertions);
                return;
            }
        } // If it didn't return an int, then we can't tell.
        //     For sake of durability, we'll assume everything is OK.

        echo "$mag===================". "=================================". "====================\n";
        echo "$mag---------------$ylw Running all Kickback Kingdom unittests. $mag----------------\n";
        echo "$dmg-------------------". "---------------------------------". "--------------------\n";
        echo "$res\n";

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
        echo "${gry}------------------------------------------------------------------------\n";
        echo "${wht}Now we will trigger an assertion to make sure assertions are turned on.\n";
        echo "${wht}(Or throw an exception if they aren't.)\n";
        echo "${res}\n";

        // It might seem odd that we'd check this at the end,
        // but I don't want to assume that we can "catch" AssertErrors.
        $setup_is_good = false;
        $goodmsg =
            "$gry----------". "----------------------------------------------------". "----------\n".
            "$wht--------->$grn   GOOD! Your `zend.assertions` is set correctly!   $wht<---------\n".
            "$gry----------". "----------------------------------------------------". "----------\n".
            "$res\n";
        try {
            echo "$gry";
            assert(false, $goodmsg);
            echo "$res";
        } catch (\AssertionError $e) { // Catch the assert to avoid spamming.
            echo($goodmsg); // If we successfully caught it, then we still need to print the message.
            $setup_is_good = true;
        }

        if (!$setup_is_good) {
            self::on_unittests_cant_run($zend_assertions);
            return;
        }

        echo "$mag===================". "=================================". "====================\n";
        echo "$mag-------------------$ylw Finished running ALL unittests. $mag--------------------\n";
        echo "$dmg-------------------". "---------------------------------". "--------------------\n";
        echo "$res\n";
    }

    private static function on_unittests_cant_run(?int $zend_assertions) : void
    {
        $res = ANSI::color(ANSI::RESET);

        $grn = ANSI::color(ANSI::FG,  ANSI::bGREEN);
        $cyn = ANSI::color(ANSI::FG,  ANSI::bCYAN);
        $mag = ANSI::color(ANSI::FG,  ANSI::bMAGENTA);
        $ylw = ANSI::color(ANSI::FG,  ANSI::bYELLOW);
        $wht = ANSI::color(ANSI::FG,  ANSI::bWHITE);
        //$dcyn = ANSI::color(ANSI::FG, ANSI::dCYAN);  // too dark; hard to read
        $dcyn = ANSI::color(ANSI::FG, [5,37]);   // brighter cyan
        //$dcyn = ANSI::color(ANSI::FG, [5,44]); // even brighter; maybe too bright; not enough contrast
        $bold  = ANSI::color(ANSI::BOLD);
        $bgred = ANSI::color(ANSI::BG,  ANSI::bRED);
        $bgdrd = ANSI::color(ANSI::BG,  ANSI::dRED);
        $flash = ANSI::color(
            ANSI::BG,
            ANSI::bRED,
            ANSI::BOLD,
            ANSI::REVERSE,
            ANSI::BLINK);

        $kickback_root = Str::normalize_path(\Kickback\InitializationScripts\SCRIPT_ROOT);
        $kickback_root = substr($kickback_root, 0, -strlen('/html'));
        $path_left_margin_to_box_right_margin_nchars = 59;
        if ( strlen($kickback_root) <= $path_left_margin_to_box_right_margin_nchars ) {
            $spaces_and_colon = str_repeat(' ',$path_left_margin_to_box_right_margin_nchars - strlen($kickback_root)) . "$mag:";
        } else {
            $spaces_and_colon = "$mag";
        }

        $mpz_nchars = 8; // (m)argin (p)adding for (z)end.assertions
        $mpz = str_repeat(' ',$mpz_nchars);
        $z = null;
        if ( isset($zend_assertions) )
        {
            if ( $zend_assertions > 99999 )  {
                $z = '>99999';
            } else
            if ( $zend_assertions < -9999 ) {
                $z = '<-9999';
            } else {
                // But it should really be -1, 0, or 1.
                $z = '='.strval($zend_assertions);
            }
            $mpz_nchars -= strlen($z);
            $mpz = str_repeat(' ',$mpz_nchars);
        }
        $rr = "$res$bgred";
        $r2 = "$res$bgdrd";
        echo "$bgred-------" .  "------------"  .   "----"."------"  .  "------------------------------------"."-------$res\n";
        echo "$bgred!!!!!!!$bgdrd            ${flash}BAD!$r2 ^o^  ${bold}Unittests DID NOT RUN!              $rr!!!!!!!$res\n";
        if (isset($zend_assertions) && $zend_assertions === 1 ) {
        echo "$bgred!!!!!!!$bgdrd"  .   "  (`zend.assertions=1` (good!)"  .  " BUT assert can't trip!)    "."$rr!!!!!!!$res\n";
        } else if ( isset($zend_assertions) && $zend_assertions !== 1 ) {
        echo "$bgred!!!!!!!$bgdrd"  .   "       (`zend.assertions$z`"  .  " turns assertions off.)  $mpz" . "$rr!!!!!!!$res\n";
        } else {
        echo "$bgred!!!!!!!$bgdrd"  .   "       (`zend.assertions`"  .  " may be set incorrectly.)        "."$rr!!!!!!!$res\n";
        }
        echo "$bgred-------" .  "------------"  .   "----"."------"  .  "------------------------------------"."-------$res\n";
        if ( !isset($zend_assertions) || $zend_assertions !== 1 ) {
        echo   "$mag:$wht       ". "                    ". "                                           $mag:\n";
        echo   "$mag:$wht You can ensure that unittests are enabled                            "  .   "$mag:\n";
        echo   "$mag:$wht by passing `-d zend.assertions=1` to your php command.               "  .   "$mag:\n";
        echo   "$mag:$wht Example:                                                             "  .   "$mag:\n";
        echo   "$mag:$cyn   cd$dcyn $kickback_root$cyn/html$spaces_and_colon\n";
        echo   "$mag:$cyn   php $grn-d zend.assertions=1$cyn scratch-pad/unittest.php                  $mag:\n";
        echo   "$mag:$wht       ". "                    ". "                                           $mag:\n";
        echo   "$mag-". "--". "--------------------". "------------------------------------------------$mag-\n";
        echo "$res\n";
        } else {
        echo "$res\n";
        }
    }
}
?>
