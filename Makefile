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
