# Makefile for PalMA

# Compile all available translations for the PalMA web interface.

.PHONY: all install phpcs phpbf

DISTDIR=docs/dist
LANGUAGES=$(shell cd locale && ls -d *.UTF-8 | sed s/.UTF-8//)

SRC=index.php
SRC+=i12n.php
SRC+=login.php
SRC+=upload.php
SRC+=$(wildcard examples/screensaver/*.php)
SRC+=$(wildcard theme/*/*/*.php)

PO=$(wildcard locale/*.UTF-8/LC_MESSAGES/palma.po)

all: $(patsubst %.po, %.mo, $(PO)) locale/README.md

%.mo: %.po
	msgfmt --output-file=$@ $?

palma.po: $(SRC)
	xgettext --default-domain=palma --keyword=__ --package-name=palma \
		 --output-dir=. --from-code=UTF-8 $(SRC)
	perl -pi -e s/charset=CHARSET/charset=UTF-8/ $@

$(PO): palma.po
	msgmerge --update $@ palma.po
	touch $@

locale/README.md: $(PO)
	perl $(DISTDIR)/find-untranslated.pl --markdown $(LANGUAGES) >$@

.git/hooks: .git/hooks/pre-push .git/hooks/pre-commit

.git/hooks/%: $(DISTDIR)/%.sh
	ln -sf ../../$< $@

install: all
	sudo ./install

phpcs:
	-phpcs -n --standard=PSR2 --file-list=.phpcs.list

phpcbf:
	-phpcbf -n --standard=PSR2 --file-list=.phpcs.list
