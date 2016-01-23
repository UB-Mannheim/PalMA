#!/bin/sh

#------------------------------------------------------------------------------
# NAME
#         pre-push
#
# SYNOPSIS
#         make .git/hooks/pre-push
#         # or
#         ln -s ../../pre-push.sh .git/hooks/pre-push
#
#         git add ... && git commit && git push;
#
# LICENSE
#        Placed in the Public Domain by Mannheim University Library in 2016
#
# DESCRIPTION
#        Runs the check-signed-off.pl perl script
#
#------------------------------------------------------------------------------

which perl >/dev/null || { echo "Perl not installed"; exit 0; }

perl docs/dist/check-signed-off.pl
