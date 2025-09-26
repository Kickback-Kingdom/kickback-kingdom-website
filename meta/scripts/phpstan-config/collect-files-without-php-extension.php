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

// These simple config-automation scripts do not have an autoloader.
// So don't use the PHP `use` statement. Just use `require_once`
// to textually include the class that you need to use.
require_once(__DIR__.'/util/PHPStanConfig_ErrorHandler.php');
require_once(__DIR__.'/util/PHPStanConfig_FileSystem.php');
require_once(__DIR__.'/util/PHPStanConfig_Script.php');
require_once(__DIR__.'/util/PHPStanConfig_ScriptOutput.php');

class collect_files_without_php_extension extends PHPStanConfig_Script
{
    /** @var array<string> */
    private array $paths = [];

    /**
    * @param  array<string>  $args
    */
    #[\Override]
    protected function main(array $args) : void
    {
        $this->parse_args($args);
        $resolved_paths = $this->resolve_paths();
        $this->output->open();
        $this->scope_exit(fn() => $this->output->close());
        $this->scan_for_php_files_within_roots($resolved_paths);
    }

    /**
    * @param  array<string>  $args
    */
    protected function parse_args(array $args) : void
    {
        $argc = \count($args);
        for($i = 1; $i < $argc; $i++) {
            $this->parse_single_arg($i, $args);
        }

        $this->output->validate_args();

        if (0 === \count($this->paths)) {
            $this->err->abort("Error: at least one path=... argument is required.\n");
        }
    }

    /**
    * @param  array<string>  $args
    */
    private function parse_single_arg(int $i, array $args) : void
    {
        $arg = $args[$i];

        if (\str_starts_with($arg, 'path=')) {
            $this->paths[] = \substr($arg, 5);
            return;
        }

        if ($this->output->parse_arg($i, $arg, $args)) {
            return;
        }

        $this->err->abort("Error: unknown argument '$arg'. Only path=... and output=... are allowed.\n");
    }

    /**
    * @return  array<string>
    */
    private function resolve_paths() : array
    {
        $fs = $this->fs;
        $resolved_paths = [];
        foreach ($this->paths as $path)
        {
            $real = '';
            if ($fs->check_dir_access($path, $real)) {
                $resolved_paths[] = $real;
            }
        }

        if (0 === \count($resolved_paths)) {
            $this->err->abort("Error: no valid paths provided.\n");
        }

        return $resolved_paths;
    }

    /** @param array<string> $resolved_paths */
    private function scan_for_php_files_within_roots(array $resolved_paths) : void
    {
        // --- Write neon header ---
        $this->output->fwrite("parameters:\n");
        $this->output->fwrite("    paths:\n");

        // --- Scan paths ---
        foreach ($resolved_paths as $path)
        {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($it as $file) {
                $this->collect_if_php($file);
            }
        }
    }

    private function collect_if_php(\SplFileInfo $file) : void
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
            $this->err->msg("Warning: could not open file: '$opath'\n");
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
            $this->err->msg("Warning: could not normalize path: '$opath'\n");
            return;
        }

        $this->output->fwrite("        - $path\n");
    }


}

$script = new collect_files_without_php_extension(\getenv());
$script->run($argv); // Which indirectly calls `collect_files_without_php_extension->main($argv)`

exit(0);
