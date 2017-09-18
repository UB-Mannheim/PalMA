#!/bin/bash

# Arguments list:
# $1 "install" for a fresh installation or "update" for updating an existing one
# $2 directory where PalMA already is or should be installed (recommended: "/var/www/html")
# $3 "rpi" for raspberry pi or "standard" for normal PCs
# $4 no_url or URL to information about PalMA in your institution (e.g. "https://www.your-institution.org/link-to-your-palma-site/")
# $5 the name of the palma station
# $6 the name of the theme
# $7 the url of the palma station

echo "Checking arguments..."
var1_ID=$1
var2_ID=$2
var3_ID=$3
var4_ID=$4
var5_ID=$5
var6_ID=$6
var7_ID=$7

for i in {1..7}; do
    v="var${i}_ID"
    if [ -n "${!v}" ]; then
        echo "$v set to: ${!v}"
    else
        echo "$v is not set! Please check your arguments."
        echo 'Usage: install_palma.sh [install|upgrade] [install dir (e.g. "/var/www/html")] [standard | rpi] [no_url or e.g. "https://www.your-institution.org/link-to-your-palma-site/"] [name, e.g. "palma-01"] [theme, e.g. "demo/simple"] [url of the station, e.g. "http://palma-01.your-institution.org"]'
        exit 1
    fi
done

INSTALL_DIR=$2
INSTITUTION_URL=$4
STATION_NAME=$5
THEME=$6
START_URL=$7

if [$1 == "update"]; then
    echo "Saving old sources list"
    cp /etc/apt/sources.list /etc/apt/sources.list.backup
    echo "Adding Stretch sources"
    cat << EOT > /etc/apt/sources.list
    deb http://ftp.de.debian.org/debian/ stretch main contrib non-free
    deb http://ftp.de.debian.org/debian/ stretch-updates main controb non-free
    deb http://security.debian.org/ stretch/updates main contrib non-free
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
if [!$INSTALL_DIR/palma.ini]; then
    cp $INSTALL_DIR/examples/palma.ini $INSTALL_DIR/palma.ini
fi
if [$INSTITUTION_URL == "no_url"]; then
        $INSTITUTION_URL = ""
fi

cat << EOT > $INSTALL_DIR/palma.ini
;
; palma.ini
;
; Config File for Environment Variables
;
; Copy into the same directory as index.php.
;
; The entries here are available as PHP constants:
;
; CONFIG_DISPLAY
; CONFIG_SSH
; CONFIG_PASSWORD
; CONFIG_PIN
; CONFIG_STATIONNAME
; CONFIG_THEME
; CONFIG_START_URL
; CONFIG_POLICY
; CONFIG_CONTROL_FILE
; CONFIG_UPLOAD_DIR
; CONFIG_INSTITUTION_URL

[display]
; X display id (default: ":1").
id = ":1"
; SSH command to connect to display (optional).
;~ ssh = "ssh palma@localhost";

[general]
; Enable or disable password authorization (default: false).
password = false
; Enable or disable pin authorization (default: true).
pin = true
; Name of this PalMA station (default: host name). Only used for display.
stationname = "$STATION_NAME"
; Theme (style) to use for this station (default: "demo/simple").
theme = "$THEME"
[path]
; URL which users use to connect.
start_url = "$START_URL"
; Privacy policy text (optional).
;policy = "<a href=\"demo/simple/policy.php\">Privacy Policy</a>"
; URL used internally for monitor control (default: control.php).
;control_file = "http://localhost/control.php"
; Directory for file uploads (default: "/var/www/html/uploads").
upload_dir = "$INSTALL_DIR/uploads"
; URL to additional PalMA information on your webpage (default: "")
institution_url = "$INSTITUTION_URL"
EOT

# Webserver configuration - TODO: what if there already is a configuration?
echo "Webserver configuration"
if [ $1 = "rpi" ]; then
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
chown -R www-data:www-data .$INSTALL_DIR/..

if [$1 == "update"]; then
        echo "Rebooting system"
        reboot
    else #$1 == "install"
        echo "Starting PalMA"
        service palma start
    fi

echo "End of $0"
