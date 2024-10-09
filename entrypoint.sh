#!/bin/sh

php /app/autoload.php
if [ $? -eq 1 ]; then
    exit 1
fi

if [ ! -f "/app/.env" ]; then
    printenv > /app/.env
fi 

php /app/dm migrate

if [ $? -eq 1 ]; then
    exit 1
fi

chmod +x /app/bin/*.sh

if [ -n "$NGINX_ACCESS_LOGS_PATH" ]; then
    /app/bin/nginx_access_logs.sh &
fi

php /app/dm run:entrypoint
