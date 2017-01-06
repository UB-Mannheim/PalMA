PalMA – share a team monitor
============================

Copyright (C) 2014–2017 Universitätsbibliothek Mannheim

Authors: Alexander Wagner, Stefan Weil (UB Mannheim)

This is free software. You may use it under the terms of the
GNU General Public License (GPL). See [docs/gpl.txt](docs/gpl.txt) for details.

Parts of the software use different licenses which are listed
in file [LICENSE](LICENSE).

[![CircleCI](https://circleci.com/gh/UB-Mannheim/PalMA/tree/master.svg?style=svg)](https://circleci.com/gh/UB-Mannheim/PalMA/tree/master)
[![Build Status](https://travis-ci.org/UB-Mannheim/PalMA.svg?branch=master)](https://travis-ci.org/UB-Mannheim/PalMA)

Summary
-------

With PalMA, a group of people can share a team monitor. Different documents,
websites or local desktops can be shown simultaneously in up to four display
sections.

Users authorize to the team monitor and control it by using a web application
which runs on their mobile device or on their notebook.

![PalMA in use](https://raw.githubusercontent.com/UB-Mannheim/ubma-screenshots/master/IMG_5965.JPG)

Hardware requirements
---------------------

The team monitor with adequate size and high resolution is connected
to a computing device (usually a mini pc) running Linux.

A minimal setup can be built with an ARM based mini pc like the
Raspberry PI for less than 100 EUR. It can drive monitors with
HDMI and full HD resolution (1920 x 1080 pixel), but is slow
and only offers limited memory for viewer applications.

A setup with good performance can be built with an Intel NUC for
around 200 EUR. It also provides HDMI and resolutions up to
1920 x 1200 pixel.

A high end setup uses a mini pc with Intel Core i5, more RAM and
a fast solid state disk (SSD). It can drive up to three monitors
(still untested). HDMI allows full HD resolution, display port (DP)
even larger resolution.


Installation
------------

Mannheim University Library develops and installs the PalMA web application
on mini pcs running Debian GNU Linux (Jessie). Other Linux based hardware
and software combination can also be used, but might require some smaller
modifications.

See [INSTALL.md](INSTALL.md) for details.


Client Software
---------------

Microsoft Windows and Mac OS X clients need additional software if users
want to share their desktop. These products were tested successfully
with desktop computers and notebooks:

* UltraVNC – http://www.uvnc.com/ (GNU General Public License)
  This is a free VNC server for Windows.
  Using the Single Click version UltraVNC SC, it is possible
  to address preconfigured displays without any installation.

* RealVNC – http://realvnc.com/ (commercial)
  This VNC server is needed for MacBooks with retina display.
  There is a free VNC server for individual private use.

* x11vnc – http://www.karlrunge.com/x11vnc/ (free)
  This VNC server is included in most Linux distributions.

It is currently not possible to share the desktop of mobile
devices (smartphones) and tablet computers.


Bug reports
-----------

Please file your bug reports to https://github.com/UB-Mannheim/PalMA/issues.
Make sure that you are using the latest version of the software
before sending a report.


Contributing
------------

Bug fixes, new functions, suggestions for new features and
other user feedback are appreciated.

The source code is available from https://github.com/UB-Mannheim/PalMA.
Please prepare your code contributions also on GitHub.


Acknowledgments
---------------

This project uses other free software:

* DropzoneJS – http://www.dropzonejs.com/ (MIT License)
* Font Awesome by Dave Gandy – http://fontawesome.io/ (SIL OFL 1.1, MIT License)
* php-gettext 1.0 – https://launchpad.net/php-gettext (GPL v2 or later)
* Pure CSS modules – http://purecss.io/ (Yahoo BSD License)
* QRcode – http://www.swetake.com/qrcode/index-e.html (BSD License)
* UltraVNC – http://www.uvnc.com/ (GPL)
