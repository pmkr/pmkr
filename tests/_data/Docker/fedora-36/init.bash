#!/usr/bin/env bash

set -x
set -e

#sed \
#    --regexp-extended \
#    --expression 's/^DPkg::Post-Invoke \{/#\0/g' \
#    --expression 's/^APT::Update::Post-Invoke \{/#\0/g' \
#    --in-place \
#    '/etc/apt/apt.conf.d/docker-clean'

# Minimal requirements to run `pmkr`.
dnf install --assumeyes \
    php-cli \
    php-bz2 \
    php-ctype \
    php-curl \
    php-dom \
    php-mbstring \
    php-phar

php ./bin/pmkr

# Minimal requirements to compile PHP core or PHP extension.
#apt-get install -y \
#    autoconf \
#    bison \
#    cmake \
#    findutils \
#    g++ \
#    gcc \
#    git \
#    make \
#    re2c

# User friendly.
dnf install --assumeyes \
    util-linux
