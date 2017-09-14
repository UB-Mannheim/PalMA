PalMA Installation Instructions
===============================

Operating system
----------------

The PalMA web application requires a web server (usually Apache 2) which
supports PHP 5 and SQLite.

User provided contents are shown using a simple web browser (midori),
an image viewer (feh), a PDF viewer (zathura), a video player (vlc) and a VNC
server.
Office files are converted to PDF by libreoffice and then handed over to
zathura.

PalMA controls running viewers using wmctrl and xdotool.

So a complete PalMA installation can be based on Debian GNU Linux (Version 9
"Stretch").
Just add some required Debian packages (these and all other installation
commands must be run as root user):

    apt-get install midori feh vlc zathura ssvnc x11vnc
    apt-get install apache2 libapache2-mod-php7.0 sqlite3
    apt-get install php7.0-curl php7.0-gd php7.0-intl php7.0-sqlite3 php7.0-mbstring
    apt-get install wmctrl xdotool openbox libjs-jquery

Attention for Debian 8 "Jessie"! Before you can install Midori on Jessie you
must add the
backport repository to `/etc/apt/sources.list`:

    cp /etc/apt/sources.list /etc/apt/sources.list.backup
    echo "deb http://ftp.debian.org/debian jessie-backports main" >>/etc/apt/sources.list
    apt-get update
    apt-get install midori

Some more packages are optional:

    apt-get install gettext git libavcodec-extra make unattended-upgrades

The last one must be configured:

    dpkg-reconfigure unattended-upgrades

More advanced users will also want to configure mail:

    dpkg-reconfigure exim4-config


Apache
------

The PHP default configuration for the Apache2 webserver permits file uploads
up to 2 MB. This limit is too low for typical documents (images,
office documents, pdf). Change the setting `upload_max_filesize` in
`/etc/php/7.0/apache2/php.ini`. 10 MB is a good value. There is another limit
for the maximum size of HTML posts with a default value of 8 MB.
As this is less than the 10 MB needed for file uploads, the setting
`post_max_size` must also be increased by setting it to 10 MB.

PalMA uses `.htaccess` to protect the database and the uploads directory.
To enable this feature, Apache2 needs this section in file
`/etc/apache2/sites-available/000-default.conf`:

    <Directory /var/www/html>
        # "RewriteEngine" needs "FileInfo".
        # "Order" needs "Limit".
        AllowOverride FileInfo Limit
    </Directory>

The Apache2 module `rewrite` must be enabled, too:

    a2enmod rewrite
    service apache2 restart

PalMA
-----

The following description assumes that the web server's root directory
is `/var/www/html` (this is the default since Debian Jessie)
and that PalMA is directly installed there.

Of course it is also possible to install PalMA in any other path.

Get the latest version of PalMA from GitHub:

    # Get latest PalMA. Add --branch v1.1.0 to get that version.
    git clone https://github.com/UB-Mannheim/PalMA.git /var/www/html
    # Create or update translations of PalMA user interface (optional).
    make -C /var/www/html

Typically, PalMA should be started automatically. Activate autostart via
systemd
with
these commands:

    cp /var/www/html/scripts/palma.service /etc/systemd/system
    chmod 755 /etc/systemd/system/palma.service
    systemctl daemon-reload
    systemctl enable palma.service

Now a configuration file `/var/www/html/palma.ini` must be added.
A template for this file is available from subdirectory `examples`, so run
this command to get a preliminary file:

    cp /var/www/html/examples/palma.ini /var/www/html/palma.ini

Some entries in `palma.ini` still need to be fixed for your local installation.
These include at least the entries `theme` and `start_url`.

At last we need to grant write access to www-data so that the web server can
create and modify a sqlite3 database `palma.db`, a directory for file uploads
can be created automatically and some viewer programs can write their
configuration data.

So we add write access for www-data in directory `~www-data` (typically
`/var/www`) by changing the ownership:

    chown -R www-data:www-data /var/www


Customize an installation
-------------------------

Most site specific settings are kept in a special subdirectory under `theme`.
A new PalMA installation can add its own subdirectory which optionally can
include more subdirectories if the installation uses several different
settings.

Each setting includes files for the screensaver, several images and icons,
and a preconfigured Windows executable (UltraVNC SC).
See [theme/THEMES.md](theme/THEMES.md) for details.


How to add existing and new translations
----------------------------------------

PalMA initially supported English and German user interfaces for the web
frontend.
Students from the University of Mannheim provided additional translations. All
translated texts are in the subdirectory `locale`.

Newly added languages also need modifications in `Makefile`
and in `i12n.php`.

In a Debian GNU Linux installation, it is also necessary to add matching
locales, either by running `dpkg-reconfigure locales` manually or by enabling
the locales in `/etc/locale.gen` and running `locale-gen`. Here is an
example which enables the English locale in its US variant (`en_US.UTF-8`):

    perl -pi -e 's/^#.(en_US.UTF-8)/$1/' /etc/locale.gen && locale-gen


Raspberry Pi
------------

A low cost (less than 50 EUR plus monitor) PalMA station can be built using
the Raspberry Pi. The following configuration which is based on the Rasbian
distribution (<http://www.raspbian.org/>) was successfully tested with a
Raspberry Pi 3:

    apt-get install midori feh libjs-jquery nginx-light openbox
    apt-get install php5-cgi php5-cli php5-curl php5-fpm php5-gd php5-intl
php5-sqlite
    apt-get install ssvnc sqlite3 vlc wmctrl xdotool zathura
    mkdir -p /var/www/html
    chown www-data:www-data /var/www/html

We replaced the apache2 web server with nginx because it uses much
less resources. Fix the server root and enable PHP5 in the configuration
file `/etc/nginx/sites-enabled/default`:

    server {
        root /var/www/html;
        index index.html index.htm index.php;
        # ...
        location ~ \.php$ {
                fastcgi_split_path_info ^(.+\.php)(/.+)$;
                fastcgi_pass unix:/var/run/php5-fpm.sock;
                fastcgi_index index.php;
                include fastcgi_params;
        }
    }


Security
--------

We try to fix known security problems but also know that PalMA is not
designed to be used with direct access from the Internet.

PalMA should be operated in an intranet with limited access.
