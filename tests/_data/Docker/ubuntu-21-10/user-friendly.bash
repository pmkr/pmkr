#!/usr/bin/env bash

set -x
set -e

apt-get update
apt-get install -y \
    bat \
    command-not-found \
    jq

cat <<'EOT'
SHELL=$SHELL ./bin/pmkr init:pmkr --force
pmkr instance:list
pmkr -vv instance:install
EOT
