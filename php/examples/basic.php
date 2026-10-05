<?php

declare(strict_types=1);

use WorldCupScoreBoard\Domain\Exception\ScoreBoardException;
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\ScoreBoard;
use WorldCupScoreBoard\Infrastructure\InMemoryFootballMatchRepository;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;

require dirname(__DIR__) . '/vendor/autoload.php';

function printMatches(string $title, array $matches): void
{
    echo $title, PHP_EOL;

    foreach ($matches as $position => $match) {
        printf(
            '%d. %s %d - %s %d%s',
            $position + 1,
            $match->homeTeam,
            $match->homeScore,
            $match->awayTeam,
            $match->awayScore,
            PHP_EOL,
        );
    }

    echo PHP_EOL;
}

$ids = new UuidGenerator();
$board = new ScoreBoard(new InMemoryFootballMatchRepository());

// 1. Start two matches. Each one begins at 0 - 0.
$mexicoCanada = new FootballMatch($ids->generate(), 'Mexico', 'Canada');
$spainBrazil = new FootballMatch($ids->generate(), 'Spain', 'Brazil');

$board->startGame($mexicoCanada);
$board->startGame($spainBrazil);

// 2. Update the scores. The numbers are the new score, not goals to add.
$board->updateScore($mexicoCanada, 0, 5);
$board->updateScore($spainBrazil, 10, 2);

// 3. Show the board: most goals first.
printMatches('On the board:', $board->summary());

// 4. Finish a match. It leaves the board and moves to the finished matches.
$board->finishGame($spainBrazil);

printMatches('On the board after Spain - Brazil finished:', $board->summary());
printMatches('Finished:', $board->finishedGames());

// 5. Errors. Every error from the score board can be caught as ScoreBoardException.
try {
    $board->startGame(new FootballMatch($ids->generate(), 'Mexico', 'Germany'));
} catch (ScoreBoardException $exception) {
    echo 'Not allowed: ', $exception->getMessage(), PHP_EOL;
}
