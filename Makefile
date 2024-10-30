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

DOCKER_RUN=docker compose run -it --rm aspiresync

TEST_DB_NAME ?= aspirecloud_testing
TEST_DB_USER ?= test
TEST_DB_PASS ?= test

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
	docker compose down --remove-orphans

up:
	docker compose up -d

check: csfix cs quality test

quality:
	${DOCKER_RUN} ./vendor/bin/phpstan --memory-limit=2G

test: reset-testing-database
	${DOCKER_RUN} ./vendor/bin/phpunit

unit:
	${DOCKER_RUN} ./vendor/bin/phpunit --testsuite=unit

functional:
	${DOCKER_RUN} ./vendor/bin/phpunit --testsuite=functional

cs:
	${DOCKER_RUN} ./vendor/bin/phpcs

csfix:
	${DOCKER_RUN} ./vendor/bin/phpcbf

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

psql:
	@${DOCKER_RUN} bash -c "PGPASSWORD=${DB_PASS} psql -h ${DB_HOST} -U ${DB_USER} ${DB_NAME}"

reset-testing-database: drop-testing-database create-testing-database migrate-testing seed-testing

drop-testing-database:
	${DOCKER_RUN} sh -c "export PGPASSWORD=${DB_ROOT_PASS} && psql -U ${DB_ROOT_USER} -h ${DB_HOST} -c 'drop database if exists ${TEST_DB_NAME}'"

create-testing-database:
	-${DOCKER_RUN} sh -c "export PGPASSWORD=${DB_ROOT_PASS} && psql -U ${DB_ROOT_USER} -h ${DB_HOST} -c \"create role ${TEST_DB_USER} login password '${TEST_DB_PASS}'\""
	${DOCKER_RUN} sh -c "export PGPASSWORD=${DB_ROOT_PASS} && psql -U ${DB_ROOT_USER} -h ${DB_HOST} -c 'create database ${TEST_DB_NAME} owner ${TEST_DB_USER}'"
	${DOCKER_RUN} sh -c "export PGPASSWORD=${TEST_DB_PASS} && psql -U ${TEST_DB_USER} -h ${DB_HOST} ${TEST_DB_NAME} < tests/test-schema.sql"

migrate-testing:

seed-testing:
