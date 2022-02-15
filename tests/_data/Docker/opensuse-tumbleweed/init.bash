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

php ./bin/pmkr

ln -s /usr/include/locale.h /usr/include/xlocale.h
SHELL="${SHELL}" ./bin/pmkr init:pmkr --force
