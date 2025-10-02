<?php
declare(strict_types=1);

require_once(__DIR__.'/PHPStanConfig_ErrorHandler.php');
require_once(__DIR__.'/PHPStanConfig_FileSystem.php');
require_once(__DIR__.'/PHPStanConfig_MessageOnlyException.php');
require_once(__DIR__.'/PHPStanConfig_ScriptOutput.php');

abstract class PHPStanConfig_Script
{
    private const SCOPE_EXIT    = 0;
    private const SCOPE_SUCCESS = 1;
    private const SCOPE_FAILURE = 2;

    protected PHPStanConfig_ErrorHandler $err;
    protected PHPStanConfig_FileSystem   $fs;
    protected PHPStanConfig_ScriptOutput $output;

    /**
    * Associative array of all environment variables.
    * @var array<string,string>
    */
    protected array $env;

    /** @var array<\Closure():void> */
    private array $scope_guards = [];

    /** @var array<int> */
    private array $scope_guard_types = [];

    /**
    * @param  array<string,string>  $env
    */
    public function __construct(array $env)
    {
        $this->env    = $env;
        $this->err    = new PHPStanConfig_ErrorHandler();
        $this->fs     = new PHPStanConfig_FileSystem($this->err);
        $this->output = new PHPStanConfig_ScriptOutput($this->err);
    }

    /**
    * @param  array<string>  $args
    */
    public final function run(array $args) : void
    {
        $success = false;
        try
        {
            $this->base_script_init();
            $this->main($args);
            $success = true;
        }
        catch(PHPStanConfig_MessageOnlyException $moe)
        {
            $this->err->msg($moe->getMessage()."\n");
        }
        // catch(\Throwable $e)
        // {
        //     // Right now, the default exception handler is probably best
        //     // for most exceptions. (E.g. consider what happens if
        //     // `base_script_init()` executes and the script is in
        //     // a non-CLI environment. It'll throw an exception of course,
        //     // but printing that exception to STDERR won't work, will it?
        //     // (Well, maybe, but it'd be nice if it _always_ worked!))
        // }
        finally
        {
            // This is how we clean up or finalize resources.
            $this->run_scope_guards($success);
        }
    }

    protected final function scope_exit(\Closure $run_on_exit) : void {
        $this->scope_guards[] = $run_on_exit;
        $this->scope_guard_types[] = self::SCOPE_EXIT;
    }

    protected final function scope_success(\Closure $run_on_return) : void {
        $this->scope_guards[] = $run_on_return;
        $this->scope_guard_types[] = self::SCOPE_SUCCESS;
    }

    protected final function scope_failure(\Closure $run_on_throw) : void {
        $this->scope_guards[] = $run_on_throw;
        $this->scope_guard_types[] = self::SCOPE_FAILURE;
    }

    private function run_scope_guards(bool $success) : void
    {
        $have_throws = false;
        $len = \count($this->scope_guards);
        for($i = $len-1; $i >= 0; $i--)
        {
            $scope_guard      = $this->scope_guards[$i];
            $scope_guard_type = $this->scope_guard_types[$i];

            // Skip if it's the wrong guard type for what happened.
            if ((!$success && $scope_guard_type === self::SCOPE_SUCCESS)
            ||  ($success  && $scope_guard_type === self::SCOPE_FAILURE)) {
                continue;
            }

            // Run the scope guard, but be careful about exception handling.
            try {
                $scope_guard();
            }
            catch (\Throwable $e) {
                $have_throws = true;
                $this->err->msg($e->__toString()."\n");
            }
        }

        // Complain if anything was thrown from within scope guards. (don't do that!)
        if ( $have_throws ) {
            $this->err->msg(
                "Exceptions were thrown from within scope guards.\n".
                "This is not supported behavior; please make sure that".
                    " exceptions are not thrown inside scope guards.\n");
        }
    }

    private function base_script_init() : void
    {
        $is_cli = false;
        if (PHP_SAPI === 'cli'
        ||  PHP_SAPI === 'phpdbg'
        ||  \defined('STDIN'))
        {
            $is_cli = true;
        }

        if (!$is_cli) {
            throw new \Exception("Error: This script must be run from the command line.");
        }

        $env = $this->env;
        $ensure_defined =
            function(string $var_name) use(&$env) : void
        {
            if (!\array_key_exists($var_name, $env)) {
                $this->err->abort("Error: Environment variable not defined: $var_name. The calling script (.bat or .sh) MUST define this.");
            }
        };

        $ensure_defined('PHPSTAN_SCRIPT_PATH');
        $ensure_defined('PHPSTAN_SCRIPT_DIRECTORY');
        $ensure_defined('PHPSTAN_SCRIPT_BASENAME');
        $ensure_defined('KK_DOCUMENT_ROOT');

        $PHPSTAN_SCRIPT_DIRECTORY = $env['PHPSTAN_SCRIPT_DIRECTORY'];
        if (!\chdir($env['PHPSTAN_SCRIPT_DIRECTORY'])) {
            $this->err->abort("Error: Unable to change directory to PHPSTAN_SCRIPT_DIRECTORY='$PHPSTAN_SCRIPT_DIRECTORY'");
        }
    }

    /**
    * @param  array<string>  $args
    */
    protected abstract function main(array $args) : void;
}