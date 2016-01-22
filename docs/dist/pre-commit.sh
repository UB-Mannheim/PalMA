#!/bin/bash

#------------------------------------------------------------------------------
# NAME
#         pre-commit.sh
#
# SYNOPSIS
#         make .git/hooks/pre-commit
#         # or
#         ln -s ../../pre-commit.sh .git/hooks
#
#         git add ... && git commit;
#
# LICENSE
#        Placed in the Public Domain by Mannheim University Library in 2016
#
# DESCRIPTION
#        Runs the php-codesniffer.php script against any staged PHP files
#        Runs perlcritic on Perl files
#        Runs shellcheck on Shell files
#
# SEE ALSO
#        perlcritic(1), shellcheck(1), phpcs(1), git-diff(1)
#------------------------------------------------------------------------------

# git-diff command
git_diff="git diff --cached --name-only --diff-filter=ACM"

# If there are any added or modified files staged for commit

staged=$($git_diff)
if [[ -z "$staged" ]];then
    echo "Nothing staged"
    exit
fi

# If any PHP files are staged
staged_php=($($git_diff|grep '\.php$'))
if [[ "${#staged_php[@]}" -ne 0 ]];then
    # Run PHP_CodeSniffer
    docs/dist/php-codesniffer.sh "${staged_php[@]}" || exit $?
    echo "PHP sources OK"
fi

# If any Shell (bash) scripts are staged
staged_shell=($($git_diff|grep '\.sh$'))
if [[ "${#staged_shell[@]}" -ne 0 ]];then
    if which shellcheck >/dev/null; then
        out=$(shellcheck --shell bash "${staged_shell[@]}")
        if [[ ! -z "$out" ]];then
            echo "$out";
            exit 1;
        fi
        echo "Shell sources OK"
    else
        echo "shellcheck not installed (In Debian/Ubuntu: shellcheck)"
    fi
fi

# If any Perl files are staged
staged_perl=($($git_diff|grep '\.pl$'))
if [[ "${#staged_perl[@]}" -ne 0 ]];then
    if which perlcritic >/dev/null; then
        perlcritic --verbose 8 --severity 3 "${staged_perl[@]}" || exit $?
        echo "Perl sources OK"
    else
        echo "perlcritic not installed (In Debian/Ubuntu libperl-critic-perl)"
    fi
fi

