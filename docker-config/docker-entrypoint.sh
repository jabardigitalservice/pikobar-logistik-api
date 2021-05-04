#!/bin/sh
app=${DOCKER_APP:-app}

if [ "$app" = "app" ]; then

    echo "Running the app..."
    /usr/bin/supervisord -c /etc/supervisord.conf

elif [ "$app" = "queue" ]; then

    echo "Running the queue..."
    php artisan queue:work --queue=default --sleep=3 --tries=3

else
    echo "Could not match the container app \"$app\""
    exit 1
fi

php composer.phar dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan route:clear
#php backend/artisan migrate --no-interaction -vvv --force

/usr/bin/supervisord