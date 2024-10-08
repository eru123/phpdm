#!/bin/sh

while true; do
    php /app/dm run:nginx_access_logs -V
    sleep 1
done
