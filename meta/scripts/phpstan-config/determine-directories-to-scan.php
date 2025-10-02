<?php
declare(strict_types=1);
/**
* determine-directories-to-scan.php
*
* This script does these things:
* * Scans path(s) provided by all `local-dir=` options (there can be more than one).
* * Looks for `paths.txt` or `phpstan-paths.txt` (with or without the .txt)
* * If found, places all non-empty lines that don't start with '#' into a list
* * Emits a .neon config file having that list as its `paths:` element.
* * If not found, looks for the file given by the `defaults=` option.
* * From that file, places all non-empty lines that don't start with '#' into a list
* * In either case, the .neon file is emitted to `stdout` by default.
* * If `output=` is speecified, the .neon config is written to that file.
*
* Usage:
*   php determine-directories-to-scan.php \
*     defaults=<some/dir/default-paths.txt> \
*     local-dir=<another/dir/phpstan-config> \
*     local-dir=<another/dir/something/phpstan-config> \
*     output=<output/dir/phpstan-paths.neon>
*/

// These simple config-automation scripts do not have an autoloader.
// So don't use the PHP `use` statement. Just use `require_once`
// to textually include the class that you need to use.
require_once(__DIR__.'/util/PHPStanConfig_ErrorHandler.php');
require_once(__DIR__.'/util/PHPStanConfig_FileSystem.php');
require_once(__DIR__.'/util/PHPStanConfig_Script.php');
require_once(__DIR__.'/util/PHPStanConfig_ScriptOutput.php');
require_once(__DIR__.'/util/PHPStanConfig_TextListScript.php');

class determine_directories_to_scan extends PHPStanConfig_TextListScript
{
    private const FILES_TO_CHECK = [
        'phpstan-paths',
        'phpstan-paths.txt',
        '$PHPSTAN_CONFIG_DIR/paths',
        '$PHPSTAN_CONFIG_DIR/paths.txt',
        '$PHPSTAN_CONFIG_DIR/phpstan-paths',
        '$PHPSTAN_CONFIG_DIR/phpstan-paths.txt'
    ];

    /**
    * @param  array<string>  $args
    */
    #[\Override]
    protected function main(array $args) : void
    {
        $this->parse_args($args);
        $resolved_paths = $this->resolve_paths(self::FILES_TO_CHECK);
        $this->output->open();
        $this->scope_exit(fn() => $this->output->close());
        $this->process_all_lines_in_all_files($resolved_paths);
    }

    /** @param array<string> $resolved_paths */
    private function process_all_lines_in_all_files(array $resolved_paths) : void
    {
        // Write neon header
        $this->output->fwrite("parameters:\n");
        $this->output->fwrite("    paths:\n");

        // Transfer input lines into neon lines.
        foreach ($resolved_paths as $path) {
            $this->process_lines_with($path, $this->extract_phpstan_paths_from_line(...));
        }
    }

    private function extract_phpstan_paths_from_line(string $txt_file_path, int $line_number, string $line_contents) : bool
    {
        $line = \trim($line_contents);
        if ( 0 === \strlen($line) || \str_starts_with($line, '#') ) {
            return true; // Ignore empty lines and comments
        }

        $phpstan_path = $line;
        $phpstan_scan_target = \realpath($phpstan_path);
        if ($phpstan_scan_target === false) {
            $this->err->msg("Warning: could not normalize path: '$phpstan_path'\n");
            return true;
        }
        $this->output->fwrite("        - $phpstan_scan_target\n");
        return true;
    }
}

$script = new determine_directories_to_scan(\getenv());
$script->run($argv); // Which indirectly calls `determine_directories_to_scan->main($argv)`
