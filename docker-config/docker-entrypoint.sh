#!/bin/sh

php composer.phar dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
#php backend/artisan migrate --no-interaction -vvv --force

/usr/bin/supervisord