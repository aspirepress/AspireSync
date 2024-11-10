
DB_FILE ?= data/aspiresync.sqlite

ifneq (,$(wildcard ./.env))
    include .env
    export
endif

ifeq ($(origin TAGNAME),undefined)
	TAG_NAME=staging-build
else
    TAG_NAME=${TAGNAME}
endif

DOCKER_RUN=docker compose run -it --rm aspiresync


.PHONY: *

build-local: build-prod build-dev ## Builds all the local containers for prod and dev

build-all: build-prod-aws build-dev-aws ## Builds all containers by way of building the AWS containers

push-all: push push-dev ## Pushes all prod and dev containers to AWS

build-prod:
	docker build --platform=linux/amd64,linux/arm64 --target prodbuild -t aspirepress/aspiresync:latest -t aspirepress/aspiresync:$(TAG_NAME) -f ./docker/Dockerfile .

build-dev:
	docker build --target devbuild -t aspiresync-dev -f ./docker/Dockerfile .
	docker build --target devbuild -t aspiresync-dev-build -f ./docker/Dockerfile .

build-prod-aws: build-prod
	docker tag aspirepress/aspiresync:$(TAG_NAME) ${AWS_ECR_REGISTRY}:$(TAG_NAME)
	docker tag aspirepress/aspiresync:latest ${AWS_ECR_REGISTRY}:latest

build-dev-aws: build-dev
	docker tag aspiresync-dev-build ${AWS_ECR_REGISTRY}:dev-build
	docker tag aspiresync-dev-build ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD`

run:
	${DOCKER_RUN} bash

composer-install:
	${DOCKER_RUN} composer install

composer-update:
	${DOCKER_RUN} composer update

init: down build-dev up composer-install

down:
	docker compose down --remove-orphans --volumes --rmi local

up:
	docker compose up --build -d

check: cs quality test

quality:
	${DOCKER_RUN} ./vendor/bin/phpstan --memory-limit=2G

test:
	${DOCKER_RUN} ./vendor/bin/phpunit

unit:
	${DOCKER_RUN} ./vendor/bin/phpunit --testsuite=unit

functional:
	${DOCKER_RUN} ./vendor/bin/phpunit --testsuite=functional

cs:
	${DOCKER_RUN} ./vendor/bin/phpcs --parallel=4

fix:
	${DOCKER_RUN} ./vendor/bin/phpcbf --parallel=4

sqlite:
	${DOCKER_RUN} sqlite3 $(DB_FILE)

#### Container registry management

authenticate: authenticate-aws docker-login-aws

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
