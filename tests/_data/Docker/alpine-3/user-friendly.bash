#!/usr/bin/env bash

# User friendly.
apk add \
    'mc' \
    'zsh' \
    'vim'

cat <<'EOT'
SHELL="${SHELL}" ./bin/pmkr init:pmkr --force
pmkr instance:list
pmkr -vv instance:install
EOT
