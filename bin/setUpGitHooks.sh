#!/usr/bin/env bash
#
# Copyright © 2021, 2022 Oracle and/or its affiliates.
#
# Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
#

BASE_PATH=`pwd -P`

HOOK_NAME=pre-commit
echo "Installing git $HOOK_NAME hook..."
if [ ! -f $BASE_PATH/.git/hooks/$HOOK_NAME ]
then
    sed "s|%%BASE_PATH%%|$BASE_PATH|" $BASE_PATH/bin/hooks/pre-commit.tmpl > $BASE_PATH/.git/hooks/$HOOK_NAME
    chmod +x $BASE_PATH/.git/hooks/$HOOK_NAME

    echo "Done installing $HOOK_NAME hook."
else
    echo -e "\033[0;33mWARNING: The $HOOK_NAME git hook already exists in $BASE_PATH/.git/hooks/. This is typical for subsequent 'composer install' or 'composer update' runs.\033[0;0m"
fi