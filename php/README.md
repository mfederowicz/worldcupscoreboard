# World Cup Score Board — PHP

A small application that keeps the scores of football matches that are being played right now.
Everything is kept in memory. There is no framework, no database and no web server.

This is the PHP version. There is also a Go version in [`../golang/`](../golang/).

## What you need

- PHP 8.4 or newer
- Composer
- make

## How to run it

```
git clone https://github.com/mfederowicz/worldcupscoreboard.git scoreboard
cd scoreboard/php
composer install
make tests     # runs the tests (PHPUnit)
make board     # runs the demo: ./bin/console board
make example   # runs a short example: php examples/basic.php
```

The demo starts five matches, one every half a second, and sets their scores. Then it shows the
board, finishes two matches, and shows the board and the finished matches again.

The example in `examples/basic.php` is one plain PHP file with comments. It starts two matches,
updates the scores, shows the board, finishes a match and shows one error. It is the quickest
way to see how to call the code from your own script.

## How to use it

```php
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\ScoreBoard;
use WorldCupScoreBoard\Infrastructure\InMemoryFootballMatchRepository;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;

$ids = new UuidGenerator();
$board = new ScoreBoard(new InMemoryFootballMatchRepository());

$match = new FootballMatch($ids->generate(), 'Mexico', 'Canada');

$board->startGame($match);            // the match is on the board, 0 - 0
$board->updateScore($match, 0, 5);    // sets the score to 0 - 5
$board->summary();                    // matches being played, in order
$board->finishGame($match);           // the match leaves the board
$board->finishedGames();              // finished matches, in the same order
```

### Order of the matches

`summary()` and `finishedGames()` put the match with the most goals (home + away) first.
If two matches have the same number of goals, the one that started later comes first.

## How the code is organised

```
bin/
  console                                 starts the demo
examples/
  basic.php                               a short script that uses the score board
src/
  Domain/                                 the rules of the score board
    ScoreBoard.php                        start, update, finish, summary
    FootballMatch.php                     one match: teams, score, start and finish
    FootballMatchRepository.php           where matches are stored (interface)
    Uuid.php                              the id of a match
    IdGenerator.php                       how a new id is made (interface)
    Exception/                            all errors the score board can report
      ScoreBoardException.php             common type of all errors below (interface)
      FootballMatchNotFoundException.php
      InvalidFootballMatchException.php
      InvalidFootballMatchStateException.php
      TeamAlreadyPlayingException.php
  Infrastructure/                         code that talks to the outside world
    InMemoryFootballMatchRepository.php   keeps matches in an array
    UuidGenerator.php                     makes ids with the symfony/uid library
  Console/
    BoardCommand.php                      the demo
tests/
  Unit/                                   the tests, in the same three folders
```

The code in `Domain` does not use any library and does not know about the other two folders.
`ScoreBoard` gets its storage from outside, in the constructor. To keep matches in a database
instead of memory, you write one new class and pass it in. Nothing else has to change.

## What happens in special cases

| Case | What happens |
|---|---|
| A team name is empty or only spaces | error: `InvalidFootballMatchException` |
| Both teams are the same (upper or lower case does not matter) | error: `InvalidFootballMatchException` |
| A score is below zero | error: `InvalidFootballMatchException` |
| The new score is lower than the old one | allowed, so a wrong score can be corrected |
| A team is already playing another match | error: `TeamAlreadyPlayingException` |
| Update or finish a match that is not on the board | error: `FootballMatchNotFoundException` |
| Finish the same match twice | error: `FootballMatchNotFoundException` |
| Call start, update or finish on a match at the wrong time | error: `InvalidFootballMatchStateException` |
| Ask for the summary when the board is empty | an empty list |

Spaces around team names are removed. Every error above can be caught as `ScoreBoardException`.

## Choices I made, and their weak points

- **Only the storage and the id generator are interfaces.** These are the two parts I expect to
  be replaced. Everything else is a plain class.
- **A match can be changed after it is created.** `summary()` gives back the real match objects.
  The score can only be changed with `updateScore()`, but someone who has a match object can call
  it on the match directly and skip the board.
- **Finished matches are remembered.** The board itself only needs to drop them. I keep them in
  a list inside `ScoreBoard`, not in the storage, so they are lost when the board object is gone.
- **Matches with the same number of goals are ordered by start time.** Matches are added one after
  another, so the start time shows which one was added later. If start times came from another
  system, I would give each match a number instead.
- **A team is just a name.** "Korea" and "South Korea" are two different teams for the board.
- **Ids are made outside the match and passed in.** This keeps library code out of `Domain` and
  lets tests choose the id.

## Using it in another project

The code is already laid out like a Composer package: it has a package name
(`mfederowicz/worldcupscoreboard`) and its classes are loaded automatically from `src/`.
It is not published yet. For now another project on the same machine can use it like this,
in its own `composer.json`:

```json
{
    "repositories": [
        { "type": "path", "url": "../scoreboard/php" }
    ],
    "require": {
        "mfederowicz/worldcupscoreboard": "@dev"
    }
}
```

To publish it as a real package, two things are still to do:

- Give it its own repository. Composer expects `composer.json` at the top of a repository, and
  here it is inside the `php/` folder.
- Make `symfony/console` optional. Only the demo needs it; the score board itself does not.

## What could be added later

- A database: one more class that implements `FootballMatchRepository`.
- Other ways to order the summary: move the ordering out of `ScoreBoard` into its own class.
- Messages when a score changes: `ScoreBoard` could announce each change.
- A list of known teams, so a team has an id and not only a name.
