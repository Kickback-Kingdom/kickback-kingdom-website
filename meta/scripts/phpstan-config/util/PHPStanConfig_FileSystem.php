<?php
declare(strict_types=1);

require_once(__DIR__.'/PHPStanConfig_ErrorHandler.php');

class PHPStanConfig_FileSystem
{
    private PHPStanConfig_ErrorHandler $err;

    public function __construct(PHPStanConfig_ErrorHandler $err)
    {
        $this->err = $err;
    }

    public function check_access(string $path, string &$normalized_path) : bool
    {
        if ( \strlen($path) === 0 || $path === '.' || $path === '..' ) {
            return false;
        }

        if (!\file_exists($path)) {
            $this->err->msg("Warning: path '$path' does not exist.\n");
            return false;
        }

        $real = \realpath($path);
        if ($real === false) {
            $this->err->msg("Warning: could not normalize path: '$path'\n");
            return false;
        }

        $normalized_path = $real;
        return true;
    }

    public function check_file_access(string $path, string &$normalized_path) : bool
    {
        $tmp_path = '';
        $res = $this->check_access($path, $tmp_path);
        if ( !$res ) {
            return false;
        }

        if (!\is_file($tmp_path)) {
            $this->err->msg("Warning: path '$tmp_path' is not a file.\n");
            return false;
        }

        $normalized_path = $tmp_path;
        return true;
    }

    public function check_dir_access(string $path, string &$normalized_path) : bool
    {
        $tmp_path = '';
        $res = $this->check_access($path, $tmp_path);
        if ( !$res ) {
            return false;
        }

        if (!\is_dir($tmp_path)) {
            $this->err->msg("Warning: path '$tmp_path' is not a file.\n");
            return false;
        }

        $normalized_path = $tmp_path;
        return true;
    }
}