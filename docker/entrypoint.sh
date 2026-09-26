#!/bin/sh
set -e

# Solo prepara la aplicación cuando se arranca el servidor web; cualquier
# otro comando (p. ej. `php artisan tinker`) se ejecuta directamente.
if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "ERROR: APP_KEY no está definida. Consulta la sección Docker del README." >&2
        exit 1
    fi

    if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
        php artisan migrate --force
    fi

    # Cachés de producción (se regeneran en cada arranque con la config actual)
    php artisan optimize
fi

exec "$@"
