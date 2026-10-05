.PHONY: build board example tests

build:
	go build -o bin/board ./cmd/board

board: build
	./bin/board

example:
	go run ./examples/basic

tests:
	go test -v -race ./...
