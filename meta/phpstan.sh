#!/bin/sh
set -e
# set -x   # Use this to have the shell print every command that is executed.
PHPSTAN_SCRIPT_PATH="$0"
PHPSTAN_SCRIPT_DIRECTORY=$(dirname "$PHPSTAN_SCRIPT_PATH")
PHPSTAN_SCRIPT_BASENAME=$(basename "$PHPSTAN_SCRIPT_PATH")

KK_DOCUMENT_ROOT="$PHPSTAN_SCRIPT_DIRECTORY/../html"

if [ -z "$KK_PROJECT_ROOT" ]; then
    KK_PROJECT_ROOT=$(dirname "$PHPSTAN_SCRIPT_DIRECTORY")
fi

# Convert paths to absolute paths.
# This is important, because the script may change
# its current working directory (e.g. to the PHPSTAN_SCRIPT_DIRECTORY),
# at which point relative paths would no longer be correct.
# Absolute paths are always valid within the same system,
# so we make things much more likely to succeed by doing this.
get_abs_filename() {
    # $1 : relative filename
    dir=$(cd -- "$(dirname -- "$1")" && pwd -P) || return 1
    base=$(basename -- "$1") || return 1
    printf "%s/%s" $dir $base
}
PHPSTAN_SCRIPT_PATH=$(get_abs_filename $PHPSTAN_SCRIPT_PATH)
PHPSTAN_SCRIPT_DIRECTORY=$(get_abs_filename $PHPSTAN_SCRIPT_DIRECTORY)
PHPSTAN_SCRIPT_BASENAME=$(get_abs_filename $PHPSTAN_SCRIPT_BASENAME)
KK_DOCUMENT_ROOT=$(get_abs_filename $KK_DOCUMENT_ROOT)
KK_PROJECT_ROOT=$(get_abs_filename $KK_PROJECT_ROOT)

# If it's available on the system, use the `realpath`
# command to reduce and normalize the various paths.
# This makes paths less complex, and thus makes things
# more likely to work later on.
realpath / 2> /dev/null
if [ $? -eq 0 ]; then
    PHPSTAN_SCRIPT_PATH=$(realpath $PHPSTAN_SCRIPT_PATH)
    PHPSTAN_SCRIPT_DIRECTORY=$(realpath $PHPSTAN_SCRIPT_DIRECTORY)
    PHPSTAN_SCRIPT_BASENAME=$(realpath $PHPSTAN_SCRIPT_BASENAME)
    KK_DOCUMENT_ROOT=$(realpath $KK_DOCUMENT_ROOT)
    KK_PROJECT_ROOT=$(realpath $KK_PROJECT_ROOT)
fi

THIS_SCRIPT_DIR="$PHPSTAN_SCRIPT_DIRECTORY"

# On Windows-DOS, this is used to pass --no-ansi to avoid _some_ mojibake.
# On Linux/Unix, this is probably unnecessary, as we have terminals
# that can render with fonts of our chosing, and render utf-8 text.
# In these scenarios, any unrendered characters are more likely due to
# the currently chosen font just lacking ways to render those characters.
PHPSTAN_SCRIPT_PLATFORM_OPTS=""

export PHPSTAN_SCRIPT_PLATFORM_OPTS
export PHPSTAN_SCRIPT_PATH
export PHPSTAN_SCRIPT_DIRECTORY
export PHPSTAN_SCRIPT_BASENAME
export KK_DOCUMENT_ROOT
export KK_PROJECT_ROOT

PHPSTAN_OPTS=$(php "$THIS_SCRIPT_DIR/scripts/phpstan-config/determine-cli-options.php" \
    defaults="$THIS_SCRIPT_DIR/phpstan-config/default-opts.txt" \
    local-dir="$KK_PROJECT_ROOT/extra/phpstan-config" \
    local-dir="$KK_DOCUMENT_ROOT/scratch-pad/phpstan-config")

php "$THIS_SCRIPT_DIR/scripts/phpstan-config/determine-directories-to-scan.php" \
    defaults="$THIS_SCRIPT_DIR/phpstan-config/default-paths.txt" \
    local-dir="$KK_PROJECT_ROOT/extra/phpstan-config" \
    local-dir="$KK_DOCUMENT_ROOT/scratch-pad/phpstan-config" \
    output="$THIS_SCRIPT_DIR/tmp/phpstan-paths.neon"

php "$THIS_SCRIPT_DIR/scripts/phpstan-config/collect-files-without-php-extension.php" \
    path="$KK_DOCUMENT_ROOT/api/v2/server" \
    output="$THIS_SCRIPT_DIR/tmp/phpstan-files-without-php-extension.neon"

# Disable globbing so filenames like *.c don't expand during reparsing
set -f

# Reparse the text in PHPSTAN_OPTS as shell words and put them into $1 $2 ...
# (we use double-quotes around the right-hand side only to protect against
#  a variable that is empty causing a syntax error in some shells)
# In a security-sensitive context this would create a shell injection
# vulnerability. We are considering it fine here because this script
# will not be accessible from the internet and will not handle user input;
# it is strictly for developer use: it automates our PHPStan use.
eval "set -- $PHPSTAN_OPTS"

# Restore globbing
set +f

export PHPSTAN_RUNNING="1"
set -x
php "$KK_DOCUMENT_ROOT/vendor/composer/phpstan/phpstan/phpstan" "$@"