# Makefile for PalMA

# Compile all available translations for the PalMA web interface.

.PHONY: all

DISTDIR=docs/dist
LANGUAGES=al_AL ar de_DE en_US es_ES it_IT ru_RU ur_PK zh_CN zh_TW

SRC=index.php
SRC+=login.php
SRC+=upload.php
SRC+=$(wildcard examples/screensaver/*.php)
SRC+=$(wildcard selectplace/*.php)
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
