#!/bin/bash

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
then
    echo -e "\033[1;91m"
    echo "UOJ ERROR: FAILED to download Composer: invalid checksum."
    echo -e "\033[0m"
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
if [ $? -ne 0 ]; then
    echo -e "\033[1;91m"
    echo "UOJ ERROR: FAILED to setup Composer."
    echo -e "\033[0m"
    exit 1
fi
rm composer-setup.php

./composer.phar config repo.packagist composer https://mirrors.aliyun.com/composer/
if [ $? -ne 0 ]; then
    echo -e "\033[1;91m"
    echo "UOJ ERROR: FAILED to use the local mirror of Composer. Please open composer-setup.sh to change it to an available local mirror."
    echo -e "\033[0m"
    exit 1
fi

./composer.phar clear-cache && ./composer.phar update
if [ $? -ne 0 ]; then
    echo -e "\033[1;91m"
    echo "UOJ ERROR: FAILED to run Composer to install required PHP packages."
    echo -e "\033[0m"
    exit 1
fi