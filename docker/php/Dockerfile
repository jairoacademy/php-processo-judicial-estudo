# Imagem do PHP-FPM. Dois alvos (multi-stage):
#   base: extensões de produção (pdo_mysql, intl, opcache)
#   dev:  base + Xdebug e usuário com o mesmo UID do host (arquivos montados ficam graváveis)
FROM php:8.4-fpm-alpine AS base

# icu-libs fica na imagem final; icu-dev só existe durante a compilação do intl
RUN apk add --no-cache icu-libs \
    && apk add --no-cache --virtual .build-deps icu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl opcache \
    && apk del .build-deps

COPY docker/php/conf.d/app.ini "$PHP_INI_DIR/conf.d/zz-app.ini"
WORKDIR /var/www/app

FROM base AS dev

ARG UID=1000
ARG GID=1000

# Composer (gerenciador de dependências) copiado da imagem oficial; git e unzip baixam os pacotes
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache git unzip

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers shadow \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && usermod -u "$UID" www-data \
    && groupmod -g "$GID" www-data \
    && apk del .build-deps

COPY docker/php/conf.d/dev.ini "$PHP_INI_DIR/conf.d/zz-dev.ini"

# Roda como www-data (UID do host): arquivos criados por composer e pelo PHP ficam com o dono certo
ENV COMPOSER_HOME=/tmp/composer
USER www-data
