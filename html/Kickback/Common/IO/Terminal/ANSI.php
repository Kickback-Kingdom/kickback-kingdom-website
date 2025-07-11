<?php
declare(strict_types=1);

namespace Kickback\Common\IO\Terminal;

use Kickback\Common\Primitives\Arr;




/**
* Class housing ANSI escape-sequence handling code.
*/
final class ANSI
{
    use \Kickback\Common\Traits\StaticClassTrait;

    /**
    * Generates an escape sequence from the given color codes.
    *
    * @param  int|int[]  $codes
    */
    public static function color(int|array ...$codes) : string
    {
        return self::ESC . implode(';', array_map('strval', Arr::flatten($codes))) . 'm';
    }

    /** Code that introduces an ANSI escape sequence into terminal output. */
    public const ESC = "\x1B[";

    /** Resets ANSI (color) attributes. */
    public const RESET = 0;

    /**
    * Sets the "bold" or "increased intensity" text attribute.
    */
    public const BOLD = 1;

    /**
    * Sets the "faint" or "decreased intensity" or "dim" text attribute.
    */
    public const FAINT = 2;

    /**
    * Sets the "blinking" text attribute.
    *
    * This is the "slow" blinking code, 5.
    * The "fast" blinking code is "not widely supported".
    * https://en.wikipedia.org/w/index.php?title=ANSI_escape_code&oldid=1291641590
    */
    public const BLINK = 5;

    /**
    * Sets the "reverse video" text attribute.
    */
    public const REVERSE = 7;

    /**
    * Set's normal intensity, e.g. it unsets either the BOLD or FAINT text attributes.
    */
    public const RESET_INTENSITY = 22;

    // TODO: Do these persist?
    // Either way, how standard is the behavior in the 2nd assignment and beyond?

    /** Selects foreground for the next ANSI color attribute assignment. */
    public const FG = 38;

    /** Selects background for the next ANSI color attribute assignment. */
    public const BG = 48;

    // NOTE: Using 4-bit colors can cause variance in different terminals.
    // Meanwhile, the 8-bit|256-color palette specifies EXACTLY what color to display.
    // (Without assuming a 24-bit color terminal.)
    //
    // This is why we use the 8-bit codes, even if they are a bit longer.

    // (Though for "BLACK", there is only one possibility,
    // so we can use the abbreviated color code for that.)

    /**
    * Black, foreground mode, using the short color sequence "30"
    */
    public const FG_BLACK = 30;

    /**
    * Black, background mode, using the short color sequence "40"
    */
    public const BG_BLACK = 40;

    // NOTE: The "dark" colors are supposed to be 0x80 in their respective
    // "ON" channels, but testing shows that it doesn't always work
    // (ex: Konsole sets it to 0xFFFFFF for "dark" white).
    //
    // So we use the more specific palette colors and the problem is solved,
    // but there are no 0x80 channels available. 0x87 is pretty close, though,
    // so we use that instead.
    /**
    * Black, as specified using the color sequence "5;0"
    *
    * 5 -> "256-color palette"
    * 0 -> #000000 | "black"
    */
    public const dBLACK = [5,0];

    /**
    * Dark-ish red, as specified using the color sequence "5;88"
    *
    * 5 -> "256-color palette"
    * 88 -> #870000 | "red"
    */
    public const dRED = [5,88];

    /**
    * Dark-ish green, as specified using the color sequence "5;28"
    *
    * 5 -> "256-color palette"
    * 28 -> #008700 | "green"
    */
    public const dGREEN = [5,28];

    /**
    * Dark-ish blue, as specified using the color sequence "5;18"
    *
    * 5 -> "256-color palette"
    * 18 -> #000087 | "blue"
    */
    public const dBLUE = [5,18];

    /**
    * Dark-ish cyan (teal-ish color), as specified using the color sequence "5;30"
    *
    * 5 -> "256-color palette"
    * 30 -> #008787 | "cyan"
    */
    public const dCYAN = [5,30];

    /**
    * Dark-ish magenta, as specified using the color sequence "5;90"
    *
    * 5 -> "256-color palette"
    * 90 -> #870087 | "magenta"
    */
    public const dMAGENTA = [5,90];

    /**
    * Dark-ish yellow, as specified using the color sequence "5;100"
    *
    * 5 -> "256-color palette"
    * 100 -> #878700 | "yellow"
    */
    public const dYELLOW = [5,100];

    /**
    * "White", though more of a bright gray, as specified using the color sequence "5;102"
    *
    * 5 -> "256-color palette"
    * 102 -> #878787 | "(dark) white"
    */
    public const dWHITE = [5,102];

    /**
    * Bright red, as specified using the color sequence "5;196"
    *
    * 5 -> "256-color palette"
    * 196 -> #ff0000 | "bright red"
    */
    public const bRED = [5,196];

    /**
    * Bright green, as specified using the color sequence "5;46"
    *
    * 5 -> "256-color palette"
    * 46 -> #00ff00 | "bright green"
    */
    public const bGREEN = [5,46];

    /**
    * "Bright" blue, as specified using the color sequence "5;21"
    *
    * 5 -> "256-color palette"
    * 21 -> #0000ff | "bright blue"
    *
    * Note that, as usual for 0x0000FF, this is a somewhat deep and slightly dark
    * shade of blue as far as color-perception goes. Consider using a "whiter"
    * color if you want the same contrast against darker colors as you would
    * with GREEN or RED.
    */
    public const bBLUE = [5,21];

    /**
    * "Bright" blue, as specified using the color sequence "5;51"
    *
    * 5 -> "256-color palette"
    * 51 -> #00ffff | "bright cyan"
    */
    public const bCYAN = [5,51];

    /**
    * "Bright" magenta, as specified using the color sequence "5;201"
    *
    * 5 -> "256-color palette"
    * 201 -> #00ffff | "bright magenta"
    */
    public const bMAGENTA = [5,201];

    /**
    * "Bright" yellow, as specified using the color sequence "5;226"
    *
    * 5 -> "256-color palette"
    * 226 -> #00ffff | "bright yellow"
    */
    public const bYELLOW = [5,226];

    /**
    * Bright white, as specified using the color sequence "5;231"
    */
    public const bWHITE = [5,231];
}
?>
