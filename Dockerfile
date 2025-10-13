FROM dunglas/frankenphp:latest-php8.3

RUN install-php-extensions \
    intl \
    pdo \
    pdo_mysql \
    zip \
    xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

COPY . .

COPY Caddyfile /etc/caddy/Caddyfile

RUN composer run-script --no-interaction post-install-cmd || true

RUN chown -R www-data:www-data /app

EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
