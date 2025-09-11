#!/bin/sh
THIS_SCRIPT_DIR=$(dirname "$0")

php "$THIS_SCRIPT_DIR/scripts/phpstan-config/collect-files-without-php-extension.php" \
    path="$THIS_SCRIPT_DIR/../html/api/v2/server" \
    output="$THIS_SCRIPT_DIR/tmp/phpstan-files-without-php-extension.neon"

php "$THIS_SCRIPT_DIR/../html/vendor/composer/phpstan/phpstan/phpstan" \
    analyse -v -c "$THIS_SCRIPT_DIR/phpstan.neon"
