<?php
declare(strict_types=1);
/**
* collect-files-without-php-extension.php
*
* This script does these things:
* * Scans path(s) provided by `path=` options.
* * Looks for files that contain '<?php' but do not end with ".php".
* * Adds those files to a list.
* * Emits a .neon config file having that list as its `paths:` element.
* * The .neon file is emitted to `stdout` by default.
* * If `output=` is speecified, the .neon config is written to that file.
*
* Usage:
*   php collect-files-without-php-extension.php path=foo path=bar output=output.neon
*/

$is_cli = false;
if (PHP_SAPI === 'cli'
||  PHP_SAPI === 'phpdbg'
||  \defined('STDIN'))
{
    $is_cli = true;
}

if (!$is_cli) {
    \fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$paths = [];
$output_file = null;
$output_count = 0;

// --- Parse arguments ---
foreach (\array_slice($argv, 1) as $arg)
{
    if (\str_starts_with($arg, 'path=')) {
        $paths[] = \substr($arg, 5);
    }
    else
    if (\str_starts_with($arg, 'output=')) {
        $output_count++;
        $output_file = \substr($arg, 7);
    }
    else {
        \fwrite(STDERR, "Error: unknown argument '$arg'. Only path=... and output=... are allowed.\n");
        exit(1);
    }
}

if ($output_count > 1) {
    \fwrite(STDERR, "Warning: multiple output= parameters detected. Using the last one.\n");
}

if (0 === \count($paths)) {
    \fwrite(STDERR, "Error: at least one path=... argument is required.\n");
    exit(1);
}

// --- Resolve paths ---
$resolved_paths = [];
foreach ($paths as $path)
{
    if (!\file_exists($path)) {
        \fwrite(STDERR, "Warning: path '$path' does not exist.\n");
        continue;
    }

    $real = \realpath($path);
    if ($real === false) {
        \fwrite(STDERR, "Warning: could not normalize path: '$path'\n");
        continue;
    }

    if (!\is_dir($real)) {
        \fwrite(STDERR, "Warning: path '$path' is not a directory.\n");
        continue;
    }

    $resolved_paths[] = $real;
}

if (0 === \count($resolved_paths)) {
    \fwrite(STDERR, "Error: no valid paths provided.\n");
    exit(1);
}

// --- Open output stream ---
if (isset($output_file))
{
    $output_dir = \dirname($output_file);
    if ( !\file_exists($output_dir) ) {
        $success = \mkdir($output_dir, 0777, true);
        if (!$success) {
            \fwrite(STDERR, "Output file's directory didn't exist, and mkdir failed: $output_dir\n");
            exit(1);
        }
    }

    $out = \fopen($output_file, 'w');
    if ($out === false) {
        \fwrite(STDERR, "Unable to open output file: $output_file\n");
        exit(1);
    }
} else {
    $out = STDOUT;
}

// --- Write neon header ---
\fwrite($out, "parameters:\n");
\fwrite($out, "    paths:\n");

// --- Scan paths ---
/**
* @param  resource  $out
*/
function process_file(\SplFileInfo $file, mixed $out) : void
{
    if (!$file->isFile()) {
        return;
    }

    if ($file->getExtension() === 'php') {
        return;
    }

    $opath = $file->getPathname();
    $fh = \fopen($opath, 'r');
    if ($fh === false) {
        \fwrite(STDERR, "Warning: could not open file: '$opath'\n");
        return;
    }

    $is_php = false;
    try
    {
        // We only check the 1st kB of file contents.
        // This really should be near the top of the file,
        // and if it isn't, we could be reading some kind of
        // data payload or other not-great-to-be-scanning thing,
        // which could take a while.
        // So everyone will just have to put it near the top of the file.
        // We can expand this number if it's really needed,
        // or switch to another strategy if this becomes a problem.
        $file_contents = \fread($fh, 1024);
        if ($file_contents !== false
        &&  \strpos($file_contents, '<?php') !== false) {
            $is_php = true;
        }
    }
    finally
    {
        \fclose($fh);
    }

    if (!$is_php) {
        return;
    }

    $path = \realpath($opath);
    if ($path === false) {
        \fwrite(STDERR, "Warning: could not normalize path: '$opath'\n");
        return;
    }

    \fwrite($out, "        - $path\n");
}


foreach ($resolved_paths as $path)
{
    $it = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
    );

    /** @var SplFileInfo $file */
    foreach ($it as $file) {
        process_file($file, $out);
    }
}

// --- Cleanup ---
if (isset($output_file)) {
    \fclose($out);
} else {
    \fflush($out); // Just in case.
}

exit(0);
