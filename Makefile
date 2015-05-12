# Makefile for PalMA

# Compile all available translations for the PalMA web interface.

.PHONY: all

SRC=index.php
SRC+=login.php
SRC+=upload.php
SRC+=examples/screensaver/pictureshow.php
SRC+=examples/screensaver/tiles.php
SRC+=selectplace/learningcenter.php
SRC+=selectplace/monitor.php

PO=$(wildcard locale/*.UTF-8/LC_MESSAGES/palma.po)

all: $(patsubst %.po, %.mo, $(PO))

%.mo: %.po
	msgfmt --output-file=$@ $?

palma.po: $(SRC)
	xgettext --default-domain=palma --output-dir=. --from-code=UTF-8 $(SRC)
	perl -pi -e s/charset=CHARSET/charset=UTF-8/ $@

$(PO): palma.po
	msgmerge --update $@ palma.po
	touch $@
