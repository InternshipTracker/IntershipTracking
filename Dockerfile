FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y \
    unzip git curl libzip-dev zip nodejs npm \
    && docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN composer install --no-dev --optimize-autoloader

# 🔥 DATABASE FIX
RUN mkdir -p database
RUN touch database/database.sqlite
RUN chmod -R 777 database

# 🔥 VITE BUILD
RUN npm install
RUN npm run build

# 🔥 CACHE CLEAR
RUN php artisan config:clear
RUN php artisan view:clear
RUN php artisan route:clear

# 🔥 MIGRATION
RUN php artisan migrate --force

CMD php artisan serve --host=0.0.0.0 --port=10000
