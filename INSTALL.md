PalMA Installation Instructions
===============================

Operating system
----------------

The PalMA web application requires a web server (usually Apache 2) which
supports PHP 2 and SQLite.

User provided contents are shown using a simple web browser (currently still
netsurf, will be replaced by midori), an image viewer (eog, will be replaced
by feh), a video player (vlc) and an office suite (libreoffice).

PalMA controls running viewers using wmctrl and xdotool.

So a complete PalMA installation can be based on Debian GNU Linux (Jessie)
with these additional Debian packages:

apache2 eog feh libjs-jquery midori netsurf
php5-curl php5-gd php5-intl wmctrl xdotool

The web server wants to create and modifiy a sqlite3 database palma.db,
so www-data needs write access to the installation directory.

For file uploads, a writable directory upload is created automatically.
This also needs write access for www-data to the installation directory.

Some viewer programs want to write their configuration data. This requires
write access for www-data in directory ~www-data (typically /var/www).


 # Activate javascript for Apache.
 $ a2enconf javascript-common



How to add existing and new translations
----------------------------------------

PalMA initially supported English and German user interfaces for the web frontend.
Students from Mannheim University provided additional translations. More translations
can be added on demand.

All translated texts are under subdirectory locale.

Newly added languages also need modifications in Makefile and in gettext.php.

In a Debian GNU Linux installation, it is also necessary to add matching locales,
either by running 'dpkg-reconfigure locales' manually or by enabling the locales
in /etc/locale.gen and running locale-gen. Here is an example which enables
the English locale in its US variant (en_US.UTF-8):

perl -pi -e s/^#.en_US.UTF-8/en_US.UTF-8/ /etc/locale.gen && locale-gen
