.PHONY: *

OPTS=list

build:
	docker build --target basebuild -t plugin-slurp-base -f ./docker/Dockerfile .
	docker build --target prodbuild -t plugin-slurp -f ./docker/Dockerfile .

run:
	docker run -it plugin-slurp ${OPTS}

dev-install-composer:
	docker run -it -v ./code:/opt/plugin-slurp plugin-slurp-base composer install

dev-update-composer:
	docker run -it -v ./code:/opt/plugin-slurp plugin-slurp-base composer update

run-dev:
	docker run -it -v ./code:/opt/plugin-slurp plugin-slurp-base sh
