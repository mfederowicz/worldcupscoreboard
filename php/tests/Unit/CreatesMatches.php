<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit;

use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;

trait CreatesMatches
{
    private static function newMatch(string $homeTeam, string $awayTeam): FootballMatch
    {
        return new FootballMatch(new UuidGenerator()->generate(), $homeTeam, $awayTeam);
    }
}
