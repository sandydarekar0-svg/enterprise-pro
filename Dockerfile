FROM php:8.1-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    curl git zip unzip \
    libssl-dev libcurl4-openssl-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers ssl

RUN docker-php-ext-install \
    mysqli pdo pdo_mysql curl json openssl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-dev --optimize-autoloader 2>/dev/null || true

RUN mkdir -p uploads logs && \
    chmod -R 755 uploads logs

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080 3001

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

CMD ["/entrypoint.sh"]
