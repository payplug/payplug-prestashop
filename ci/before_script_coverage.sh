#!/bin/bash
# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Install git (the php image doesn't have it) which is required by composer
apt-get update -yqq
apt-get install git -yqq

# Install and enable Xdebug
pecl install xdebug
docker-php-ext-enable xdebug

# Install composer version 1
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=1.10.16

# Install all project dependencies
composer install
