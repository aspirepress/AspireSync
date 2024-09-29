.PHONY: *

OPTS=list

build:
	docker build --target basebuild -t plugin-slurp-base -f ./docker/Dockerfile .
	docker build --target prodbuild -t plugin-slurp -f ./docker/Dockerfile .

run:
	docker run -it plugin-slurp ${OPTS}

run-base:
	docker run -it -v ./code:/opt/plugin-slurp plugin-slurp-base sh