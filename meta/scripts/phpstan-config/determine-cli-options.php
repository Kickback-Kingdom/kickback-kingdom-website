<?php
declare(strict_types=1);
/**
* determine-cli-options.php
*
* This script does these things:
* * Scans path(s) provided by all `local-dir=` options (there can be more than one).
* * Looks for `opts.txt` or `phpstan-opts.txt` (with or without the .txt)
* * If not found, instead opens the file provided by the (mandatory) `defaults=` option.
* * Scans the file(s) line-by-line
* * Ignores empty lines and lines beginning with '#'
* * Concatenates all other lines into one (space-separated) output stream
* * The output is emitted to `stdout` by default.
* * If `output=` is specified, then all output is written into that file.
*
* Usage:
*   php determine-cli-options.php \
*     defaults=<some/dir/default-opts.txt> \
*     local-dir=<another/dir/phpstan-config> \
*     local-dir=<another/dir/something/phpstan-config> \
*     output=<output/dir/phpstan-opts.neon>
*/

// These simple config-automation scripts do not have an autoloader.
// So don't use the PHP `use` statement. Just use `require_once`
// to textually include the class that you need to use.
require_once(__DIR__.'/util/PHPStanConfig_ErrorHandler.php');
require_once(__DIR__.'/util/PHPStanConfig_FileSystem.php');
require_once(__DIR__.'/util/PHPStanConfig_Script.php');
require_once(__DIR__.'/util/PHPStanConfig_ScriptOutput.php');
require_once(__DIR__.'/util/PHPStanConfig_StringInterpolation.php');
require_once(__DIR__.'/util/PHPStanConfig_System.php');
require_once(__DIR__.'/util/PHPStanConfig_TextListScript.php');

class determine_cli_options extends PHPStanConfig_TextListScript
{
    //private static function debug_msg(string $s) : bool { return PHPStanConfig_System::debug_msg($s); }
    private static function echo_msg(string $s) : bool { return PHPStanConfig_System::echo_msg($s); }

    private const FILES_TO_CHECK = [
        'phpstan-opts',
        'phpstan-opts.txt',
        '$PHPSTAN_CONFIG_DIR/opts',
        '$PHPSTAN_CONFIG_DIR/opts.txt',
        '$PHPSTAN_CONFIG_DIR/phpstan-opts',
        '$PHPSTAN_CONFIG_DIR/phpstan-opts.txt'
    ];

    private int $total_populated_line_count = 0;

    /**
    * @param  array<string>  $args
    */
    #[\Override]
    protected function main(array $args) : void
    {
        $this->define_special_environment_variables();
        $this->parse_args($args);
        $resolved_paths = $this->resolve_paths(self::FILES_TO_CHECK);
        $this->output->open();
        $this->scope_exit(fn() => $this->output->close());
        $this->process_all_lines_in_all_files($resolved_paths);
    }

    /** @param array<string> $resolved_paths */
    private function process_all_lines_in_all_files(array $resolved_paths) : void
    {
        $this->total_populated_line_count = 0;
        foreach ($resolved_paths as $path) {
            $this->process_lines_with($path, $this->extract_phpstan_opts_from_line(...));
        }
    }

    // TODO: Call \getenv and provide $ENV_VAR and %ENV_VAR% substitution.
    // Also ensure that there is a $PHPSTAN_NEON_CONFIG_FILE variable always defined.
    private function extract_phpstan_opts_from_line(string $path, int $line_number, string $line_contents) : bool
    {
        $line = \trim($line_contents);
        if ( 0 === \strlen($line) || \str_starts_with($line, '#') ) {
            return true; // Ignore empty lines and comments
        }

        // We're placing everything into one line anyways,
        // so we may as well remove line-continuation tokens.
        if ( \str_ends_with($line, '\'') ) {
            $line = \substr($line, 0, \strlen($line)-1);
        }

        $line = PHPStanConfig_StringInterpolation::interpolate($this->env, $line);
        //$line = \str_replace('phpstan.neon', '/var/www/localhost/kickback-kingdom-website/meta/phpstan.neon', $line);

        $phpstan_opts_line = $line;
        if ( 0 < $this->total_populated_line_count ) {
            $this->output->fwrite(' ');
        }
        $this->output->fwrite($phpstan_opts_line);
        $this->total_populated_line_count++;

        return true;
    }

