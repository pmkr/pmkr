#!/usr/bin/env bash

set -x
set -e

sed \
    --regexp-extended \
    --expression 's/^DPkg::Post-Invoke \{/#\0/g' \
    --expression 's/^APT::Update::Post-Invoke \{/#\0/g' \
    --in-place \
    '/etc/apt/apt.conf.d/docker-clean'

# https://serverfault.com/questions/1106694/unable-to-run-apt-update-on-ubuntu-21-10
sed \
    --regexp-extended \
    --expression 's/([a-z]{2}.)?archive.ubuntu.com/old-releases.ubuntu.com/g' \
    --expression 's/security.ubuntu.com/old-releases.ubuntu.com/g' \
    --in-place \
    '/etc/apt/sources.list'


TIMEZONE="${TIMEZONE:-Europe/Budapest}"
ln -snf "/usr/share/zoneinfo/${TIMEZONE}" /etc/localtime
echo "${TIMEZONE}" > /etc/timezone


# Minimal requirements to run `pmkr`.
apt-get update
apt-get install -y \
    patch \
    php \
    php-bz2 \
    php-ctype \
    php-curl \
    php-dom \
    php-mbstring \
    php-phar

#digitlst.cpp:67:13: fatal error: xlocale.h: No such file or directory
#   67 | #   include <xlocale.h>
mkdir -p /usr/include
( cd /usr/include ; ln -s './locale.h' './xlocale.h' )


touch "${HOME}/.gitconfig"
cat <<'INI' >> "${HOME}/.gitconfig"
[safe]
    directory = *

INI
