# Utiliser une image PHP avec Apache
FROM php:8.2-apache

# Installer les dépendances nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip \
    && pecl install apcu && docker-php-ext-enable apcu

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurer Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY ./vhosts.conf /etc/apache2/sites-enabled/000-default.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code de l'application
COPY . .

# Installer les dépendances Composer
RUN composer install --optimize-autoloader --no-dev

# Donner les permissions appropriées
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Exposer le port 80
EXPOSE 80