# PHP Data Metrics

PHP as Data Metrics Collector.

## Environment Variables

Env variables works on both local environment and docker container.

- For trying out this project on local environment, you can create `.env` file inside `app` directory.
- For running this project on docker container, you can directly add environment variables to docker container, whether using `-e` or `--env` flag in docker or using `environment` field on phpdm docker service of your `docker-compose.yml` file.

### MySql

| Environment Variable | Description    | Default Value |
| -------------------- | -------------- | ------------- |
| DB_HOST              | MySql Server   | `localhost`   |
| DB_PORT              | MySql Port     | `3306`        |
| DB_USER              | MySql Username | `root`        |
| DB_PASS              | MySql Password | empty         |
| DB_NAME              | MySql Name     | `phpdm`       |

### Nginx Access Logs Integration

Nginx Access Logs Collector is Compatible with Nginx Proxy Manager Container. If you have custom nginx log format, You can modify NginxAccess Integration class and add custom regex to match your custom log format. If you are modifying the regex please match the pattern names as follows: `message`, `type`, `timestamp`, `upstream_cache_status`, `upstream_status`, `status`, `method`, `scheme`, `host`,`uri`, `ip`, `size`, `ratio`, `server`, `user_agent`, `referer`.

| Environment Variable        | Description                                                                                                | Default Value                          |
| --------------------------- | ---------------------------------------------------------------------------------------------------------- | -------------------------------------- |
| NGINX_ACCESS_INTERVAL       | Nginx Access logs collection interval in cron format, for more info refer to [Crontabs Section](#Crontabs) | `*/10 * * * * *` runs every 10 seconds |
| NGINX_ACCESS_LOGS_PATH      | Nginx Access log paths seperated by `:`. You can use glob pattern matching.                                | `/var/log/nginx/*access.log`           |
| NGINX_ACCESS_ANALYTICS_ONLY | Nginx Access mode that only save analytics data and discard logs.                                          | `true`                                 |

# Crontabs

Our extended contab parser is compatible with running rules with seconds (just add another `*`), we also supported [Crontab Guru](https://crontab.guru/).

| Supported Alias | Cron Expression | Description         |
| --------------- | --------------- | ------------------- |
| `@yearly`       | `0 0 1 1 *`     | Runs once a year.   |
| `@year`         | `0 0 1 1 *`     | Runs once a year.   |
| `@annually`     | `0 0 1 1 *`     | Runs once a year.   |
| `@annual`       | `0 0 1 1 *`     | Runs once a year.   |
| `@monthly`      | `0 0 1 * *`     | Runs once a month.  |
| `@month`        | `0 0 1 * *`     | Runs once a month.  |
| `@weekly`       | `0 0 * * 0`     | Runs once a week.   |
| `@week`         | `0 0 * * 0`     | Runs once a week.   |
| `@daily`        | `0 0 * * *`     | Runs once a day.    |
| `@day`          | `0 0 * * *`     | Runs once a day.    |
| `@midnight`     | `0 0 * * *`     | Runs once a day.    |
| `@nightly`      | `0 0 * * *`     | Runs once a day.    |
| `@night`        | `0 0 * * *`     | Runs once a day.    |
| `@hourly`       | `0 * * * *`     | Runs once an hour.  |
| `@hour`         | `0 * * * *`     | Runs once an hour.  |
| `@minutely`     | `* * * * *`     | Runs once a minute. |
| `@minute`       | `* * * * *`     | Runs once a minute. |
| `@secondly`     | `* * * * * *`   | Runs every second.  |
| `@second`       | `* * * * * *`   | Runs every second.  |
