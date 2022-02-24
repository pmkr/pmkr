#!/usr/bin/env bash

set -x
set -e

sed \
    --regexp-extended \
    --expression 's/^DPkg::Post-Invoke \{/#\0/g' \
    --expression 's/^APT::Update::Post-Invoke \{/#\0/g' \
    --in-place \
    '/etc/apt/apt.conf.d/docker-clean'


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
