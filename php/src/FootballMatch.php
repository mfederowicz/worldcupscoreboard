<?php

declare(strict_types=1);

namespace WorldCupScoreBoard;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Uid\Uuid;

final class FootballMatch
{
    public readonly string $matchId;
    public readonly string $homeTeam;
    public readonly string $awayTeam;
    public private(set) int $homeScore = 0;
    public private(set) int $awayScore = 0;
    public private(set) ?DateTimeImmutable $startMatchTime = null;
    public private(set) ?DateTimeImmutable $finishMatchTime = null;

    public function __construct(string $homeTeam, string $awayTeam)
    {
        $homeTeam = trim($homeTeam);
        $awayTeam = trim($awayTeam);

        if ($homeTeam === '' || $awayTeam === '') {
            throw new InvalidArgumentException('Team name must not be empty.');
        }

        if (mb_strtolower($homeTeam) === mb_strtolower($awayTeam)) {
            throw new InvalidArgumentException('A team cannot play against itself.');
        }

        $this->matchId = Uuid::v4()->toRfc4122();
        $this->homeTeam = $homeTeam;
        $this->awayTeam = $awayTeam;
    }

    public function startMatch(): void
    {
        if ($this->startMatchTime !== null) {
            throw new LogicException('Match has already been started.');
        }

        $this->startMatchTime = new DateTimeImmutable();
    }

    public function finishMatch(): void
    {
        $this->assertInProgress();

        $this->finishMatchTime = new DateTimeImmutable();
    }

    public function updateScore(int $homeScore, int $awayScore): void
    {
        $this->assertInProgress();

        if ($homeScore < 0 || $awayScore < 0) {
            throw new InvalidArgumentException('Score must not be negative.');
        }

        $this->homeScore = $homeScore;
        $this->awayScore = $awayScore;
    }

    private function assertInProgress(): void
    {
        if ($this->startMatchTime === null || $this->finishMatchTime !== null) {
            throw new LogicException('Match is not in progress.');
        }
    }
}
