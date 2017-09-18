#!/bin/bash

# Arguments list:
# $1 "install" for a fresh installation or "update" for updating an existing one
# $2 directory where PalMA already is or should be installed (recommended: "/var/www/html")
# $3 "rpi" for raspberry pi or "standard" for normal PCs
# $4 no_url or URL to information about PalMA in your institution (e.g. "https://www.your-institution.org/link-to-your-palma-site/")
# $5 the name of the palma station

echo "Checking arguments..."
var1_ID=$1
var2_ID=$2
var3_ID=$3
var4_ID=$4
var5_ID=$5

for i in {1..5}; do
    v="var${i}_ID"
    if [ -n "${!v}" ]; then
        echo "$v set to: ${!v}"
    else
        echo "$v is not set! Please check your arguments."
        echo 'Usage: install_palma.sh [install|upgrade] [install dir (e.g. "/var/www/html")] [standard | rpi] [no_url or e.g. "https://www.your-institution.org/link-to-your-palma-site/"] [name, e.g. "palma-bwl-01"]'
        exit 1
    fi
done

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
cd $2
if [ $1 == "install" ]; then
        echo "Cloning PalMA"
        git clone https://github.com/UB-Mannheim/PalMA.git $2
    else #$2 == update
        echo "Cleaning install directory"
        git stash
        echo "Checking out master"
        git checkout master
        git stash
        echo "Pulling latest PalMA"
        git pull
fi

echo "Adding new lines to palma.ini"
# TODO: what if there already is a palma.ini with the institution_url line?
# what about the theme, pin, password, policy etc.?
# what about the domain (like .bib.uni-mannheim.de)?

if [!$2/palma.ini]; then
    cp $2/examples/palma.ini $2/palma.ini
fi

if [$4 == "no_url"]; then
        cat << EOT >> $2/palma.ini
        ; URL to additional PalMA information on your webpage (default: "")
        institution_url = ""
        EOT
    else #$4 == "https://someurl.org/palma-info" or whatever
        cat << EOT >> $2/palma.ini
        ; URL to additional PalMA information on your webpage (default: "")
        institution_url = $4
        EOT
fi


echo "Updating the hostname"
# Updates the hostname of a PalMA station to the first argument of this script.
# Resets the host in /etc/hostname and /etc/hosts.
# Resets the station name in /var/www/html/palma.ini.
#
# NOTE: Expects hostnames of the form "lc00" and expects station names to begin with "LC "

# TODO:
# - Replace the SSH keys.
# - Enable other name formats

name="$5"
files=("/etc/hostname" "/etc/hosts" "$2/palma.ini")

name_uc="LC ${name:2:3}"

for file in "${files[@]}"
do
    if [ -f "$file" ]
    then
        sed -i "s/lc[0-9][0-9]/$name/g" "$file"
        sed -i "s/LC [0-9][0-9]/$name_uc/g" "$file"
    else
        echo "File with filename '$file' does not exist."
    fi
done



# Webserver configuration - TODO: what if there already is a configuration?
echo "Webserver configuration"
if [ $1 = "rpi" ]; then
        echo "Hier nginx configuration einfügen"
    else
        echo "Hier apache configuration einfügen"
fi

echo "Remove old and add new autostart"
rm /etc/init.d/palma
cp $2/scripts/palma.service /etc/systemd/system/palma.service
chmod 755 /etc/systemd/system/palma.service
systemctl daemon-reload
systemctl enable palma.service

echo "Create new languages"
make -C $2

echo "Fix ownership"
chown -R www-data:www-data $2

if [$1 == "update"]; then
        echo "Rebooting system"
        reboot
    else #$1 == "install"
        echo "Starting PalMA"
        service palma start
    fi

echo "End of $0"
