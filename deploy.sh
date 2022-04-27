#!/bin/bash

php artisan down
git pull
php artisan migrate --force
php composer.phar install --no-dev
php artisan up