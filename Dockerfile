FROM php:7.4-apache

RUN apt-get update -y && apt-get install -y libpng-dev && apt-get install -y libcurl4-openssl-dev
RUN apt-get -y install git
RUN apt-get install -y unzip
RUN docker-php-ext-install pdo pdo_mysql gd curl
COPY start-apache /usr/local/bin
RUN a2enmod rewrite

WORKDIR /var/www/html

COPY . .
RUN chown -R www-data:www-data /var/www/html

RUN curl -sS https://getcomposer.org/installer | \
    php -- --install-dir=/usr/bin/ --filename=composer
COPY composer.json ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-scripts --no-dev --optimize

CMD ["start-apache"]