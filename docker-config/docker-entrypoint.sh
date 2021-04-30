#!/bin/sh

composer dump-autoload -d backend/
php backend/artisan config:clear
php backend/artisan cache:clear
php backend/artisan route:clear
#php backend/artisan migrate --no-interaction -vvv --force

/usr/bin/supervisor

