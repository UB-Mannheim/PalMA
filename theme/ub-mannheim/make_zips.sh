#!/bin/sh

for dir in a3 a5 be bwl lc; do
    cp "$dir/helpdesk.txt" winvnc-palma
    (cd winvnc-palma && zip "../$dir/winvnc-palma.zip" *)
done
rm -f winvnc-palma/helpdesk.txt
