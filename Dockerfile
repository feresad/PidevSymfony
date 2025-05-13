# Utiliser l'image PHP-FPM
FROM php:8.2-fpm

# Installer les dépendances nécessaires, y compris Nginx
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    nginx \
    && docker-php-ext-install pdo pdo_mysql intl zip \
    && pecl install apcu && docker-php-ext-enable apcu

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code de l'application
COPY . .

# Installer les dépendances Composer
RUN composer install --optimize-autoloader --no-dev

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copier la configuration Nginx
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf

# Script de démarrage pour lancer PHP-FPM et Nginx
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

# Exposer le port 80 pour Nginx
EXPOSE 80

# Commande de démarrage
CMD ["/start.sh"]