FROM alpine:3.13

LABEL Maintainer="Jabar Digital Service <digital.service@jabarprov.go.id>" \
      Description="Lightweight container with Nginx 1.16 & PHP-FPM 7.4 based on Alpine Linux (forked from trafex/alpine-nginx-php7)."

ADD https://packages.whatwedo.ch/php-alpine.rsa.pub /etc/apk/keys/php-alpine.rsa.pub

# make sure you can use HTTPS
#RUN apk --update add ca-certificates

#RUN echo "https://dl.bintray.com/php-alpine/v3.10/php-7.4" >> /etc/apk/repositories

# Install packages
#RUN apk add php php-fpm php-opcache php-openssl php-curl php-gd \
#    nginx supervisor curl

# Get latest Composer
#RUN apk add composer
RUN apk --no-cache add php7 php7-fpm php7-opcache php7-mysqli php7-json php7-openssl php7-curl \
    php7-zlib php7-xml php7-phar php7-intl php7-dom php7-xmlreader php7-ctype php7-session php7-fileinfo php7-tokenizer php7-pdo_pgsql \ 
    php7-simplexml php7-iconv php7-xmlwriter php7-zip php7-pdo_sqlite\
    php7-mbstring php7-gd nginx supervisor curl


# https://github.com/codecasts/php-alpine/issues/21
#RUN ln -s /usr/bin/php7 /usr/bin/php

# Configure nginx
COPY docker-config/nginx.conf /etc/nginx/nginx.conf

# Remove default server definition
RUN rm etc/nginx/conf.d/default.conf

# Configure PHP-FPM
COPY docker-config/fpm-pool.conf /etc/php7/php-fpm.d/www.conf
COPY docker-config/php.ini /etc/php7/conf.d/custom.ini

# Configure supervisord
COPY docker-config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

#Debug supervisord.conf
RUN ls -l

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
COPY --chown=nobody . /var/www/html
COPY --from=composer:2.0.9 /usr/bin/composer /usr/local/bin/composer

#COPY --chown=nobody .env-local /var/www/html/.env


RUN php /usr/local/bin/composer install --no-dev --optimize-autoloader \
  && chmod +x docker-config/docker-entrypoint.sh

#RUN composer install

# Expose the port nginx is reachable on
EXPOSE 8080

# Let supervisord start nginx & php-fpm
#CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
ENTRYPOINT [ "docker-config/docker-entrypoint.sh" ]

# Configure a healthcheck to validate that everything is up&running
HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:8080/fpm-ping