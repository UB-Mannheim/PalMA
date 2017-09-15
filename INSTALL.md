PalMA Installation Instructions
===============================

Requirements
----------------

PalMA runs on Linux (tested on Debian 9 Stretch and Raspbian), needs a webserver with PHP and SQLite and some viewer programs.
Hardware requirements are relatively low. For reasonable performance we recommend something at least as strong as a Raspberry Pi 3.

In the following we will cover the points you'll need to set up a PalMA station:

- Required Debian packages
- Webserver configuration (apache2 and nginx)
- PalMA
- Customizing your installation
- Adding new languages (if needed)

_All installation commands must be run as root user._

Required packages
----------------

With the following lines run as root user we can install the needed viewer programs (for images, PDFs, videos and VNC connections), tools used for windowmanagement, database, PHP modules and building tools.

    apt-get install midori feh vlc zathura ssvnc x11vnc
    apt-get install wmctrl xdotool openbox libjs-jquery sqlite3
    apt-get install php7.0 php7.0-cgi php7.0-cli php7.0-curl
    apt-get install php7.0-fpm php7.0-gd php7.0-intl php7.0-sqlite3 php7.0-mbstring
    apt-get install gettext git libavcodec-extra make

Now we install the webserver (normally apache2, but for Raspberry Pi we recommend nginx-light):

    apt-get install apache2 libapache2-mod-php7.0

or

    apt-get install nginx-light

Webserver configuration
----------------

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

Nginx
------
For Raspberry Pi we replaced the apache2 web server with nginx because it uses much
less resources. Make sure the following configurations (server root, enabling php7) are set in
file `/etc/nginx/sites-enabled/default`:

    server {
        root /var/www/html;
        index index.html index.htm index.php index.nginx-debian.html;
        # ...
        location ~ \.php$ {
                include snippets/fastcgi-php.conf;
                fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
            }
    }


PalMA
----------------

The following description assumes that the web server's root directory
is `/var/www/html` (this is the default since Debian Jessie)
and that PalMA will be installed directly there.
(Of course it is also possible to install PalMA in any other path.)

Get the latest version of PalMA from GitHub:

    # Get latest PalMA.
    git clone https://github.com/UB-Mannheim/PalMA.git /var/www/html
    # Create or update translations of PalMA user interface (optional).
    make -C /var/www/html

Typically, PalMA should be started automatically. Activate autostart via systemd with these commands:

    cp /var/www/html/scripts/palma.service /etc/systemd/system/palma.service
    chmod 755 /etc/systemd/system/palma.service
    systemctl daemon-reload
    systemctl enable palma.service

Now a configuration file `/var/www/html/palma.ini` must be added.
A template for this file is available from subdirectory `examples`, so run
this command to get a preliminary file:

    cp /var/www/html/examples/palma.ini /var/www/html/palma.ini

Please change entries in `palma.ini` according to your local installation.
These include at least the entries `theme` and `start_url`.

At last we need to grant write access to www-data so that the web server can
create and modify a sqlite3 database `palma.db`, a directory for file uploads
can be created automatically and some viewer programs can write their
configuration data.

So we add write access for www-data in directory `~www-data` (typically
`/var/www`) by changing the ownership:

    chown -R www-data:www-data /var/www


Customize your installation
----------------

Most site specific settings are kept in a special subdirectory under `theme`.
A new PalMA installation can add its own subdirectory which optionally can
include more subdirectories if the installation uses several different
settings.

Each setting includes files for the screensaver, several images and icons,
and a (partially) preconfigured files for the VNC feature.
See [theme/THEMES.md](theme/THEMES.md) for details.


Add existing and new translations
----------------

PalMA initially supported English and German user interfaces for the web
frontend.
Students from the University of Mannheim provided additional translations. All
translated texts are in the subdirectory `locale`.

Newly added languages also need modifications in `Makefile` and in `i12n.php`.

In a Debian GNU Linux installation, it is also necessary to add matching
locales, either by running `dpkg-reconfigure locales` manually or by enabling
the locales in `/etc/locale.gen` and running `locale-gen`. Here is an
example which enables the English locale in its US variant (`en_US.UTF-8`):

    perl -pi -e 's/^#.(en_US.UTF-8)/$1/' /etc/locale.gen && locale-gen
