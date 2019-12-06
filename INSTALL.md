# PalMA Installation Instructions

## Introduction

You can install PalMA using Debian packages or manually, using the
installation script `install`. For a list of options, run `./install
--help`.

In the following we will cover the points you'll need to set up a PalMA station:

* Requirements
* Required Debian packages
* Webserver configuration
* PalMA
* Theming your installation
* Adding new languages

The default paths when using the installation script are:
* `/usr/share/palma`: location of data files and the web content shown
  in the browser
* `/usr/lib/palma`: location of the `startx` shell script controlling
  the processes related to PalMA
* `/etc`: location of `palma.ini`, the main settings file
* `/var/lib/palma`: location of `palma.db`, the user database

_All installation commands must be run as root user._

## Requirements

For a PalMA station you need a computing device (e.g. a regular PC or a Raspberry Pi) with internet access and a monitor connected to it. The larger the screen, the greater the benefit.

PalMA runs on Linux (tested on Debian 9 Stretch and Raspbian), needs a webserver with PHP and SQLite and some viewer programs.
Hardware requirements are relatively low. For reasonable performance we recommend something at least as powerful as a Raspberry Pi 3.

## Required packages

With the following lines we can install the needed viewer programs (for images, PDFs, videos and VNC connections), tools used for window management, database, PHP modules and building tools.

    apt-get install midori feh libjpeg-turbo-progs libreoffice ssvnc vlc x11vnc zathura
    apt-get install wmctrl xdotool openbox libjs-jquery sqlite3
    apt-get install php php-cgi php-cli php-curl
    apt-get install php-fpm php-gd php-intl php-sqlite3 php-mbstring
    apt-get install gettext git libavcodec-extra gstreamer1.0-libav make
    apt-get install apache2 libapache2-mod-php

Instead of apache2 it is also possible to use nginx, for example on weaker machines.

    apt-get install nginx-light

## Webserver configuration

Example configuration files are provided in the `examples`
subdirectory, particularly
* `palma.apache.conf`: configuration file for apache, should be copied to `/etc/apache2/conf-available/palma.conf` and then be activated by `a2enconf palma`
* `palma.nginx.conf`: configuration file for nginx, should be copied to `/etc/ngnx/sites-available/palma` and linked to `/etc/ngnx/sites-enabled/palma`
* `palma.php.ini`: php configuration file for apache2 and nginx, should be copied to `/etc/php/7.x/apache2/conf.d/palma.ini` or `/etc/php/7.x/fpm/conf.d/palma.ini`, respectively.

### Apache

The PHP default configuration for the Apache2 webserver permits file
uploads up to 2 MB. This limit is too low for typical documents
(images, office documents, pdf). There is another limit for the
maximum size of HTML posts with a default value of 8 MB.  As this is
less than the 10 MB needed for file uploads, the setting
`post_max_size` must also be increased by setting it to 10 MB.  Refer
to `examples/palma.php.ini` for ready-to-use settings and copy the
file to its proper location (see above).

PalMA uses `.htaccess` to protect the database and the uploads directory.
To enable this feature, Apache2 needs this section in file
`/etc/apache2/sites-available/000-default.conf`:

    <Directory /var/www/html>
        # "RewriteEngine" needs "FileInfo".
        # "Order" needs "Limit".
        AllowOverride FileInfo Limit
    </Directory>
    
or use `examples/palma.apache.conf`.

The Apache2 module `rewrite` must be enabled, too:

    a2enmod rewrite
    service apache2 restart

### Nginx

When using nginx instead of apache2 use `examples/palma.nginx.conf` as
template for site configuration (server root, enabling php).

## PalMA

When installing PalMA from Debian packages, you will need the main
`palma` package and either `palma-nginx` or `palma-apache` depending
on the web server you intend to use.

Download the packages to your designated PalMA host and run (as root):

    dpkg -i palma*.deb # in your download directory
    apt-get -f install # pull missing dependencies
     
If everything went well, PalMA should automatically start up.
For customizing themes and other options, please edit `/etc/palma.ini`.

If you are installing PalMA manually, using the installation script,
you can either pull the git repository or download it as a zip file and unpack it to a directory on the designated PalMA host. After that, just run `./install` as root or `./install -v` for verbose output.

Now you should have your own PalMA station up and running.  See the
next two sections on how to customize your installation and how to add
new languages to it.

## Upgrading

Older versions of PalMA kept all files in `/var/www/html`. In order to
remove it, an uninstall script is provided in the `scripts` directory.

## Theming your installation

PalMA was initially developed for the Learning Center at Mannheim University
Library. So the looks of PalMA are coherent with our design.
If you want to customize the design you can add a new theme.
Add one or more directories for your institution in the directory `theme`, e.g.:

    theme/our-institution/department1
    theme/our-institution/department2
    theme/some-other-institution
    theme/your-institution

_To change colors, icons and backgrounds in the user interface you will have to edit_ `palma.css` _and_ `images/user_background.png`.
_Better theming options might follow in future releases._

Each theme directory must include these files:

* `background.png` - the background image on the team display. Any user windows will be shown on top of this background image. (Not to be confused with `images/user_background.png` that users see in the background of the user interface on their devices.)
* `favicon.ico` - icon typically shown in bookmark lists of browsers or when the PalMA URL is saved on a smartphone.
* `palma-logo-49x18.png` - logo used by PalMA's web interface `index.php`.
* `palma-logo-67x25.png` - logo used by the login web interface `login.php`.
* `screensaver.php` - is shown when no users are connected. It includes dynamically generated URL, PIN and QR-Code as well as background images. To prevent display burn-in it changes between two designs every few minutes. So we use different background images with English and German usage instructions:
  * `palma_d.png`
  * `palma_e.png`
* VNC software for screensharing
  * `winvnc-palma.exe` - an UltraVNC server for Windows, that **must be [preconfigured](http://www.uvnc.com/docs/uvnc-sc.html]) to suit your institution**.
  * `x11.sh` - a script used for VNC screensharing on Linux.

Don't forget to enable your theme in `palma.ini`.

## Add existing and new translations

PalMA initially supports English and German user interfaces for the web
frontend. Please help us by providing additional translations for everyone on GitHub.

All translated texts are in the subdirectory `locale`.
Newly added languages also need modifications in `Makefile` and in `i12n.php`.

In a Debian GNU Linux installation, it is also necessary to add matching
locales, either by running `dpkg-reconfigure locales` manually or by enabling
the locales in `/etc/locale.gen` and running `locale-gen`. Here is an
example which enables the English locale in its US variant (`en_US.UTF-8`):

    perl -pi -e 's/^#.(en_US.UTF-8)/$1/' /etc/locale.gen && locale-gen
