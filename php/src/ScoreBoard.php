<?php

declare(strict_types=1);

namespace WorldCupScoreBoard;

interface ScoreBoard
{
    public function startGame(FootballMatch $match): void;
    public function finishGame(FootballMatch $match): void;
    public function updateScore(FootballMatch $match, int $homeScore, int $awayScore): void;
    public function summary(): array;
    public function finishedGames(): array;
}
