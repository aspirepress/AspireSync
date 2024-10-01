.PHONY: *

OPTS=list

build:
	docker build --target basebuild -t asset-grabber-base -f ./docker/Dockerfile .
	docker build --target prodbuild -t asset-grabber -f ./docker/Dockerfile .

run:
	docker run -it asset-grabber ${OPTS}

dev-install-composer:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base composer install

dev-update-composer:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base composer update

run-dev:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base sh

check: csfix cs quality test

quality:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base sh -c "./vendor/bin/phpstan"

test:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base sh -c "./vendor/bin/phpunit"

cs:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base sh -c "./vendor/bin/phpcs"

csfix:
	docker run -it -v ./code:/opt/asset-grabber asset-grabber-base sh -c "./vendor/bin/phpcbf"
