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

    cd $CURRENT_DIR

    exit 1
}

CURRENT_DIR=`pwd`

printf "\n\nRunning Unit Tests...\n"
$CURRENT_DIR/vendor/composer/composer/bin/composer test-unit
if [ $? != 0 ]
then
    fail "Unit tests failed."
fi

cd $CURRENT_DIR

exit $?