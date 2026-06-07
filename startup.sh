#!/bin/sh
printf 'upload_max_filesize = 10M\npost_max_size = 12M\n' > /tmp/php-uploads.ini
export PHP_INI_SCAN_DIR="${PHP_INI_SCAN_DIR}:/tmp"
php artisan storage:link --force
php artisan serve --host=0.0.0.0 --port=8000
