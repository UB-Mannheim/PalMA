# Makefile for PalMA

# Compile all available translations for the PalMA web interface.

.PHONY: all

DISTDIR=docs/dist

SRC=index.php
SRC+=login.php
SRC+=upload.php
SRC+=$(wildcard examples/screensaver/*.php)
SRC+=$(wildcard selectplace/*.php)
SRC+=$(wildcard theme/*/*/*.php)

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

TRANSLATIONS.md: $(PO)
	perl $(DISTDIR)/find-untranslated.pl --markdown > $@

.git/hooks: .git/hooks/pre-push .git/hooks/pre-commit

.git/hooks/%: $(DISTDIR)/%.sh
	ln -sf ../../$< $@
