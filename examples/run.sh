#!/usr/bin/env bash
# billing creates a customer; orders reads it from its own copy, then asks billing over RPC. Needs Redis on localhost.
set -euo pipefail
cd "$(dirname "$0")"

export APP_KEY=base64:$(printf 'example-key-example-key-example!!' | base64)
export MICROSERVICES_RPC_SECRET=example-secret DB_CONNECTION=sqlite CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync

for app in billing orders; do
    mkdir -p $app/storage/framework/{cache,sessions,views} $app/storage/logs $app/bootstrap/cache
    (cd $app && composer install --quiet --no-interaction && rm -f database/database.sqlite && touch database/database.sqlite && php artisan migrate --force --quiet)
done

redis-cli del microservices:events > /dev/null

(cd billing && exec php -S 127.0.0.1:8001 -t public > /dev/null 2>&1) & server=$!
(cd orders && exec php artisan microservices:events:consume > /dev/null) & consumer=$!
trap 'kill $server $consumer 2> /dev/null' EXIT
sleep 2

(cd billing && php artisan customers:create Ada)
sleep 2

output=$(cd orders && php artisan customers:show 1)
echo "$output"
[ "$output" = $'copy: Ada\nrpc: Ada' ]
