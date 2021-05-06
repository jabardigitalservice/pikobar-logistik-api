FROM alpine:3.13

LABEL Maintainer="Jabar Digital Service <digital.service@jabarprov.go.id>" \
    Description="Lightweight container with Nginx 1.16 & PHP-FPM 7.4 based on Alpine Linux (forked from trafex/alpine-nginx-php7)."

ARG PHP_VERSION="7.4.15-r0"

# Fix iconv issue when generate pdf
RUN apk --no-cache add --repository http://dl-cdn.alpinelinux.org/alpine/v3.13/community/ gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

# Install packages
RUN apk --no-cache add nano php7=${PHP_VERSION} php7-fpm php7-opcache php7-phar php7-openssl php7-gd php7-ctype php7-curl php7-dom \
    php7-iconv php7-intl php7-json php7-mbstring php7-pdo_mysql php7-pdo_sqlite php7-session php7-simplexml php7-tokenizer php7-fileinfo php7-xml php7-xmlreader php7-xmlwriter \
    php7-zip php7-zlib php7-sodium php7-posix php7-pcntl nginx supervisor curl && \
    rm /etc/nginx/conf.d/default.conf

# Configure nginx
COPY docker-config/nginx.conf /etc/nginx/nginx.conf

# Configure PHP-FPM
COPY docker-config/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY docker-config/php.ini /etc/php7/conf.d/custom.ini

# Configure supervisord
COPY docker-config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup document root
RUN mkdir -p /var/www/html

# Make sure files/folders needed by the processes are accessable when they run under the nobody user
RUN chown -R nobody.nobody /var/www/html && \
    chown -R nobody.nobody /run && \
    chown -R nobody.nobody /var/lib/nginx && \
    chown -R nobody.nobody /var/log/nginx

# Switch to use a non-root user from here on
USER nobody

# Add application
WORKDIR /var/www/html
COPY --chown=nobody . /var/www/html/
COPY --from=composer:2.0.9 /usr/bin/composer /usr/local/bin/composer

RUN php /usr/local/bin/composer install --no-dev --optimize-autoloader

# Expose the port nginx is reachable on
EXPOSE 8080

ENTRYPOINT [ "docker-config/docker-entrypoint.sh" ]
