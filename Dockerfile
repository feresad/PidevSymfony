# Utiliser l'image PHP-FPM
FROM php:8.2-fpm

# Installer les dépendances nécessaires, y compris Nginx
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    nginx \
    cron \
    # Dépendances pour GD
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql intl zip gd \
    && pecl install apcu && docker-php-ext-enable apcu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code de l'application
COPY . .

# Installer les dépendances Composer en mode production, sans scripts
ENV APP_ENV=prod
RUN composer install --optimize-autoloader --no-dev --no-scripts

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copier la configuration Nginx
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf

# Script de démarrage pour lancer PHP-FPM, Nginx, et vider le cache
COPY ./start.sh /start.sh
RUN chmod +x /start.sh

# Exposer le port 8080 pour Render
EXPOSE 8080

# Commande de démarrage
CMD ["/start.sh"]
