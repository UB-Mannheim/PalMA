FROM debian:stretch

ARG BROWSER=midori
ARG INITSYS=systemd
ARG PALMANAME=palma-docker
ARG WWWSERV=nginx

ARG DEBIAN_FRONTEND=noninteractive

WORKDIR /tmp

# install most dependencies
RUN  apt-get -q update \
  && apt-get install --no-install-recommends -y \
     ca-certificates python3 x11vnc x11-xserver-utils xserver-xorg-video-dummy feh libreoffice ssvnc vlc x11vnc zathura wmctrl xdotool openbox sqlite3 unclutter php php-cgi php-cli php-curl php-fpm php-gd php-intl php-sqlite3 php-mbstring gettext git libavcodec-extra make wget xorg \
     ${BROWSER} \
     "$(echo ${WWWSERV} | sed -e 's,nginx,nginx-light,' -e 's,apache,apache2 libapache2-mod-php,')" \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# install PalMA
COPY . /tmp
RUN  ./install --browser ${BROWSER} --name ${PALMANAME} --server ${WWWSERV} \
  && mkdir -p /etc/X11/xorg.conf.d/ \
  && install -m 644 examples/10-headless.conf /etc/X11/xorg.conf.d/ \
  && find . -delete \
  && chown -R www-data:www-data /var/www

# expose www and vnc ports
EXPOSE 80 5900

ENV THEME demo/simple

COPY docker-entrypoint.sh /tmp
CMD sh /tmp/docker-entrypoint.sh
