#!/bin/sh
THIS_SCRIPT_DIR=$(dirname "$0")
php "$THIS_SCRIPT_DIR/../html/vendor/composer/phpstan/phpstan/phpstan" analyse -v -c "$THIS_SCRIPT_DIR/phpstan.neon"
