#!/usr/bin/env bash

set -x
set -e

pacman-key --init
pacman --sync --noconfirm --refresh
pacman --sync --noconfirm archlinux-keyring

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

# digitlst.cpp:67:13: fatal error: xlocale.h: No such file or directory
#   67 | #   include <xlocale.h>
mkdir -p /usr/include
( cd /usr/include ; ln -s './locale.h' './xlocale.h' )

# configure: error: freetype-config not found.
# @todo Remove this.
ln -s /usr/bin/pkg-config /usr/bin/freetype-config

touch "${HOME}/.gitconfig"
cat <<'INI' >> "${HOME}/.gitconfig"
[safe]
    directory = *

INI
