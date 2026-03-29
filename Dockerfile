FROM php:8.2-cli

WORKDIR /app

COPY . .

RUN apt-get update && apt-get install -y \
    unzip git curl libzip-dev zip \
    && docker-php-ext-install zip

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN composer install --no-dev --optimize-autoloader

# Install Node
RUN apt-get update && apt-get install -y nodejs npm

# Install frontend dependencies
RUN npm install

# Build Vite assets
RUN npm run build

RUN php artisan view:clear
RUN php artisan config:clear
RUN php artisan route:clear
RUN php artisan optimize

RUN chmod -R 777 storage bootstrap/cache

RUN php artisan migrate --force
RUN php artisan db:seed --force

RUN php artisan config:clear
RUN php artisan cache:clear || true
RUN php artisan view:clear
RUN php artisan route:clear

CMD php artisan serve --host=0.0.0.0 --port=10000
