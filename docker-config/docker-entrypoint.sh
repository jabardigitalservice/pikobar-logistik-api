#!/bin/sh

printf "Checking database connection...\n\n"
mysql_ready() {
    /usr/bin/mysqladmin ping --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD > /dev/null 2>&1
}

while !(mysql_ready)
do
    sleep 3
    echo "Waiting for database connection ..."
done

composer dump-autoload
artisan config:clear
artisan cache:clear
artisan route:clear
#php backend/artisan migrate --no-interaction -vvv --force

/usr/bin/supervisord

