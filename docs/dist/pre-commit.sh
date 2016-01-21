#!/bin/bash

changed=$*
if [[ "$changed" = "." ]];then
    changed=$(find . -type f -name '*.php')
fi
if [[ -z "$changed" ]];then
    changed=$(git diff --cached --name-only --diff-filter=ACM)
fi
if echo "$changed"|grep "\.php";then
    CS_PHAR="docs/dist/phpcs.phar"
    if [[ ! -e "$CS_PHAR" ]];then
        curl -s https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar > $CS_PHAR
    fi
    if [[ ! -e "$CS_PHAR" ]];then
        echo "PHP_CodeSniffer not available"
        exit
    fi

    php $CS_PHAR --standard=PSR2 $changed
fi
