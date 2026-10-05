# World Cup Score Board — Go

A small library that keeps the scores of football matches that are being played right now.
Everything is kept in memory. There is no framework, no database and no web server.
It uses only the Go standard library.

This is the same idea as the PHP version in [`../php/`](../php/), with the same names and the
same rules, written the way Go code is usually written.

## What you need

- Go 1.26.5 or newer
- make

## How to run it

```
git clone https://github.com/mfederowicz/worldcupscoreboard.git scoreboard
cd scoreboard/golang
make tests     # runs the tests: go test -v -race ./...
make board     # builds the demo and runs it: ./bin/board
make example   # runs a short example: go run ./examples/basic
```

There is nothing to install first, because the code has no dependencies.

The demo starts five matches, one every half a second, and sets their scores. Then it shows the
board, finishes two matches, and shows the board and the finished matches again.

The example in `examples/basic/main.go` is one plain Go file with comments. It starts two matches,
updates the scores, shows the board, finishes a match and shows one error. It is the quickest
way to see how to call the code from your own program.

### Building the demo

Go code is compiled before it runs. `make board` does both steps for you. To do them by hand:

```
make build     # compiles the demo to bin/board: go build -o bin/board ./cmd/board
./bin/board    # runs it
```

The `bin/` folder is not kept in git. To run the demo without keeping a file, use
`go run ./cmd/board`.

## How to use it

```go
import "worldcupscoreboard/internal/scoreboard"

board := scoreboard.NewScoreBoard(scoreboard.NewInMemoryFootballMatchRepository())

match, err := scoreboard.NewFootballMatch(scoreboard.NewUUID(), "Mexico", "Canada")
if err != nil {
	// the team names are not allowed
}

err = board.StartGame(match)           // the match is on the board, 0 - 0
err = board.UpdateScore(match, 0, 5)   // sets the score to 0 - 5
board.Summary()                        // matches being played, in order
err = board.FinishGame(match)          // the match leaves the board
board.FinishedGames()                  // finished matches, in the same order
```

Go has no exceptions. A function that can fail returns an error as its last value, and the
caller checks it. A full, working example is in `examples/basic/main.go`.

### Order of the matches

`Summary()` and `FinishedGames()` put the match with the most goals (home + away) first.
If two matches have the same number of goals, the one that started later comes first.

## How the code is organised

```
Makefile                                      build, board, example, tests
go.mod                                        the name of the module and the Go version
cmd/
  board/
    main.go                                   the demo
    main_test.go
examples/
  basic/
    main.go                                   a short program that uses the score board
internal/
  scoreboard/                                 the score board (one Go package)
    score_board.go                            start, update, finish, summary
    football_match.go                         one match: teams, score, start and finish
    football_match_repository.go              where matches are stored (interface)
    in_memory_football_match_repository.go    keeps matches in a slice
    uuid.go                                   the id of a match, and how a new one is made
    errors.go                                 all errors the score board can report
    *_test.go                                 the tests, next to the code they test
```

`ScoreBoard` gets its storage from outside, in `NewScoreBoard`. To keep matches in a database
instead of memory, you write one new type with the four methods of `FootballMatchRepository`
and pass it in. Nothing else has to change.

### What is different from the PHP version

| PHP | Go | Why |
|---|---|---|
| `Domain/`, `Infrastructure/`, `Console/` folders | one package, `internal/scoreboard`, and `cmd/board` | In Go every folder is a separate package. This is too little code to split into three. |
| Exception classes | error values: `ErrInvalidFootballMatch` and others | Go has no exceptions. |
| `ScoreBoardException` interface | `ScoreBoardError` type | The common type of all errors. |
| `IdGenerator` and `UuidGenerator` | one function, `NewUUID()` | The id is made with the standard library, so there is no outside library to hide. |
| `$match->homeScore` | `match.HomeScore()` | The fields are private; a method reads them. |
| `null` start or finish time | the zero time, checked with `.IsZero()` | A Go `time.Time` is never `nil`. |
| `./bin/console board` | `./bin/board` | The Go program does one thing, so it needs no command name. |

