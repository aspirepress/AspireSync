
EXEC := meta/bin/dcexec
# EXEC :=

COMPOSER := $(EXEC) composer

build:
	$(COMPOSER) install

init: init-docker build migrate

init-docker:
	docker compose down --remove-orphans --volumes
	docker compose up -d

migrate:
	$(EXEC) bin/console doctrine:migrations:migrate --no-interaction

test:
	$(COMPOSER) run test

check: check-style test

fix: fix-style

check-style:
	$(COMPOSER) run style:check

fix-style:
	$(COMPOSER) run style:fix

.PHONY: *
