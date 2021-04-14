php composer.phar dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan migrate --no-interaction -vvv --force

echo "done!"

printf "\nstart apache2...\n"
apache2ctl -D FOREGROUND
