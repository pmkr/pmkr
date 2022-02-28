#!/usr/bin/env bash

set -x
set -e

# Minimal requirements to run `pmkr`.
dnf install --assumeyes \
    php-cli \
    php-bz2 \
    php-ctype \
    php-curl \
    php-dom \
    php-mbstring \
    php-phar
