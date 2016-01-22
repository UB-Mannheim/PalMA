#!/bin/bash

CODE_SNIFFER=$(which phpcs)
if [[ -z "$CODE_SNIFFER" ]];then
    CS_PHAR="docs/dist/phpcs.phar"
    if [[ ! -e "$CS_PHAR" ]];then
        curl -o "$CS_PHAR" 'https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar'
    fi
    if [[ ! -e "$CS_PHAR" ]];then
        echo "PHP_CodeSniffer not available"
        exit
    fi
    CODE_SNIFFER="php $CS_PHAR"
fi

changed=$(git diff --cached --name-only --diff-filter=ACM|grep -o '\.php')
if [[ -z "$changed" ]];then
    changed=$(find . -type f -name '*.php')
fi
if echo "$changed"|grep "\.php";then
    $CODE_SNIFFER --standard=PSR2 $changed
fi
