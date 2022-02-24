#!/usr/bin/env bash

set -x
set -e

pacman-key --init

pacman --sync --refresh

# Minimal requirements to run `pmkr`.
pacman --sync --noconfirm \
    glibc \
    patch \
    php

sed \
    --in-place \
    --expression='s/;extension=bz2/extension=bz2/g' \
    --expression='s/;extension=iconv/extension=iconv/g' \
    --expression='s/display_errors = Off/display_errors = On/g' \
    --expression='s/display_startup_errors = Off/display_startup_errors = On/g' \
    '/etc/php/php.ini'
