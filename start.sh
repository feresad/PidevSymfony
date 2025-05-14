#!/bin/bash
# Vider le cache Symfony en mode production
php bin/console cache:clear --env=prod --no-debug
composer require symfony/runtime
# Démarrer PHP-FPM en arrière-plan
php-fpm &

# Démarrer Nginx en avant-plan
nginx -g 'daemon off;'