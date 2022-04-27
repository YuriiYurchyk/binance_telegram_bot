#!/bin/bash

php artisan down
git pull
php artisan migrate
php composer.phar install --no-dev
php artisan up