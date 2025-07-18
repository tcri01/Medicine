composer install
php artisan key:generate


php artisan migrate:reset
php artisan migrate
php artisan passport:keys
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
php artisan optimize:clear
php artisan event:clear
chmod -R ugo+rw storage
php artisan storage:link
