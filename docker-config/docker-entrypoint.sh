#!/bin/sh

app=${DOCKER_APP:-app}

if [ "$app" = "app" ]; then

    echo "Running the app..."
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

elif [ "$app" = "queue" ]; then

    echo "Running the queue..."
    php artisan queue:work --queue=default --sleep=3 --tries=3

else
    echo "Could not match the container app \"$app\""
    exit 1
fi

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
php backend/artisan config:clear
php backend/artisan cache:clear
php backend/artisan route:clear
#php backend/artisan migrate --no-interaction -vvv --force

/usr/bin/supervisord