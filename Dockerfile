# Usa PHP 8.2 con Apache como base
FROM php:8.2-apache

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

# Configura Apache para Laravel
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Configura AllowOverride para .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Instala dependencias del sistema y extensiones de PHP necesarias para Laravel
RUN apt-get update && apt-get install -y \
    unzip zip curl git libonig-dev libzip-dev libpq-dev \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip mbstring gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copia Composer desde imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia archivos de dependencias primero (para mejor cache de Docker)
COPY composer.json composer.lock ./

# Instala dependencias de Laravel (solo producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copia todo el proyecto
COPY . .

# Ejecuta scripts post-instalación de Composer
RUN composer run-script post-autoload-dump

# Crea el archivo de almacenamiento de enlaces simbólicos si no existe
RUN php artisan storage:link || true

# Establece permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Optimiza Laravel para producción
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expone el puerto de Apache
EXPOSE 80

# Script de inicio para manejar migraciones y comandos iniciales
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Inicia con el script personalizado
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]