    private function define_special_environment_variables() : void
    {
        $psep = '/';
        $script_dir = $this->env['PHPSTAN_SCRIPT_DIRECTORY'];
        if (\str_contains($script_dir,'\\')) {
            $psep = '\\';
        }
        // We'll check two places:
        //   $project_dir/meta/phpstan.neon
        // and
        //   $project_dir/meta/phpstan-config/phpstan.neon
        //
        // I don't like hardcoding these,
        // but I'm not sure what to do.
        // The config needs to be rooted _somewhere_.
        $neon_config_basename = 'phpstan.neon';
        $neon_config_dir  = $script_dir;
        $neon_config_path = $neon_config_dir . $psep . $neon_config_basename;
        if (!\file_exists($neon_config_path)) {
            $neon_config_dir  = $script_dir . $psep . 'phpstan-config';
            $neon_config_path = $neon_config_dir . $psep . $neon_config_basename;
        }

        // Make these paths very unambiguous, and also reduce the
        // amount of syntax (ex: .. segments) that other parts
        // of the script pipeline will need to handle.
        $normalized_absolute_config_dir = \realpath($neon_config_dir);
        if ( $normalized_absolute_config_dir !== false ) {
            $neon_config_dir  = $normalized_absolute_config_dir;
            $neon_config_path = $neon_config_dir . $psep . $neon_config_basename;
        }

        // We can't double quote stuff being passed to PHPStan.
        // When the shell expands the argument list, the double quotes
        // will be treated as part of the name.
        $windoze = self::running_on_windows();
        $neon_config_dir      = self::escape_file_path($neon_config_dir, $windoze);
        $neon_config_basename = self::escape_file_path($neon_config_basename, $windoze);
        $neon_config_path     = self::escape_file_path($neon_config_path, $windoze);

        if (!\array_key_exists('PHPSTAN_NEON_CONFIG_DIRECTORY',$this->env)) {
            $this->env['PHPSTAN_NEON_CONFIG_DIRECTORY'] = $neon_config_dir;
        }
        if (!\array_key_exists('PHPSTAN_NEON_CONFIG_BASENAME',$this->env)) {
            $this->env['PHPSTAN_NEON_CONFIG_BASENAME']  = $neon_config_basename;
        }
        if (!\array_key_exists('PHPSTAN_NEON_CONFIG_PATH',$this->env)) {
            $this->env['PHPSTAN_NEON_CONFIG_PATH']      = $neon_config_path;
        }
    }

    private static function running_on_windows() : bool {
        static $running_on_windows_ = null;
        if(isset($running_on_windows_)) {
            return $running_on_windows_;
        }
        // https://stackoverflow.com/a/5879065
        // Note that some comments suggest that the directory separator
        // test can fail, so we also have a failover using PHP_OS.
        $running_on_windows_ = false;
        if (\DIRECTORY_SEPARATOR === '\\') {
            $running_on_windows_ = true;
        } else
        if (\strncasecmp(\PHP_OS, 'WIN', 3) === 0) {
            $running_on_windows_ = true;
        }
        return $running_on_windows_;
    }