## What happens in special cases

| Case | What happens |
|---|---|
| A team name is empty or only spaces | error: `ErrInvalidFootballMatch` |
| Both teams are the same (upper or lower case does not matter) | error: `ErrInvalidFootballMatch` |
| The match id is empty | error: `ErrInvalidFootballMatch` |
| A score is below zero | error: `ErrInvalidFootballMatch` |
| The new score is lower than the old one | allowed, so a wrong score can be corrected |
| A team is already playing another match | error: `ErrTeamAlreadyPlaying` |
| Update or finish a match that is not on the board | error: `ErrFootballMatchNotFound` |
| Finish the same match twice | error: `ErrFootballMatchNotFound` |
| Call start, update or finish on a match at the wrong time | error: `ErrInvalidFootballMatchState` |
| A text that is not a UUID is given to `UUIDFromString` | error: `ErrInvalidUUID` |
| Ask for the summary when the board is empty | an empty list |

Spaces around team names are removed. To check for one error, use `errors.Is`:

```go
if errors.Is(err, scoreboard.ErrTeamAlreadyPlaying) {
	// one of the teams is already on the board
}
```

To check for any error of the score board, use `errors.As` with `ScoreBoardError`:

```go
var scoreBoardError scoreboard.ScoreBoardError
if errors.As(err, &scoreBoardError) {
	// the score board refused the action
}
```

## Choices I made, and their weak points

- **Only the storage is an interface.** It is the part I expect to be replaced. Everything else
  is a plain type.
- **The package is in `internal/`.** Go does not let code outside this module import it. This is
  on purpose: it is not a public package yet.
- **It is made for one goroutine.** There are no locks. If many goroutines use the same board at
  once, the caller has to guard it, for example with a `sync.Mutex`.
- **A match can be changed after it is created.** `Summary()` gives back pointers to the real
  matches. The score can only be changed with `UpdateScore()`, but someone who has a match can
  call it on the match directly and skip the board.
- **Finished matches are remembered.** The board itself only needs to drop them. I keep them in
  a list inside `ScoreBoard`, not in the storage, so they are lost when the board is gone.
- **Matches with the same number of goals are ordered by start time.** Matches are added one after
  another, so the start time shows which one was added later. If start times came from another
  system, I would give each match a number instead.
- **A team is just a name.** "Korea" and "South Korea" are two different teams for the board.
- **Matches are kept in a slice, not a map.** A Go map has no fixed order, and a slice keeps the
  order in which matches were added. Finding a match looks at each one in turn, which is fine for
  the few matches played at the same time.
- **Error messages are the same sentences as in the PHP version.** They start with a capital
  letter and end with a dot, for example `Team "Mexico" is already playing.` Go code usually
  writes error messages in lower case without a dot. I chose the same words in both versions,
  so both report an error in the same way.
- **A match must not be `nil`.** Passing `nil` to the board is a mistake in the calling code and
  stops the program, as it does in most Go code.

## Using it in another project

It cannot be used from another project yet, for two reasons:

- The package is inside `internal/`, which only this module may import.
- The module is named `worldcupscoreboard`. A module that others can download needs its
  repository address as its name, for example `github.com/mfederowicz/worldcupscoreboard/golang`.

To publish it, move `internal/scoreboard` out of `internal/` and change the module name in
`go.mod`. Other projects could then add it with `go get`.

## What could be added later

- A database: one more type that implements `FootballMatchRepository`.
- Use from many goroutines: locks in `ScoreBoard` and in `FootballMatch`.
- Other ways to order the summary: move the ordering out of `ScoreBoard` into its own function.
- Messages when a score changes: `ScoreBoard` could announce each change.
- A list of known teams, so a team has an id and not only a name.
