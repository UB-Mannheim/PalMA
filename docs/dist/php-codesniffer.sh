#!/bin/bash

#------------------------------------------------------------------------------
# NAME
#         php-codesniffer.sh
#
# SYNOPSIS
#         php-codesniffer.sh [file/directory...]
#
# LICENSE
#        Placed in the Public Domain by Mannheim University Library in 2016
#
# DESCRIPTION
#        Check PHP files for code standard conformance using PHP_CodeSniffer
#
#        Will use phpcs(1) if is found in the $PATH or download the
#        PHP_CodeSniffer PHAR to the directory of the script and
#        execute it using the system's php(1).
#
# ENVIRONMENT
#       $PHP_CODE_STANDARD : Code standard to use. Defaults to 'PSR2'
#
# SEE ALSO
#        phpcs(1), php(1), curl(1)
#------------------------------------------------------------------------------

DIR="$(dirname "$(readlink -f "$0")")"
PHP_CODE_STANDARD=${PHP_CODE_STANDARD:-$DIR}
CS_PHAR="$DIR/phpcs.phar"
CS_URL='https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar'

[[ -z "$*" ]] && { echo "No arguments given"; exit 0; }
CODE_SNIFFER=$(which phpcs)
if [[ -z "$CODE_SNIFFER" ]];then
    which 'curl' >/dev/null || { echo "Please install curl."; exit 0; }
    which 'php' >/dev/null || { echo "Please install php."; exit 0; }
    [[ ! -e "$CS_PHAR" ]] && { curl -o "$CS_PHAR" "$CS_URL"; }
    [[ ! -e "$CS_PHAR" ]] && { echo "PHP_CodeSniffer not available"; exit 0; }
    CODE_SNIFFER="php $CS_PHAR"
fi

$CODE_SNIFFER --standard="$PHP_CODE_STANDARD" -s "$@"
