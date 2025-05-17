#!/bin/bash

# Vider le cache Symfony en mode production
php bin/console cache:clear --env=prod --no-debug
composer require symfony/runtime
mkdir -p /var/www/html/var/log
touch /var/www/html/var/log/messenger.log
chown www-data:www-data /var/www/html/var/log/messenger.log
chmod 664 /var/www/html/var/log/messenger.log

# Démarrer PHP-FPM en arrière-plan
php-fpm &

# Démarrer le superviseur du consommateur de messages en arrière-plan
php bin/console messenger:consume async -vv --memory-limit=128M >> /var/www/html/var/log/messenger.log 2>&1 &
# Démarrer Nginx en avant-plan
nginx -g 'daemon off;'