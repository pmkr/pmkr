#!/usr/bin/env sh

set -x
set -e

# Minimal requirements to run `pmkr`.
apk add \
    patch \
    php8 \
    php8-bz2 \
    php8-curl \
    php8-ctype \
    php8-dom \
    php8-iconv \
    php8-json \
    php8-mbstring \
    php8-openssl \
    php8-phar \
    php8-tokenizer \
    php8-xml \
    php8-xmlreader \
    php8-xmlwriter

( cd /usr/bin ; ln -s './php8' './php' )

# digitlst.cpp:67:13: fatal error: xlocale.h: No such file or directory
#   67 | #   include <xlocale.h>
mkdir -p /usr/include/
( cd /usr/include/ ; ln -s './locale.h' './xlocale.h' )

# configure: error: freetype-config not found.
# @todo Remove this.
#ln -s /usr/bin/pkg-config /usr/bin/freetype-config

touch "${HOME}/.gitconfig"
cat <<'INI' >> "${HOME}/.gitconfig"
[safe]
    directory = *

INI
