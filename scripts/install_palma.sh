#!/bin/bash

echo "Checking arguments..."
var1_ID=$1 # [install | update]
var2_ID=$2 # ["/path/to/install-directory"]
var3_ID=$3 # [standard | rpi]
var4_ID=$4 # [no_url | "https://www.your-institution.org/link-to-your-palma-site/"]
var5_ID=$5 # ["name of this PalMA station"]
var6_ID=$6 # ["theme-direcotry"]
var7_ID=$7 # ["http://url-to-this-palma-station.org"]

for i in {1..7}; do
    v="var${i}_ID"
    if [ -n "${!v}" ]; then
        echo "$v set to: ${!v}"
    else
        echo -e "$v is not set! Please check your arguments.\nPlease mind the exact use of double-quotes and trailing slashes."
        echo -e 'Required arguments in required order:\n
        [install | update] - install for fresh PalMA installation, update for upgrading existing PalMA installation (INCLUDES DEBIAN UPGRADE TO STRETCH!)\n
        ["/path/to/install-directory"] - directory where PalMA already is or should be installed - without trailing slash! - recommended: "/var/www/html"\n
        [standard | rpi] - specify if PalMA is installed on a regular pc or a Raspberry Pi\n
        [no_url | "https://www.your-institution.org/link-to-your-palma-site/"] - no_url or URL to information about PalMA in your institution\n
        ["name of this PalMA station"] - the name of the palma station, e.g. "palma-01"\n
        ["theme-directory"]  - name of the theme (sub)directory, e.g. "demo/simple" or "mytheme" or "our-institution/department2"\n
        ["http://url-to-this-palma-station.org"] - url used to connect to this palma station - with trailing slash! - e.g. "http://palma-01.your-institution.org/"'
        exit 1
    fi
done

INSTALL_DIR=$2
INSTITUTION_URL=$4
STATION_NAME=$5
THEME=$6
START_URL=$7

if [ $1 == "update" ]; then
    echo "Saving old sources list"
    cp /etc/apt/sources.list /etc/apt/sources.list.backup
    echo "Adding Stretch sources"
    sed -i 's/jessie/stretch/g' /etc/apt/sources.list
EOT
    # Get OS upgrade
    echo "Getting update..."
    apt-get -y update
    echo "Getting upgrade..."
    apt-get -y upgrade
fi

# Install necessary packages
echo "Installing viewer packages"
apt-get -y install midori feh vlc zathura ssvnc x11vnc
echo "Installing windowcontrol packages"
apt-get -y install wmctrl xdotool openbox libjs-jquery sqlite3
echo "Installing php7.0 packages"
apt-get -y install php7.0 php7.0-cgi php7.0-cli php7.0-curl
apt-get -y install php7.0-fpm php7.0-gd php7.0-intl php7.0-sqlite3 php7.0-mbstring
echo "Installing building tool packages"
apt-get -y install gettext git libavcodec-extra make unattended-upgrades
if [ $3 == "rpi" ]; then
        echo "Installing webserver nginx-light"
        apt-get -y install nginx-light
    else #$3 == "standard"
        echo "Installing webserver apache2"
        apt-get -y install apache2 libapache2-mod-php7.0
fi

# Remove unwanted packages - Done automatically by cfengine? - TODO

# Install PalMA
cd $INSTALL_DIR
if [ $1 == "install" ]; then
        echo "Cloning PalMA"
        git clone https://github.com/UB-Mannheim/PalMA.git $INSTALL_DIR
    else #$1 == update
        echo "Cleaning install directory"
        git stash
        echo "Checking out master"
        git checkout master
        git stash
        echo "Pulling latest PalMA"
        git pull
fi

echo "Writing palma.ini - overwrites pin, password and policy to default values"
if [ !$INSTALL_DIR/palma.ini ]; then
    cp $INSTALL_DIR/examples/palma.ini $INSTALL_DIR/palma.ini
fi
if [ $INSTITUTION_URL == "no_url" ]; then
        $INSTITUTION_URL = ""
fi

sed -i "/^[;]*[ ]*stationname = /c\stationname = $STATION_NAME" $INSTALL_DIR/palma.ini
sed -i "/^[;]*[ ]*theme = /c\theme = $THEME" $INSTALL_DIR/palma.ini
sed -i "/^[;]*[ ]*start_url = /c\start_url = $START_URL" $INSTALL_DIR/palma.ini
sed -i "/^[;]*[ ]*upload_dir = /c\upload_dir = $INSTALL_DIR/uploads" $INSTALL_DIR/palma.ini
sed -i "/^[;]*[ ]*institution_url = /c\institution_url = $INSTITUTION_URL" $INSTALL_DIR/palma.ini || echo "institution_url = $INSTITUTION_URL" >> $INSTALL_DIR/palma.ini

# Webserver configuration - TODO: what if there already is a configuration?
echo "Webserver configuration"
if [ $1 == "rpi" ]; then
        echo "Hier nginx configuration einfügen"
    else
        echo "Hier apache configuration einfügen"
fi

echo "Remove old and add new autostart"
rm /etc/init.d/palma
cp $INSTALL_DIR/scripts/palma.service /etc/systemd/system/palma.service
chmod 755 /etc/systemd/system/palma.service
systemctl daemon-reload
systemctl enable palma.service

echo "Create new languages"
make -C $INSTALL_DIR

echo "Fix ownership"
chown -R www-data:www-data $INSTALL_DIR/..

if [ $1 == "update" ]; then
        echo "Rebooting system"
        systemctl reboot
    else #$1 == "install"
        echo "Starting PalMA"
        service palma start
    fi

echo "End of $0"
#EOF
