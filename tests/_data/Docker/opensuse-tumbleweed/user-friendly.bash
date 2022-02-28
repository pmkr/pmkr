#!/usr/bin/env bash

set -x
set -e

zypper install --no-confirm \
    bat \
    command-not-found \
    bzip2 \
    jq \
    tar \
    util-linux \
    yq

cat <<'EOT'
SHELL=$SHELL ./bin/pmkr init:pmkr --force
pmkr instance:list
pmkr -vv instance:install
EOT
