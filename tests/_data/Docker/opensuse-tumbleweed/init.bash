#!/usr/bin/env bash

set -x
set -e

if [[ "${ZYPPER_KEEP_PACKAGES}" = 'true' ]] ; then
    zypper modifyrepo --keep-packages --all
fi

# Minimal requirements to run `pmkr`.
zypper install --no-confirm \
    php8 \
    php8-cli \
    php8-bz2 \
    php8-ctype \
    php8-curl \
    php8-dom \
    php8-iconv \
    php8-openssl \
    php8-mbstring \
    php8-phar \
    patch

# Composer install OR dev requirements.
zypper install --no-confirm \
    git \
    tar \
    zip \
    unzip \
    php8-zip \
    php8-xmlwriter \
    php8-tokenizer \
    glibc-devel

mkdir -p /usr/include
( cd /usr/include ; ln -s './locale.h' './xlocale.h' )

sed \
    --expression 's@^display_errors = Off$@display_errors = STDERR@g' \
    --expression 's@^display_startup_errors = Off$@display_startup_errors = On@g' \
    --expression 's@^;error_log = syslog$@error_log = /var/log/php-error.log@g' \
    --in-place \
    /etc/php8/cli/php.ini

git config \
    --global \
    --add \
    safe.directory \
    /root/.cache/pmkr/git/github.com/sensational/sassphp.git
