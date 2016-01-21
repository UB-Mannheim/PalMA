#!/bin/sh

which perl >/dev/null || { echo "Perl not installed";  exit; }

perl docs/dist/check-signed-off.pl
