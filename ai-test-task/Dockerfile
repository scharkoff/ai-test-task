FROM php:8.3-fpm

WORKDIR /var/www/html

COPY . /var/www/html

RUN apt-get update \
 && apt-get install -y libpq-dev git unzip nodejs npm \
 && docker-php-ext-install pdo pdo_mysql \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
 && composer install --no-dev --optimize-autoloader \
 && npm install

RUN npm run build

# COPY ./www.conf /usr/local/etc/php-fpm.d/www.conf

RUN composer clear-cache \ 
 && npm cache clean --force \
 && php artisan config:clear

EXPOSE 9000

CMD ["php-fpm"]
