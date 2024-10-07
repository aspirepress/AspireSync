.PHONY: *

OPTS=list

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

ifeq ($(origin TAGNAME),undefined)
	TAG_NAME=staging-build
else
    TAG_NAME=${TAGNAME}
endif

ifdef NETWORK
	NETWORK_STR=--network=${NETWORK}
endif

DOCKER_DEV_RUN=docker run -it --rm --name assetgrabber-dev -v ./code:/opt/assetgrabber -v ./data:/opt/assetgrabber/data $(NETWORK_STR) --env-file ./.env assetgrabber-dev

build:
	mkdir -p ./build && \
	cp -r ./code/config ./code/src ./code/assetgrabber ./code/composer.* ./build && \
	docker build --target prodbuild -t assetgrabber -t ${AWS_ECR_REGISTRY}:$(TAG_NAME) -t ${AWS_ECR_REGISTRY}:latest -f ./docker/Dockerfile . && \
	rm -fr ./build

build-dev:
	mkdir -p ./build && \
	cp -r ./code/config ./code/src ./code/assetgrabber ./code/composer.* ./build && \
	docker build --target basebuild -t assetgrabber-dev -f ./docker/Dockerfile . && \
	docker build --target prodbuild -t assetgrabber-dev-build -t ${AWS_ECR_REGISTRY}:dev-build -t ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD` -f ./docker/Dockerfile . && \
	rm -fr ./build

run:
	docker run -it --rm assetgrabber sh

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

authenticate: authenticate-aws docker-login-aws

reauthenticate: authenticate

authenticate-aws:
	aws sso login --profile ${AWS_PROFILE}

docker-login-aws:
	aws ecr get-login-password --region us-east-2 --profile ${AWS_PROFILE} | docker login --username AWS --password-stdin ${AWS_ECR_ENDPOINT}

push:
	docker push ${AWS_ECR_REGISTRY}:$(TAG_NAME)
	docker push ${AWS_ECR_REGISTRY}:latest

push-dev:
	docker push ${AWS_ECR_REGISTRY}:dev-build
	docker push ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD`


