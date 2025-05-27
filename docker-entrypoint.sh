#!/bin/bash
set -e

# Esperar a que la base de datos esté disponible (si usas base de datos)
echo "Waiting for database connection..."

# Función para esperar la base de datos
wait_for_db() {
    until php artisan migrate:status &> /dev/null; do
        echo "Database not ready, waiting..."
        sleep 2
    done
}

# Para Firebase no necesitamos migraciones tradicionales
# Solo ejecutar si hay migraciones y no es Firebase
if [[ "${DB_CONNECTION:-}" != "sqlite" ]] && [[ -d "database/migrations" ]] && [[ "$(ls -A database/migrations)" ]]; then
    wait_for_db
    echo "Running database migrations..."
    php artisan migrate --force
else
    echo "Skipping migrations (using Firebase or no migrations found)"
fi

# Limpiar y reconstruir cache si es necesario
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Asegurar que el storage link existe
php artisan storage:link 2>/dev/null || true

# Asegurar permisos correctos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "Application ready!"

# Ejecutar el comando pasado como argumento
exec "$@"