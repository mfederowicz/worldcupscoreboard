# World Cup Score Board

A small application that keeps the scores of football matches that are being played right now.
It can start a match, update its score, finish it, and show all matches in order: most goals
first, and for the same number of goals, the match that started later first.

Everything is kept in memory. There is no framework, no database and no web server.

There are two versions, each in its own folder:

| Folder | Language | Status |
|---|---|---|
| [`php/`](php/) | PHP 8.4 | done |
| [`golang/`](golang/) | Go 1.26 | done |

Each folder is a separate project with its own README, tests and Makefile.
Both use the same two commands:

```
make tests     # runs the tests
make board     # runs a short demo in the terminal
```

## What it looks like

`make board` plays five matches and prints what happens. The output is the same in PHP and in Go:

```
World Cup finals begin:

Started: Mexico - Canada
Score:   Mexico 0 - Canada 5
Started: Spain - Brazil
Score:   Spain 10 - Brazil 2
Started: Germany - France
Score:   Germany 2 - France 2
Started: Uruguay - Italy
Score:   Uruguay 6 - Italy 6
Started: Argentina - Australia
Score:   Argentina 3 - Australia 1

Summary (5 matches in progress):
1. Uruguay 6 - Italy 6
2. Spain 10 - Brazil 2
3. Mexico 0 - Canada 5
4. Argentina 3 - Australia 1
5. Germany 2 - France 2

Finished: Spain 10 - Brazil 2
Finished: Germany 2 - France 2

Summary after finishing (3 matches in progress):
1. Uruguay 6 - Italy 6
2. Mexico 0 - Canada 5
3. Argentina 3 - Australia 1

Finished games (2):
1. Spain 10 - Brazil 2
2. Germany 2 - France 2
```

The summary puts the match with the most goals (home + away) first. If two matches have the
same number of goals, the one that started later comes first:

- Uruguay - Italy and Spain - Brazil both have 12 goals. Uruguay - Italy started later, so it is first.
- Argentina - Australia and Germany - France both have 4 goals. Argentina - Australia started later,
  so it is above Germany - France.

A finished match leaves the summary and moves to the finished games, which use the same order.

## Quick start (PHP)

```
git clone https://github.com/mfederowicz/worldcupscoreboard.git scoreboard
cd scoreboard/php
composer install
make tests
make board
```

More details, the list of special cases and the design choices are in [`php/README.md`](php/README.md).

## Quick start (Go)

```
git clone https://github.com/mfederowicz/worldcupscoreboard.git scoreboard
cd scoreboard/golang
make tests
make board
```

There is nothing to install first: the Go version uses only the standard library.

More details, and a list of what is different from the PHP version, are in
[`golang/README.md`](golang/README.md).
