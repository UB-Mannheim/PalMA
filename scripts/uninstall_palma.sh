#!/bin/sh

set -e

systemctl stop palma
systemctl disable palma
rm -f /etc/systemd/system/palma.service
systemctl daemon-reload

rm -rf /var/www/html
systemctl stop lightdm
skill -u lightdm
apt-get purge lightdm\*
apt-get purge apache\*

rm -rf /usr/share/palma /var/lib/palma /tmp/palma
rm -f /etc/palma.ini
