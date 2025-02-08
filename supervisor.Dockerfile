FROM alpine:3
 
RUN apt-get update && apt-get install -y \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

USER www-data

