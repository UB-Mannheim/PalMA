# TODO List for PalMA

## Apache server configuration ==
* "Options MultiViews" in /etc/apache2/sites-available/000-default.conf

## Design
* Consider using HTML5
* Migrate Websockets to HTML5 server-sent events.

## DBConnector.class.php
* Entry 'userid' in table 'window' should refer to user(userid):
* Allow additional flags for constructor:
  * `$flags = SQLITE3_OPEN_READWRITE|SQLITE3_OPEN_CREATE`
  * `$encryption_key`
* Support more than one address for a given username.
* delUser: Remove user only when no address refers to it.

## db.php
* Use db triggers instead of time based polling.
