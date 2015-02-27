Themes - customize a PalMA installation
=======================================

PalMA was initially developed for the Learning Center at Mannheim University
Library. Other institutions want to show different views to their users,
use different authentisation and customize other aspects of PalMA. This
is possible by adding a new theme to PalMA.

How to add a new theme
----------------------

Add a directory for your institution in directory `theme`. If you need more
than one theme, it is also possible to add more directories in the directory
of your institution. Examples: `ub-mannheim/a3`, `ulb-bonn`.

Each theme directory must include these files:

* `background.png`.
  This is the background image which users will see on the team display
  when they work with PalMA. Any user windows will be shown on top of
  this background image.

* `favicon.ico`.
  This icon is typically shown in bookmark lists of browsers or when the
  PalMA URL is saved on a smartphone.

* `palma-logo-49x18.png`.
  This logo is used by PalMA's web interface `index.php`.

* `palma-logo-67x25.png`.
  This logo is used by the login web interface `login.php`.

* `screensaver.php`.
  PalMA shows this URL when no users are connected. It could be a static
  HTML page, but typically some information like a URL or a PIN which is
  created on the fly is shown here, too.
  The themes of ub-mannheim change the image after 10 minutes to avoid
  display burn in. Therefore these additional two files are needed:
  * `palma_d.png`
  * `palma_e.png`

* `winvnc-palma.exe`.
  This is a preconfigured UltraVNC server for Windows.
