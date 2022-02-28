# pmkr

[![CircleCI](https://circleci.com/gh/pmkr/pmkr/tree/1.x.svg?style=svg)](https://circleci.com/gh/pmkr/pmkr/?branch=1.x)
[![codecov](https://codecov.io/gh/pmkr/pmkr/branch/1.x/graph/badge.svg?token=HSF16OGPyr)](https://app.codecov.io/gh/pmkr/pmkr/branch/1.x)


## What is pmkr?

The name „pmkr” is an abbreviation for „PHP maker”.\
pmkr is a CLI tool, which helps to compile PHP core and PHP extensions from
source, and manages multiple PHP versions.
In other words, pmkr is a kinda „PHP Version Manager”.

* Downloads the source code from php.net.
* Downloads the desired extensions from pecl.php.net or from a Git repository.
* Based on the ~/.pmkr/pmkr.*.yml configuration detects the required/missing
  packages and helps to install them with apt/yum/zypper etc... (more about this later)
* Based on the ~/.pmkr/pmkr.*.yml configuration runs the `./configure` and `make`
  commands.
* Helps to switch between the installed PHP versions. So you can have different
  PHP version in every terminal window or per project.

Just to make it clear. pmkr does not use any virtualization system (e.g.: Docker),
so it installs everything onto you bare metal. \
Of course, first you can try it out in a Docker container if you wish.


## Installation

pmkr is written in PHP, so to make it work, the first PHP executable has to be
installed somehow else. \
The easies way to install the requirements is to use the package manager of the
OS.

Currently there is no install helper script, so it requires some manual steps.


### Install requirements

**Requirements**:
* PHP >=7.4
  * ext-bz2
  * ext-curl
  * ext-ctype
  * ext-dom
  * ext-hash
  * ext-iconv
  * ext-json
  * ext-libxml
  * ext-mbstring
  * ext-phar
  * additional DEV requirements
    * ext-tokenizer
    * ext-xmlwriter
* patch

**openSUSE tumbleweed**
```bash
zypper install \
    --no-confirm \
    patch \
    php8 \
    php8-bz2 \
    php8-ctype \
    php8-curl \
    php8-dom \
    php8-iconv \
    php8-openssl \
    php8-mbstring \
    php8-phar
```

**Ubuntu 21.10**
```bash
apt-get update
apt-get install \
    -y \
    patch \
    php \
    php-bz2 \
    php-ctype \
    php-curl \
    php-dom \
    php-mbstring \
    php-phar
```

**Fedora**
```bash
dnf install \
    --assumeyes \
    patch \
    php-cli \
    php-bz2 \
    php-ctype \
    php-curl \
    php-dom \
    php-mbstring \
    php-phar
```

**Arch**
```bash
pacman --sync \
    --noconfirm \
    patch \
    php

sed \
    --in-place \
    --expression='s/;extension=bz2/extension=bz2/g' \
    --expression='s/;extension=iconv/extension=iconv/g' \
    '/etc/php/php.ini'
```


### Install pmkr

@todo Method 1: not yet - download pmkr.phar.

@todo Method 2: not yet - phive.

Method 3: Git + [Composer 2.x]
1. Run: `cd into/a/parent`
2. Run:
    ```bash
    git clone \
        --origin='upstream' \
        --branch='1.x' \
        'https://github.com/pmkr/pmkr.git' \
        'pmkr-1.x'

    cd 'pmkr-1.x'
    composer install --no-dev
    SHELL="${SHELL}" ./bin/pmkr init:pmkr
    ```


## Usage

After the installation, the basic usage is:

1. Run: `pmkr list`
2. Run: `pmkr instance:install`


## Configuration

The `pmkr config:export` command can be used in order to get an overview about
the actually used configuration.

The main configuration blocks are:

* [instances](#configuration---instances)
* [cores](#configuration---cores)
* [extensionSets](#configuration---extensionsets)
* [extensions](#configuration---extensions)

To edit the configuration pmkr.*.yml files, it is recommended to use a text
editor which understands the JSONSchema definition and gives autocomplete and
shows the corresponding documentation for the data structures. \
[pmkr configuration JSON schema]


### Configuration - instances

@todo


### Configuration - cores

@todo


### Configuration - extensionSets

@todo


### Configuration - extensions

@todo


## Package dependency installation

On a Linux systems relatively easy to compile PHP from source.
But the most time consuming task is to figure out which package is missing,
based on the error message of the `./configure` command.

An error message can be like this one:
> ... \
> checking for cURL support... yes \
> checking if we should use cURL for url streams... no \
> checking for cURL in default path... not found \
> configure: error: Please reinstall the libcurl distribution \
> ...

In this case not too difficult to figure out that the missing package is
`libcurl-devel` (or isn't it?), but in other cases it is not so obvious what is
missing.

Other problem is that, these kind of error messages come one by one. \
It would be much easier to get one error message about all the problem (missing
*.h files) and fix them in one go.

pmkr tries to solve – or at least mitigate – these difficulties. \
But it comes with a price. \
The name of the package which provides the missing `*.h` header file is vary on
different operating systems. This mapping has to be maintained manually. \
Don't worry, you don't have to do it alone, because pmkr ships this mapping. \
But it can be incomplete or out of date, so patches are welcome.

**Related configuration options**
* `#/cores/*/dependencies/packages`
* `#/extensions/*/dependencies/packages`


## Library dependency installation

On an up-to-date operating system difficult to compile an older PHP version from
source, because it doesn't work with latest packages. \
For example a PHP 5.6 can't be compiled against the latest ICU or openSSL
libraries. \
pmkr tries to solve this problem as well, by installing the required libraries
from source.

**Related configuration options**
* `#/libraries`
* `#/cores/*/dependencies/libraries`
* `#/extensions/*/dependencies/libraries`


## ZPlug

Run: `pmkr zplug:entry`

---

[Composer 2.x]: https://getcomposer.org/download/
[pmkr configuration JSON schema]: ./schema/pmkr-01.schema.yml
