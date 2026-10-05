# World Cup Score Board

A small library that keeps the scores of football matches that are being played right now.
It can start a match, update its score, finish it, and show all matches in order: most goals
first, and for the same number of goals, the match that started later first.

Everything is kept in memory. There is no framework, no database and no web server.

The same idea is written in two languages, each in its own folder:

| Folder | Language | Status |
|---|---|---|
| [`php/`](php/) | PHP 8.4 | done |
| [`golang/`](golang/) | Go | not started yet |

Each folder is a separate project with its own README, tests and Makefile.
Both use the same two commands:

```
make tests     # runs the tests
make board     # runs a short demo in the terminal
```

## Quick start (PHP)

```
git clone <repository-url> scoreboard
cd scoreboard/php
composer install
make tests
make board
```

More details, the list of special cases and the design choices are in [`php/README.md`](php/README.md).
