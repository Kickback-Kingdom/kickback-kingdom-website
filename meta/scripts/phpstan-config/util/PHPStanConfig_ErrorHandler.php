<?php
declare(strict_types=1);

require_once(__DIR__.'/PHPStanConfig_MessageOnlyException.php');

class PHPStanConfig_ErrorHandler
{
    public function msg(string $msg) : void {
        \fwrite(STDERR, $msg);
    }

    /**
    * @return never
    */
    public function abort(string $msg) : void {
        throw new PHPStanConfig_MessageOnlyException($msg);
    }
}