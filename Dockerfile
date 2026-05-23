FROM alpine:3 AS css-builder
ARG TAILWIND_VERSION=v3.4.17
WORKDIR /app
ADD https://github.com/tailwindlabs/tailwindcss/releases/download/${TAILWIND_VERSION}/tailwindcss-linux-x64 /usr/local/bin/tailwindcss
RUN chmod +x /usr/local/bin/tailwindcss
COPY tailwind.config.js ./
COPY assets/ ./assets/
COPY templates/ ./templates/
COPY src/ ./src/
RUN tailwindcss -c tailwind.config.js -i ./assets/css/input.css -o /tmp/tailwind.css --minify


FROM dunglas/frankenphp:latest-php8.3

RUN install-php-extensions \
    intl \
    pdo \
    pdo_sqlite \
    zip \
    xml

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* ./

RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts

COPY . .

COPY --from=css-builder /tmp/tailwind.css /app/public/css/tailwind.css

COPY Caddyfile /etc/caddy/Caddyfile

RUN composer run-script --no-interaction post-install-cmd || true

RUN mkdir -p /app/var && chown -R www-data:www-data /app

USER www-data

EXPOSE 80 443

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
