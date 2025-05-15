#!/bin/bash

# Fonction pour démarrer le consommateur de messages
start_messenger_consumer() {
    while true; do
        if ! pgrep -f "messenger:consume async" > /dev/null; then
            echo "Starting messenger consumer..."
            php bin/console messenger:consume async -vv &
        fi
        sleep 30
    done
}

# Vider le cache Symfony en mode production
php bin/console cache:clear --env=prod --no-debug
composer require symfony/runtime
mkdir -p /var/www/html/var/log
touch /var/www/html/var/log/messenger.log
chown www-data:www-data /var/www/html/var/log/messenger.log
chmod 664 /var/www/html/var/log/messenger.log

# Démarrer cron
cron

# Démarrer PHP-FPM en arrière-plan
php-fpm &

# Démarrer le superviseur du consommateur de messages en arrière-plan
start_messenger_consumer &

# Démarrer Nginx en avant-plan
nginx -g 'daemon off;'