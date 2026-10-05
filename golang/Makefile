.PHONY: build board tests

build:
	go build -o bin/board ./cmd/board

board: build
	./bin/board

tests:
	go test -v -race ./...
