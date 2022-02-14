#!/usr/bin/env bash
#
# Copyright © 2021, 2022 Oracle and/or its affiliates.
#
# Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
#

function fail {
    if [ "$1" ]
    then
        echo $1
    fi

    echo
    echo "NOTES:"
    echo "    - It is possible (but NOT recommended) to bypass these safeties and commit your code regardless of the risks with the '--no-verify' option. Choosing to do so may mean that the end product CANNOT be put on the Marketplace. Only build with --no-verify after confirming that no errors exist."
    echo "    - Running PHPCBF can fix X-marked sniff violations."
    echo

    cd $CURRENT_DIR

    exit 1
}

# Ensure the specified base path is valid
if [ -z "$1" ]
then
    fail "Please provide the base path"
elif [ ! -d "$1" ]
then
    fail "$1 is not a valid directory"
fi

#
CURRENT_DIR=`pwd`
cd $1
BASE_PATH=`pwd`
VENDOR_PATH=$BASE_PATH/vendor

if [ ! -d "$VENDOR_PATH" ]
then
    fail "Unable to locate the Composer vendor path. I tried $VENDOR_PATH, but it doesn't exist. Have you run Composer install?"
fi

echo "Checking for latest Magento Codesniffer ruleset version"
CODE_SNIFFER_PACKAGE="magento/magento-coding-standard"
if [[ $(composer outdated -D | grep -c $CODE_SNIFFER_PACKAGE)  -ne 0 ]]; then
  composer update $CODE_SNIFFER_PACKAGE
fi

# Only sniffs changed files that are to be packaged up into modules (files in the Oracle directory)
CODE_SNIFFER_FILES=`git status --porcelain | egrep "^([AM]+)\b" | cut -c 4- | egrep "^\/?Oracle\/"`

if [ "$CODE_SNIFFER_FILES" != "" ]
then
    printf "\n\nRunning Code Sniffer...\n"
    $VENDOR_PATH/bin/phpcs $CODE_SNIFFER_FILES
    if [ $? != 0 ]
    then
        fail "Sniff violations have been found. At the very least, errors should be address. See notes about skipping the sniffer."
    fi
fi

cd $CURRENT_DIR

exit $?