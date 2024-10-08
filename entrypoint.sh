#!/bin/sh

if [ ! -f "/app/.env" ]; then
    printenv > /app/.env
fi 

if [ -z "$ROOTFS_PATH" ]; then
    ROOTFS_PATH=/app/rootfs
fi

php dm migrate -V
chmod +x /app/scripts_sh/*.sh

if [ -n "$NGINX_PROXY_MANAGER_DATA_PATH" ]; then
    /app/scripts_sh/nginx_access_logs.sh &
fi

if [ -f "$ROOTFS_PATH/proc/meminfo" ]; then
    /app/scripts_sh/sys_mem.sh &
fi

php index.php
