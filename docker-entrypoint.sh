#!/bin/sh

# setup environment
sed -i "s,server_name.*\$,server_name ${HOSTNAME};," /etc/nginx/sites-enabled/palma
echo "theme=\"${THEME}\"" > /etc/palma.ini

# start services
/etc/init.d/php7.*-fpm start
/etc/init.d/nginx start
x11vnc -quiet -loop -display :1 > /dev/null 2>&1 &

IP=$(ip add | grep global | awk '{ print $2 }' | cut -d/ -f1)
echo "Hostname: ${HOSTNAME}"
echo "Theme:    ${THEME}"
echo "IP:       ${IP}"
echo
echo "Set up /etc/hosts with: echo '$IP ${HOSTNAME}' >> /etc/hosts"
echo "Connect via vnc: ssvncviewer ${HOSTNAME}"
echo "Connect via browser: http://${HOSTNAME}"
echo

# starting PalMA
/usr/lib/palma/startx
