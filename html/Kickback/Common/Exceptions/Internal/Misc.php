<?php
declare(strict_types=1);

namespace Kickback\Common\Exceptions\Internal;

use Kickback\Common\Traits\StaticClassTrait;

/**
* @internal
*/
final class Misc
{
    use StaticClassTrait;

    /**
    * @phpstan-pure
    * @throws void
    */
    public static function calculate_message_line_prefix(
        ?string $path,  ?int $line
    ) : string
    {
        if ( isset($path) && 0 < \strlen($path) ) {
            $sep_pos = \strrpos($path, '/');
            if ( $sep_pos !== false ) {
                $basename = \substr($path, $sep_pos+1);
            } else {
                $basename = $path;
            }
        } else {
            $basename = null;
        }

        $line_str = ((isset($line) && $line !== 0) ? \strval($line) : null);
        if ( isset($basename) && isset($line_str) ) {
            $loc_full = "$basename($line_str): ";
        } else
        if ( isset($basename) /* && !isset($line_str) */ ) {
            $loc_full = "$basename: ";
        } else
        if ( /*!isset($basename) && */ isset($line_str) ) {
            $loc_full = "($line_str): ";
        } else
        { // !isset($basename) && !isset($line_str)
            $loc_full = '';
        }

        return $loc_full;
    }
}
?>
