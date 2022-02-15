#!/usr/bin/env bash

set -x
set -e

zypper modifyrepo --keep-packages --all

# Minimal requirements to run `pmkr`.
zypper install --no-confirm \
    php8 \
    php8-bz2 \
    php8-ctype \
    php8-curl \
    php8-dom \
    php8-openssl \
    php8-mbstring \
    php8-phar \
    patch

ln -s /usr/include/locale.h /usr/include/xlocale.h

sed \
    --expression 's@^display_errors = Off$@display_errors = STDERR@g' \
    --expression 's@^display_startup_errors = Off$@display_startup_errors = On@g' \
    --expression 's@^;error_log = syslog$@error_log = /var/log/php-error.log@g' \
    --in-place \
    /etc/php8/cli/php.ini

php ./bin/pmkr
SHELL="${SHELL}" ./bin/pmkr init:pmkr --force
