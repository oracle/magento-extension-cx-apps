#!/usr/bin/env bash
#
# Copyright © 2021, 2022 Oracle and/or its affiliates.
#
# Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
#

apt-get update
apt-get install zip unzip

echo "installing composer..."
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

echo "installing auth file..."
cat $MAGENTO_AUTH > auth.json

echo "installing dependencies..."
composer install --no-dev

echo "done."
