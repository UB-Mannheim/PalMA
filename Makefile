.PHONY: all

SRC=index.php
SRC+=login.php
SRC+=upload.php
SRC+=screensaver/pictureshow.php
SRC+=screensaver/tiles.php
SRC+=selectplace/learningcenter.php
SRC+=selectplace/monitor.php

all: locale/de_DE.UTF-8/LC_MESSAGES/palma.mo
all: locale/en_US.UTF-8/LC_MESSAGES/palma.mo

%.mo: %.po
	msgfmt --output-file=$@ $?

palma.po: $(SRC)
	xgettext --default-domain=palma --output-dir=. --from-code=UTF-8 $(SRC)
	perl -pi -e s/charset=CHARSET/charset=UTF-8/ $@

locale/en_US.UTF-8/LC_MESSAGES/palma.po: palma.po
	msgmerge --update $@ palma.po
	touch $@

locale/de_DE.UTF-8/LC_MESSAGES/palma.po: palma.po
	msgmerge --update $@ palma.po
	touch $@
