
COMPOSER = composer
#COMPOSER = docker compose run -it --rm aspiresync composer

build:
	$(COMPOSER) install

test:
	$(COMPOSER) run test

check: check-style test

fix: fix-style

check-style:
	$(COMPOSER) run style:check

fix-style:
	$(COMPOSER) run style:fix

.PHONY: *
