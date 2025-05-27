# Usa PHP 8.2 con Apache como base
FROM php:8.2-apache

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

# Establece el DocumentRoot a /public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Instala dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    unzip zip curl git libonig-dev libzip-dev libpq-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Copia Composer desde imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia todo el proyecto
COPY . .

# Instala dependencias de Laravel (solo producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permisos y cache de configuración
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && php artisan config:clear \
    && php artisan config:cache

# Expone el puerto de Apache
EXPOSE 80

# Inicia Apache en modo foreground
CMD ["apache2-foreground"]
