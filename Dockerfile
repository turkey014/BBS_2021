FROM php:8.0-fpm-alpine AS php
RUN apk add -U --no-cache curl-dev
RUN docker-php-ext-install curl
RUN apk add autoconf g++ make
RUN pecl install apcu && docker-php-ext-enable apcu

RUN docker-php-ext-install exif

RUN docker-php-ext-install pdo_mysql

RUN install -o www-data -g www-data -d /var/www/public/image

RUN echo -e "post_max_size = 5M\nupload_max_filesize = 5M" >> ${PHP_INI_DIR}/php.ini
