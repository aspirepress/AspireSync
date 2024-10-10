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

build-local: build-prod build-dev ## Builds all the local containers for prod and dev

build-all: build-prod-aws build-dev-aws ## Builds all containers by way of building the AWS containers

push-all: push push-dev ## Pushes all prod and dev containers to AWS

build-prod:
	mkdir -p ./build && \
	cp -r ./code/config ./code/src ./code/bin ./code/composer.* ./build && \
	docker build --platform=linux/amd64,linux/arm64 --target prodbuild -t aspirepress/assetgrabber:latest -t aspirepress/assetgrabber:$(TAG_NAME) -f ./docker/Dockerfile . && \
	rm -fr ./build

build-dev:
	mkdir -p ./build && \
	cp -r ./code/config ./code/src ./code/bin ./code/composer.* ./build && \
	docker build --target devbuild -t assetgrabber-dev -f ./docker/Dockerfile . && \
	docker build --target devbuild -t assetgrabber-dev-build -f ./docker/Dockerfile . && \
	rm -fr ./build

build-prod-aws:
	make build-prod && \
	docker tag aspirepress/assetgrabber:$(TAG_NAME) ${AWS_ECR_REGISTRY}:$(TAG_NAME) && \
	docker tag aspirepress/assetgrabber:latest ${AWS_ECR_REGISTRY}:latest

build-dev-aws:
	make build-dev && \
	docker tag assetgrabber-dev-build ${AWS_ECR_REGISTRY}:dev-build && \
	docker tag assetgrabber-dev-build ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD`
run:
	docker run -it --rm assetgrabber sh

dev-install-composer:
	${DOCKER_DEV_RUN} composer install

dev-update-composer:
	${DOCKER_DEV_RUN} composer update

run-dev:
	${DOCKER_DEV_RUN} sh

init: build-dev dev-install-composer migrate seed

check: csfix cs quality test

quality:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpstan --memory-limit=2G"

test:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpunit"

unit:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpunit --testsuite=unit"

functional:
	${DOCKER_DEV_RUN} sh -c "./vendor/bin/phpunit --testsuite=functional"

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

migrate: ## Run database migrations
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx migrate -c db/phinx.php"

migration-rollback: ## Rollback database migrations
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx rollback -e development -c db/phinx.php"

seed: ## Run database seeds
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx seed:run -c db/phinx.php"

_empty-database: # internal target to empty database
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx migrate -c db/phinx.php -t 0"

migrate-testing: ## Run database migrations
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx migrate -e testing -c db/phinx.php"

seed-testing: ## Run database seeds
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx seed:run -e testing -c db/phinx.php"

_empty-testing-database: # internal target to empty database
	${DOCKER_DEV_RUN} sh -c "vendor/bin/phinx migrate -e testing -c db/phinx.php -t 0"

reset-database: _empty-database migrate seed ## Clean database, run migrations and seeds

reset-testing-database: _empty-testing-database migrate-testing seed-testing
