@echo off
SET PHPSTAN_SCRIPT_PATH=%~dpnx0
SET PHPSTAN_SCRIPT_DIRECTORY=%~dp0
SET PHPSTAN_SCRIPT_BASENAME=%~nx0

:: Does PHPSTAN_SCRIPT_DIRECTORY have a trailing slash? If so remove it.
:: Credit: https://stackoverflow.com/questions/2952401/remove-trailing-slash-from-batch-file-input
IF %PHPSTAN_SCRIPT_DIRECTORY:~-1%==\ SET PHPSTAN_SCRIPT_DIRECTORY=%PHPSTAN_SCRIPT_DIRECTORY:~0,-1%

SET KK_PROJECT_ROOT_NOTNORMALIZED=%PHPSTAN_SCRIPT_DIRECTORY%\..
SET KK_DOCUMENT_ROOT_NOTNORMALIZED=%PHPSTAN_SCRIPT_DIRECTORY%\..\html

:: The below "for" commands is/are how we normalize file paths.
:: That's what substitutes
::   "C:\my\project\path\meta\..\html\vendor"
:: with
::   "C:\my\project\path\html\vendor"
:: Source:  https://stackoverflow.com/a/48764567
for %%i in ("%KK_PROJECT_ROOT_NOTNORMALIZED%") do SET "KK_PROJECT_ROOT=%%~fi"

SET KK_DOCUMENT_ROOT=%KK_PROJECT_ROOT%\html

:: PHPStan emits a bunch of higher codepoints by default.
:: Command prompt really doesn't know how to display these things.
:: So we disable the "ansi" output on the Windows/DOS platform.
:: This doesn't avoid ALL mojibake: the progress counter, for instance,
::   still seems to emit control codes that CMD can't render.
SET PHPSTAN_SCRIPT_PLATFORM_OPTS=--no-ansi

:: echo %PHPSTAN_SCRIPT_PATH%
:: echo %PHPSTAN_SCRIPT_DIRECTORY%
:: echo %PHPSTAN_SCRIPT_BASENAME%

:: The below "for" command is how we extract the output of a PHP script
:: and place it into a BATCH variable. The below methodology only captures
:: the last _line_ of script output, but that's OK here because we can ensure
:: that the script only emits a single line.
:: Source:  https://stackoverflow.com/a/24423511
setlocal enableextensions
for /f "delims=" %%a in (
    'php -f "%PHPSTAN_SCRIPT_DIRECTORY%\scripts\phpstan-config\determine-cli-options.php"  defaults="%PHPSTAN_SCRIPT_DIRECTORY%\phpstan-config\default-opts.txt"  local-dir="%KK_PROJECT_ROOT%\extra\phpstan-config"  local-dir="%KK_DOCUMENT_ROOT%\scratch-pad\phpstan-config"'
) do set "PHPSTAN_OPTS=%%a"

php "%PHPSTAN_SCRIPT_DIRECTORY%\scripts\phpstan-config\collect-files-without-php-extension.php"  path="%KK_DOCUMENT_ROOT%\api\v2\server"  output="%PHPSTAN_SCRIPT_DIRECTORY%\tmp\phpstan-files-without-php-extension.neon"
php "%PHPSTAN_SCRIPT_DIRECTORY%\scripts\phpstan-config\determine-directories-to-scan.php"  defaults="%PHPSTAN_SCRIPT_DIRECTORY%\phpstan-config\default-paths.txt"  local-dir="%KK_PROJECT_ROOT%\extra\phpstan-config"  local-dir="%KK_DOCUMENT_ROOT%\scratch-pad\phpstan-config"  output="%PHPSTAN_SCRIPT_DIRECTORY%\tmp\phpstan-paths.neon"

@echo on
SET PHPSTAN_RUNNING=1
php "%KK_DOCUMENT_ROOT%\vendor\composer\phpstan\phpstan\phpstan" %PHPSTAN_OPTS%
pause