#!/bin/bash

set -e

if [ `id -u` -ne 0 ]; then
    echo "This script needs to be run as root, exiting."
    exit 1
fi

scriptdir=/usr/lib/palma-monitor
logdir=/var/log/palma

# generate random hash in order to make the URL non-predictable
hash=`dd if=/dev/urandom count=1024 2>/dev/null| sha1sum -b | cut -d' ' -f1`

# try generating apache2 config
if [ -d /etc/apache2/conf-available ]; then
    if [ -e /etc/apache2/conf-available/palma-monitor.conf ]; then
        read -s -n1 -p "apache2 config file for palma-monitor exists, re-create (y/N)? " ans
        if echo "nN" | grep -q "$ans"; then
            echo -e "\nexiting."
            exit 1
        elif ! echo "yY" | grep -q "$ans"; then
            echo -e "\ninvalid input, exiting."
            exit 1
        fi
        echo
    fi
    echo "Installing /etc/apache2/conf-available/palma-monitor.conf"
    cat > /etc/apache2/conf-available/palma-monitor.conf <<EOF
ScriptAlias "/$hash" "$scriptdir"
<Directory "$scriptdir">
    AllowOverride None
    Options +ExecCGI
    SetHandler cgi-script
    Require all granted
</Directory>
EOF
    a2enconf palma-monitor
    echo "Reloading apache2"
    if [ -d /run/systemd ]; then
        systemctl reload apache2
    else
        /etc/init.d/apache2 reload
    fi
else
    echo "Sorry, only apache is supported as web server for the monitoring station, exiting."
    exit 1
fi
echo
mkdir -p "$scriptdir"
cat > "$scriptdir/monitor" <<EOF
#!/usr/bin/env python3

import base64, os, sys
from datetime import datetime

# log dir relative to script directory (or absolute path)
logdir = '/var/log/palma'

def error(*args, **kwargs):
    """Writes to error log of http server and exits."""
    print(" ".join(map(str,args)), **kwargs, file=sys.stderr)
    sys.exit(1)

def timestamp():
    return datetime.now().replace(microsecond=0).isoformat()

# return standard header to make script GETtable
print("Content-type: text/html\n")

# extract path from URL and sanitize it
path = os.getenv('PATH_INFO')
if path is None:
    error('Could not get PATH_INFO from environment')
frags =[ i for i in path.split('/') if i and len(i) ]
if len(frags) != 2:
    error("Malformed payload in URL: %s" % path)
    
action     = base64.b64decode(frags.pop()).decode('utf-8')
palma_host = frags.pop()

if not os.path.isdir(logdir):
    os.mkdir(logdir)
logfile = palma_host + '.log'
with open(os.path.join(logdir,logfile), 'a') as f:
    print(timestamp(), action, file=f)
EOF
chmod +x "$scriptdir/monitor"
mkdir -p "$logdir"
chown www-data:www-data "$logdir"

proto='http'
if netstat -l -n | grep -q 443; then
    proto='https'
    echo "Listening port 443 detected, assuming https support"
fi
echo "Make sure that your palma.ini contains the following line:"
echo "monitor_url = \"$proto://$(hostname -f)/$hash/monitor\""
