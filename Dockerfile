# syntax=docker/dockerfile:1

###############################################################################
# Etapa 1 · base: PHP 8.4 + Apache con las extensiones que usa el proyecto
###############################################################################
# PHP 8.4: composer.lock contiene dependencias que exigen >=8.4 y otras <8.5.
FROM docker.io/library/php:8.4-apache AS base

# Instalador oficial de extensiones (resuelve dependencias del sistema)
COPY --from=docker.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

# - pdo_mysql: base de datos
# - gd, zip, intl, bcmath: maatwebsite/excel, dompdf y validaciones
# - imagick: generación de códigos de barras PNG (picqer)
# - opcache: rendimiento en producción
# - pcntl: señales del worker de colas / artisan
RUN install-php-extensions pdo_mysql gd zip intl bcmath imagick opcache pcntl \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# Configuración de PHP (producción) y de Apache
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/app.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Apache escucha en 8080 para poder ejecutarse como usuario sin privilegios
RUN a2enmod rewrite headers \
    && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2

WORKDIR /var/www/html

###############################################################################
# Etapa 2 · vendor: dependencias de Composer (sin paquetes de desarrollo)
###############################################################################
FROM base AS vendor

COPY --from=docker.io/library/composer:2 /usr/bin/composer /usr/bin/composer

# Primero solo los manifiestos para aprovechar la caché de capas
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
    && php artisan package:discover --ansi

###############################################################################
# Etapa 3 · assets: compilación de CSS/JS con Vite
###############################################################################
FROM docker.io/library/node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit --no-fund

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind escanea vistas de Jetstream y de paginación dentro de vendor/
COPY --from=vendor /var/www/html/vendor/laravel ./vendor/laravel
RUN npm run build

###############################################################################
# Etapa 4 · app: imagen final de ejecución
###############################################################################
FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    XDG_CONFIG_HOME=/tmp/.config

# El código queda de solo lectura; www-data solo escribe en storage y bootstrap/cache
COPY --from=vendor /var/www/html /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD php -r 'exit(@file_get_contents("http://127.0.0.1:8080/up") === false ? 1 : 0);'

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
