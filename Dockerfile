FROM phpswoole/swoole:4.4.25-php7.2-alpine

COPY build/php.ini /usr/local/etc/php/conf.d/php.ini

RUN set -ex \
    && apk update \
    && apk add --no-cache libcouchbase=2.10.6-r0 \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libcouchbase-dev=2.10.6-r0 zlib-dev \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install bcmath \
    && pecl update-channels \
    && pecl install couchbase-2.6.2 \
    && pecl install inotify \
    && pecl install yaf \
    && pecl install mongodb \
    && docker-php-ext-enable couchbase \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man /usr/src/php.tar.xz* \
    && apk del .build-deps 
   
WORKDIR /app