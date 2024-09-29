.PHONY: *

OPTS=list

build:
	docker build -t plugin-slurp -f ./docker/Dockerfile .

run:
	docker run -it plugin-slurp ${OPTS}