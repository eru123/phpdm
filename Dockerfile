FROM alpine:3.18

WORKDIR /app

RUN apk update \
    && apk upgrade \
    && apk add --update --no-cache \
    curl php81 \
    php81-pdo_mysql \
    php81-json \
    php81-phar \
    php81-mbstring \
    php81-iconv

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY app .
RUN composer install --no-interaction --no-progress

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
