composer install --no-dev
php artisan key:generate --ansi
php artisan migrate
php artisan storage:link



composer install --no-dev
php artisan key:generate --ansi
touch database/database.sqlite
php artisan migrate
php artisan storage:link


