#!/usr/bin/env bash

set -x
set -e

sed \
    --regexp-extended \
    --expression 's/^DPkg::Post-Invoke \{/#\0/g' \
    --expression 's/^APT::Update::Post-Invoke \{/#\0/g' \
    --in-place \
    '/etc/apt/apt.conf.d/docker-clean'

# Minimal requirements to run `pmkr`.
apt-get update
apt-get install -y \
    php \
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
apt-get install -y \
    util-linux \
    mc \
    vim

cat <<'EOT'
ln -s /usr/include/locale.h /usr/include/xlocale.h
export PATH="${HOME}/bin:${PATH}"
SHELL="${SHELL}" ./bin/pmkr init:pmkr --force
pmkr instance:list
pmkr -vv instance:install
EOT
