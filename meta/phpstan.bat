php scripts/phpstan-config/collect-files-without-php-extension.php path=../html/api/v2/server output=tmp/phpstan-files-without-php-extension.neon
php ../html/vendor/composer/phpstan/phpstan/phpstan analyse -v -c /phpstan.neon
pause
