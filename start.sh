#!/bin/bash

mkdir -p /run/php
chown -R www-data:www-data /run/php
chown -R www-data:www-data /var/www/data
rm -rf /var/www/html/index.nginx-debian.html
php-fpm -D 

exec "$@"
