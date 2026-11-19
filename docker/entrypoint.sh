#!/bin/sh
set -e

# Ejecuta un comando como www-data cuando el contenedor arranca como root
# (por ejemplo en Railway con RAILWAY_RUN_UID=0 para poder escribir en el volumen).
como_www() {
    if [ "$(id -u)" = "0" ]; then
        setpriv --reuid=www-data --regid=www-data --init-groups "$@"
    else
        "$@"
    fi
}

# Solo prepara la aplicación cuando se arranca el servidor web; cualquier
# otro comando (p. ej. `php artisan tinker`) se ejecuta directamente.
if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "ERROR: APP_KEY no está definida. Consulta la sección Docker del README." >&2
        exit 1
    fi

    # Un volumen recién montado (Railway) llega vacío y con dueño root:
    # recrear las carpetas que Laravel espera y dárselas a www-data.
    if [ "$(id -u)" = "0" ]; then
        mkdir -p storage/app/private storage/app/public \
                 storage/framework/cache/data storage/framework/sessions \
                 storage/framework/views storage/logs
        chown -R www-data:www-data storage bootstrap/cache
    fi

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        como_www php artisan migrate --force
    fi

    # Cachés de producción (se regeneran en cada arranque con la config actual)
    como_www php artisan optimize
fi

# Apache arranca como root solo para abrir el puerto y atiende como www-data
exec "$@"