    /**
    * Escape a file path WITHOUT using double quotes.
    *
    * This is helpful when we need to generate a string that will be
    * expanded in a shell and have paths remain valid.
    *
    * Double-quotes can't be used because shell expansion treats
    * them as actually part of the path.
    *
    * This explanation is all a bit basic and doesn't really cover
    * the perversity of the matter, but the point is, this function
    * complements various other shell/cmd hygeine efforts.
    *
    * Note that this does NOT escape spaces. That causes problems.
    * For ... REASONS.
    */
    private static function escape_file_path(string $path, bool $running_on_windows) : string
    {
        $len = \strlen($path);
        $cursor = 0;
        $output = '';

        // Check for '~' at the start of the path.
        if (!$running_on_windows && 0 < $len) {
            // Leading tilde is expandable to user's home.
            // This is expected behavior, and escaping it would be unexpected.
            if ( $path[0] === '~' ) {
                $cursor++;
                $output = '~';
            }
        }

        if ( $running_on_windows ) {
            $esc_char = '^';
        } else {
            $esc_char = '\\';
        }

        // Substitutions for the rest of the path.
        while(true)
        {
            // Scan for a special character.
            if ($running_on_windows) {
                // The '\' character is a path separator here, so we don't escape it.
                // Also the '$' character doesn't need escaping, and doing so will cause errors.
                // (Many of the others are currently untested.)
                $copy_len = strcspn($path,"%!&#;`|*?~<>^()[]{},\x0A\xFF'\"", $cursor);
            } else {
                $copy_len = strcspn($path,"\\!&#;`|*?~<>^()[]{}$,\x0A\xFF'\"", $cursor);
            }

            // Logic for handling already-partially-escaped things.
            // This is not advised for something like `PHPSTAN_NEON_CONFIG_PATH`
            // where it is guaranteed to not have any escaped characters
            // (if an escape sequence DOES appear in such a path, than those
            // characters are _literally_ in the path, it's not an escape sequence,
            // and each of them still needs to be escaped to survive shell shenanigans).
            // But if we are handling user text, like if we ever decide to
            // apply escaping to strings appearing in the `phpstan-opts.txt` files,
            // then it would be advised to look for already escaped characters. Maybe.
            //      vvvvvvvvvvv
            // // Scan for an (unescaped!) special character.
            // $peek = $cursor;
            // while(true) {
            //     if ($running_on_windows) {
            //         // The '\' character is a path separator here, so we don't escape it.
            //         // Also the '$' character doesn't need escaping, and doing so will cause errors.
            //         // (Many of the others are currently untested.)
            //         $copy_len = strcspn($path,"%!&#;`|*?~<>^()[]{},\x0A\xFF'\"", $peek);
            //     } else {
            //         $copy_len = strcspn($path,"\\!&#;`|*?~<>^()[]{}$,\x0A\xFF'\"", $peek);
            //     }
            //     $peek += $copy_len;
            //
            //     if ( 0 === $copy_len ) {
            //         // There can't be a preceding escape character.
            //         break;
            //     }
            //
            //     $ch = $path[$peek - 1];
            //     if ($ch === $esc_char) {
            //         // It's already escaped. Keep going.
            //         $peek++;
            //         continue;
            //     }
            //
            //     // No preceding escape character.
            //     break;
            // }
            // $copy_len = $peek - $cursor;

            // Append everything up to that character to the output.
            $output .= \substr($path,$cursor,$copy_len);
            $cursor += $copy_len;

            // Scanning halted due to end-of-string, not a special character.
            if ( $cursor === $len ) {
                break;
            }

            // Append an escape character.
            $output .= $esc_char;

            // Append the special character.
            $output .= \substr($path,$cursor,1);
            $cursor++;
        }

        return $output;
    }


    private static function unittest_escape_file_path() : void
    {
        self::echo_msg ("  ".__FUNCTION__."()\n");
        $windows = true;
        $unix = !$windows; // @phpstan-ignore booleanNot.alwaysFalse

        assert(self::escape_file_path('', $unix),    '');
        assert(self::escape_file_path('', $windows), '');
        assert(self::escape_file_path('foo', $unix),    'foo');
        assert(self::escape_file_path('foo', $windows), 'foo');
        assert(self::escape_file_path('/x/y/foo b@r b$z/nani.txt', $unix),        '/x/y/f% b@r b\$z/nani.txt');
        assert(self::escape_file_path('\\x\\y\\foo b@r b$z\\nani.txt', $unix),    '\\\\x\\\\y\\\\f% b@r b\\\\$z/nani.txt');
        assert(self::escape_file_path('/x/y/foo b@r b$z/nani.txt', $windows),     '^/x^/y^/f^% b@r b$z^/nani.txt'); // Unix slashes on Windows might be a lost cause, but we'll make an attempt.
        assert(self::escape_file_path('\\x\\y\\foo b@r b$z\\nani.txt', $windows), '\\x\\y\\f^% b@r b$z\\nani.txt');
    }

    public static function unittests() : void
    {
        if ( !PHPStanConfig_System::$do_unittesting ) {
            return;
        }

        $class_fqn = self::class;
        self::echo_msg("Running `$class_fqn::unittests()`\n");

        self::unittest_escape_file_path();

        self::echo_msg("  ... passed.\n\n");
    }
}

determine_cli_options::unittests();

$script = new determine_cli_options(\getenv());
$script->run($argv); // Which indirectly calls `determine_cli_options->main($argv)`
