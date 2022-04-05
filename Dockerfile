FROM php:7.3-apache

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apt-get update \
    && apt-get install -y nano curl

RUN curl -fsSL https://deb.nodesource.com/setup_17.x | bash - \
    && apt-get install -y nodejs

RUN apt-get install -y jpegoptim \
    && apt-get install -y optipng \
    && apt-get install -y pngquant \
    && apt-get install -y gifsicle \
    && apt-get install -y webp \
    && npm install -y -g svgo@1.3.2


