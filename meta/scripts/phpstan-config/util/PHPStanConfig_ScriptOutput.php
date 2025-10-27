<?php
declare(strict_types=1);

require_once(__DIR__.'/PHPStanConfig_ErrorHandler.php');

class PHPStanConfig_ScriptOutput
{
    private PHPStanConfig_ErrorHandler $err;
    private ?string $output_path = null;
    private int     $output_arg_count = 0;
    private bool    $have_opened_file = false;

    /** @var ?resource */
    private mixed  $file_handle = null;

    public function __construct(PHPStanConfig_ErrorHandler $err)
    {
        $this->err = $err;
    }

    /**
    * @param  array<string>  $args
    */
    public function parse_arg(int $i, string $arg, array $args) : bool
    {
        if (!\str_starts_with($arg, 'output=')) {
            return false;
        }

        $this->output_arg_count++;
        $this->output_path = \substr($arg, 7);
        $this->output_path = \rtrim($this->output_path, '/\\ ');
        return true;
    }

    public function validate_args() : void
    {
        if ($this->output_arg_count > 1) {
            $this->err->msg("Warning: multiple output= parameters detected. Using the last one.\n");
        }
    }

    public function fwrite(string $msg) : void
    {
        if ( isset($this->file_handle) ) {
            \fwrite($this->file_handle, $msg);
            return;
        }
        \fwrite(\STDOUT, $msg);
    }

    public function ftruncate(int $to_size) : void
    {
        if ( isset($this->file_handle) ) {
            \ftruncate($this->file_handle, $to_size);
            return;
        }
    }

    public function open() : void
    {
        if (!isset($this->output_path)) {
            $this->file_handle = \STDOUT;
            return;
        }

        $output_path = $this->output_path;
        $output_dir = \dirname($output_path);
        if ( !\file_exists($output_dir) ) {
            $success = \mkdir($output_dir, 0777, true);
            if (!$success) {
                $this->err->abort("Output file's directory didn't exist, and mkdir failed: $output_dir\n");
            }
        }

        $out = \fopen($output_path, 'w');
        if ($out === false) {
            $this->err->abort("Unable to open output file: $output_path\n");
        }

        $this->have_opened_file = true;
        $this->file_handle = $out;
    }

    /** @return true */
    public function close() : bool
    {
        if (isset($this->file_handle))
        {
            if ($this->have_opened_file) {
                \fclose($this->file_handle);
            } else {
                \fflush($this->file_handle); // Just in case.
            }
        }
        else
        {
            \fflush(\STDOUT);
        }
        return true;
    }
}