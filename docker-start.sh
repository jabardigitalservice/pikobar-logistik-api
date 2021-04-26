#!/usr/bin/env bash

printf "Checking database connection...\n\n"
mysql_ready() {
    /usr/bin/mysqladmin ping --host=$DB_HOST --user=$DB_USERNAME --password=$DB_PASSWORD > /dev/null 2>&1
}

while !(mysql_ready)
do
    sleep 3
    echo "Waiting for database connection ..."
done

set -e

role=${CONTAINER_ROLE:-app}
env=${APP_ENV:-production}

# if [ "$env" != "local" ]; then
#     echo "Caching configuration..."
#     (cd /var/www && php artisan config:cache && php artisan route:cache && php artisan view:cache)
# fi

if [ "$role" = "app" ]; then

    exec php-fpm

elif [ "$role" = "queue" ]; then

    echo "Executing queue..."
	sleep 60
    echo "Running the queue..."
    php /var/www/artisan queue:work redis --verbose --daemon

elif [ "$role" = "scheduler" ]; then

    while [ true ]
    do
	  now=$(date +"%Y-%m-%d %T")
	  echo "[$now] Executing cron..."
      php /var/www/artisan schedule:run --verbose --no-interaction &
      sleep $((60 - $(date +%s) % 60))
    done

else
    echo "Could not match the container role \"$role\""
    exit 1
fi
