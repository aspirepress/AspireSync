#
# Docker-related makefile
#
# Note: relative paths are always from project root (a rule of thumb for all things in meta/)
#

DOCKER = docker
DOCKERFILE = ./docker/Dockerfile
TAG = staging-build

all: dev-image prod-image

up:
	docker compose up --build -d

down:
	docker compose down

destroy:
	docker compose down --remove-orphans --volumes --rmi local

dev-image:
	docker build --target devbuild -t aspiresync-dev -f $(DOCKERFILE) .
	docker build --target devbuild -t aspiresync-dev-build -f $(DOCKERFILE) .

prod-image:
	docker build --platform=linux/amd64,linux/arm64 --target prodbuild -t aspirepress/aspiresync:latest -t aspirepress/aspiresync:$(TAG) -f $(DOCKERFILE) .

authenticate: authenticate-aws docker-login-aws

push-all: push-prod push-dev

tag-prod:
	docker tag aspirepress/aspiresync:$(TAG) ${AWS_ECR_REGISTRY}:$(TAG)
	docker tag aspirepress/aspiresync:latest ${AWS_ECR_REGISTRY}:latest

tag-dev:
	docker tag aspiresync-dev-build ${AWS_ECR_REGISTRY}:dev-build
	docker tag aspiresync-dev-build ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD`


authenticate-aws:
	aws sso login --profile ${AWS_PROFILE}

docker-login-aws:
	aws ecr get-login-password --region us-east-2 --profile ${AWS_PROFILE} | docker login --username AWS --password-stdin ${AWS_ECR_ENDPOINT}

push-prod:
	docker push ${AWS_ECR_REGISTRY}:$(TAG)
	docker push ${AWS_ECR_REGISTRY}:latest

push-dev:
	docker push ${AWS_ECR_REGISTRY}:dev-build
	docker push ${AWS_ECR_REGISTRY}:`git rev-parse --short HEAD`

.PHONY: *
