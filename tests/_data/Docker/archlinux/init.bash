#!/usr/bin/env bash

set -x
set -e

cat <<'EOT' >> ~/.bashrc
#!/usr/bin/env bash

export PATH="${HOME}/bin:${PATH}"

EOT

#zypper modifyrepo --keep-packages --all
pacman-key --init

pacman --sync --refresh

# Minimal requirements to run `pmkr`.
pacman --sync --noconfirm \
    php

sed \
    --in-place \
    --expression='s/;extension=bz2/extension=bz2/g' \
    --expression='s/;extension=iconv/extension=iconv/g' \
    --expression='s/display_errors = Off/display_errors = On/g' \
    --expression='s/display_startup_errors = Off/display_startup_errors = On/g' \
    '/etc/php/php.ini'


php ./bin/pmkr

# User friendly.
pacman --sync --noconfirm \
    'vim'


cat <<'EOT'
. ~/.bashrc
SHELL="${SHELL}" ./bin/pmkr init:pmkr --force
pmkr instance:list
pmkr instance:install 070427-nts
EOT
