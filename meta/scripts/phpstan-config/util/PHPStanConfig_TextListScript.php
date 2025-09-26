<?php
declare(strict_types=1);

require_once(__DIR__.'/PHPStanConfig_ErrorHandler.php');
require_once(__DIR__.'/PHPStanConfig_FileSystem.php');
require_once(__DIR__.'/PHPStanConfig_Script.php');
require_once(__DIR__.'/PHPStanConfig_ScriptOutput.php');

abstract class PHPStanConfig_TextListScript extends PHPStanConfig_Script
{
    private string $defaults_file = '';
    private int    $defaults_count = 0;

    /** @var array<string> */
    private array  $local_dirs = [];

    /**
    * @param  array<string>  $args
    */
    protected function parse_args(array $args) : void
    {
        $argc = \count($args);
        for($i = 1; $i < $argc; $i++) {
            $this->parse_single_arg($i, $args);
        }

        if ($this->defaults_count > 1) {
            $this->err->msg("Warning: multiple defaults= parameters detected. Using the last one.\n");
        }

        $this->output->validate_args();

        if (0 === $this->defaults_count) {
            $this->err->abort("Error: at least one defaults=... argument is required.\n");
        }
    }

    /**
    * @param  array<string>  $args
    */
    private function parse_single_arg(int $i, array $args) : void
    {
        $arg = $args[$i];

        if (\str_starts_with($arg, 'local-dir=')) {
            $this->local_dirs[] = \substr($arg, 10);
            return;
        }

        if (\str_starts_with($arg, 'defaults=')) {
            $this->defaults_count++;
            $this->defaults_file = \substr($arg, 9);
            return;
        }

        if ($this->output->parse_arg($i, $arg, $args)) {
            return;
        }

        $this->err->abort("Error: unknown argument '$arg'. ".
            "Only defaults=..., local-dir=..., and output=... are allowed.\n");
    }

    /**
    * @param   array<string>  $files_to_check
    * @return  array<string>
    */
    protected function resolve_paths(array $files_to_check) : array
    {
        $fs = $this->fs;
        $defaults_file = \rtrim($this->defaults_file, '/\\ ');
        $resolved_path = '';
        if (!$fs->check_file_access($defaults_file, $resolved_path)) {
            $this->err->abort(
                "Error: 'defaults' file '$defaults_file' is not accessible.\n".
                "       Unable to proceed. Aborting.\n");
        }

        $resolved_paths = [];
        foreach ($this->local_dirs as $local_dir)
        {
            $local_dir = \rtrim($local_dir, '/\\ ');
            $scratch_root = \dirname($local_dir);
            $phpstan_config_basedir = \basename($local_dir);
            assert(0 < \strlen($phpstan_config_basedir));

            if (!\file_exists($scratch_root)) {
                continue;
            }

            $resolved_path = '';
            if (!$fs->check_dir_access($scratch_root, $resolved_path)) {
                continue;
            }

            foreach($files_to_check as $rel_path)
            {
                $rel_dir = \dirname($rel_path);
                $basename = \basename($rel_path);
                if ( $rel_dir === '$PHPSTAN_CONFIG_DIR' ) {
                    $rel_path = $phpstan_config_basedir . '/' . $basename;
                }

                $path = $scratch_root . '/' . $rel_path;
                if (!\file_exists($path)) {
                    continue;
                }

                $resolved_path = '';
                if (!$fs->check_file_access($path, $resolved_path)) {
                    continue;
                }

                $resolved_paths[] = $resolved_path;
            }
        }

        // Use defaults if we couldn't find any overrides.
        if (0 === \count($resolved_paths)) {
            $resolved_paths = [$defaults_file];
        }

        return $resolved_paths;
    }

    /**
    * @param  \Closure(string,int,string):bool   $line_processor
    */
    protected function process_lines_with(string $file_path, \Closure $line_processor) : void
    {
        if (!is_file($file_path)) {
            return;
        }

        // Note: We aren't using SplFileObject because it doesn't
        //         have a `close` method. (why?!)
        $fh = \fopen($file_path, 'r');
        if ($fh === false) {
            $this->err->msg("Warning: could not open file: '$file_path'\n");
            return;
        }

        try {
            $this->process_file($file_path, $fh, $line_processor);
        } finally {
            \fclose($fh);
        }
    }

    /**
    * @param  resource                           $fh
    * @param  \Closure(string,int,string):bool   $line_processor
    */
    private function process_file(string $file_path, mixed $fh, \Closure $line_processor) : void
    {
        $early_return = false;
        $line_number = 0;
        $line = '';
        while(($line = \fgets($fh)) !== false)
        {
            $line_number++;
            if ( !$line_processor($file_path, $line_number, $line) ) {
                $early_return = true;
                break;
            }
        }

        if (!$early_return && !\feof($fh)) {
            $line_str = \strval($line_number);
            $this->err->msg("Warning: an I/O error occurred while reading from this file: '$file_path'\n");
            if ( 0 < $line_number ) {
            $this->err->msg("         $line_str lines were read successfully before the error.\n");
            } else {
            $this->err->msg("         Error occurred while attempting to read the first line of the file.\n");
            }
        }
    }
}