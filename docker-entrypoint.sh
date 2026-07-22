#!/bin/sh

set -eu

gosu www-data php artisan config:clear --no-interaction
gosu www-data php artisan config:cache --no-interaction
gosu www-data php artisan migrate --force --no-interaction
gosu www-data php artisan db:seed --force --no-interaction

exec "$@"
