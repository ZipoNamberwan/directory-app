FROM dunglas/frankenphp
 
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    libzip-dev \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --optimize-autoloader --no-dev

# RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public\
#     && chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public

USER www-data
 
ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--workers=12"]