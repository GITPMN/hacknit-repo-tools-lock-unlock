FROM php:7.3-fpm

RUN apt-get update

RUN apt-get install -y \
        git \
        libpq-dev \
        libzip-dev \
        subversion
RUN docker-php-ext-install -j "$(nproc)" \
        zip

# Install xdebug
RUN pecl install xdebug-2.8.0beta1 \
    && docker-php-ext-enable xdebug

RUN curl https://getcomposer.org/composer.phar --output /usr/bin/composer
RUN chmod +x /usr/bin/composer

WORKDIR /app
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["sh", "/usr/local/bin/entrypoint.sh"]
