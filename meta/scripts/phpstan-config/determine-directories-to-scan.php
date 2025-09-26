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

exit(0);

// $files_to_check = [
//     'phpstan-paths',
//     'phpstan-paths.txt',
//     '$PHPSTAN_CONFIG_DIR/paths',
//     '$PHPSTAN_CONFIG_DIR/paths.txt',
//     '$PHPSTAN_CONFIG_DIR/phpstan-paths',
//     '$PHPSTAN_CONFIG_DIR/phpstan-paths.txt'
// ];
//
// require_once(__DIR__.'/util/fs.php');
//
// $is_cli = false;
// if (PHP_SAPI === 'cli'
// ||  PHP_SAPI === 'phpdbg'
// ||  \defined('STDIN'))
// {
//     $is_cli = true;
// }
//
// if (!$is_cli) {
//     \fwrite(STDERR, "This script must be run from the command line.\n");
//     exit(1);
// }
//
// $defaults_file = '';
// $defaults_count = 0;
// $local_dirs = [];
// $output_file = null;
// $output_count = 0;
//
// // --- Parse arguments ---
// foreach (\array_slice($argv, 1) as $arg)
// {
//     if (\str_starts_with($arg, 'local-dir=')) {
//         $local_dirs[] = \substr($arg, 10);
//     }
//     else
//     if (\str_starts_with($arg, 'defaults=')) {
//         $defaults_count++;
//         $defaults_file = \substr($arg, 9);
//     }
//     else
//     if (\str_starts_with($arg, 'output=')) {
//         $output_count++;
//         $output_file = \substr($arg, 7);
//     }
//     else {
//         \fwrite(STDERR, "Error: unknown argument '$arg'. Only defaults=..., local-dir=..., and output=... are allowed.\n");
//         exit(1);
//     }
// }
//
// if ($defaults_count > 1) {
//     \fwrite(STDERR, "Warning: multiple defaults= parameters detected. Using the last one.\n");
// }
//
// if ($output_count > 1) {
//     \fwrite(STDERR, "Warning: multiple output= parameters detected. Using the last one.\n");
// }
//
// if (0 === $defaults_count) {
//     \fwrite(STDERR, "Error: at least one defaults=... argument is required.\n");
//     exit(1);
// }
//
// // --- Resolve paths ---
// $resolved_path = '';
// if (!fs_check_file_access($defaults_file, $resolved_path)) {
//     \fwrite(STDERR, "Error: 'defaults' file '$defaults_file' is not accessible.\n");
//     \fwrite(STDERR, "       Unable to proceed. Aborting.\n");
//     exit(1);
// }
//
// $resolved_paths = [];
// foreach ($local_dirs as $local_dir)
// {
//     $scratch_root = \dirname($local_dir);
//     $phpstan_config_basedir = \basename($local_dir);
//     assert(0 < \strlen($phpstan_config_basedir));
//
//     if (!\file_exists($scratch_root)) {
//         continue;
//     }
//
//     $resolved_path = '';
//     if (!fs_check_dir_access($scratch_root, $resolved_path)) {
//         continue;
//     }
//
//     foreach($files_to_check as $rel_path)
//     {
//         $rel_dir = \dirname($rel_path);
//         $basename = \basename($rel_path);
//         if ( $rel_dir === '$PHPSTAN_CONFIG_DIR' ) {
//             $rel_path = $phpstan_config_basedir . '/' . $basename;
//         }
//
//         $path = $scratch_root . '/' . $rel_path;
//         if (!\file_exists($path)) {
//             continue;
//         }
//
//         $resolved_path = '';
//         if (!fs_check_file_access($path, $resolved_path)) {
//             continue;
//         }
//
//         $resolved_paths[] = $resolved_path;
//     }
// }
//
// // Use defaults if we couldn't find any overrides.
// if (0 === \count($resolved_paths)) {
//     $resolved_paths = [$defaults_file];
// }
//
// // --- Open output stream ---
// if (isset($output_file))
// {
//     $output_dir = \dirname($output_file);
//     if ( !\file_exists($output_dir) ) {
//         $success = \mkdir($output_dir, 0777, true);
//         if (!$success) {
//             \fwrite(STDERR, "Output file's directory didn't exist, and mkdir failed: $output_dir\n");
//             exit(1);
//         }
//     }
//
//     $out = \fopen($output_file, 'w');
//     if ($out === false) {
//         \fwrite(STDERR, "Unable to open output file: $output_file\n");
//         exit(1);
//     }
// } else {
//     $out = STDOUT;
// }
//
// // --- Write neon header ---
// \fwrite($out, "parameters:\n");
// \fwrite($out, "    paths:\n");
//
// // --- Scan paths ---
// /**
// * @param  resource  $fh
// * @param  resource  $out
// */
// function extract_phpstan_target_paths_inner(string $path, mixed $fh, mixed $out) : void
// {
//     $line_number = 0;
//     $line = '';
//     while(($line = \fgets($fh)) !== false)
//     {
//         $line_number++;
//         $line = \trim($line);
//         if ( 0 === \strlen($line) || \str_starts_with($line, '#') ) {
//             continue; // Ignore empty lines and comments
//         }
//
//         $path = $line;
//         $phpstan_scan_target = \realpath($path);
//         if ($phpstan_scan_target === false) {
//             \fwrite(STDERR, "Warning: could not normalize path: '$path'\n");
//             return;
//         }
//         \fwrite($out, "        - $phpstan_scan_target\n");
//     }
//
//     if (!\feof($fh)) {
//         $line_str = \strval($line_number);
//         \fwrite(STDERR, "Warning: an I/O error occurred while reading from this file: '$path'\n");
//         if ( 0 < $line_number ) {
//         \fwrite(STDERR, "         $line_str lines were read successfully before the error.\n");
//         } else {
//         \fwrite(STDERR, "         Error occurred while attempting to read the first line of the file.\n");
//         }
//     }
// }
//
// /**
// * @param  resource  $out
// */
// function extract_phpstan_target_paths(string $path, mixed $out) : void
// {
//     if (!is_file($path)) {
//         return;
//     }
//
//     // Note: We aren't using SplFileObject because it doesn't
//     //         have a `close` method. (why?!)
//     $fh = \fopen($path, 'r');
//     if ($fh === false) {
//         \fwrite(STDERR, "Warning: could not open file: '$path'\n");
//         return;
//     }
//
//     try {
//         extract_phpstan_target_paths_inner($path, $fh, $out);
//     } finally {
//         \fclose($fh);
//     }
// }
//
//
// foreach ($resolved_paths as $path) {
//     extract_phpstan_target_paths($path, $out);
// }
//
// // --- Cleanup ---
// if (isset($output_file)) {
//     \fclose($out);
// } else {
//     \fflush($out); // Just in case.
// }
//
// exit(0);
