

composer install --no-dev
php artisan key:generate --ansi
php artisan migrate
php artisan storage:link
