#!/bin/bash
printf "Checking database connection...\n\n"
mysql_ready() {
    /usr/bin/mysqladmin ping --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD > /dev/null 2>&1
}

while !(mysql_ready)
do
    sleep 3
    echo "Waiting for database connection ..."
done

php composer.phar dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan migrate --no-interaction -vvv --force

echo "done!"

printf "\nstart apache2...\n"
apache2ctl -D FOREGROUND