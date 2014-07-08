.PHONY: all

SRC=index.php
SRC+=login.php
SRC+=upload.php
SRC+=examples/screensaver/pictureshow.php
SRC+=examples/screensaver/tiles.php
SRC+=selectplace/learningcenter.php
SRC+=selectplace/monitor.php

PO=
PO+=locale/de_DE.UTF-8/LC_MESSAGES/palma.po
PO+=locale/en_US.UTF-8/LC_MESSAGES/palma.po
PO+=locale/it_IT.UTF-8/LC_MESSAGES/palma.po

all: $(patsubst %.po, %.mo, $(PO))

%.mo: %.po
	msgfmt --output-file=$@ $?

palma.po: $(SRC)
	xgettext --default-domain=palma --output-dir=. --from-code=UTF-8 $(SRC)
	perl -pi -e s/charset=CHARSET/charset=UTF-8/ $@

$(PO): palma.po
	msgmerge --update $@ palma.po
	touch $@
