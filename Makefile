.PHONY: *

OPTS=list

DOCKER_DEV_RUN=docker run -it -v ./code:/opt/assetgrabber -v ./data:/opt/assetgrabber/data assetgrabber-base

build:
	docker build --target basebuild -t assetgrabber-base -f ./docker/Dockerfile .
	docker build --target prodbuild -t assetgrabber -f ./docker/Dockerfile .

run:
	docker run -it assetgrabber ${OPTS}

dev-install-composer:
	${DOCKER_DEV_RUN} composer install

dev-update-composer:
	${DOCKER_DEV_RUN} composer update

run-dev:
	${DOCKER_DEV_RUN} sh

init: build

check: csfix cs quality test

quality:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpstan"

test:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpunit"

cs:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpcs"

csfix:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpcbf"
