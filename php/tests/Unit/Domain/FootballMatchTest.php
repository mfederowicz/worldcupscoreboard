<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\Exception\InvalidFootballMatchException;
use WorldCupScoreBoard\Domain\Exception\InvalidFootballMatchStateException;
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\Uuid;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;
use WorldCupScoreBoard\Tests\Unit\CreatesMatches;

#[CoversClass(FootballMatch::class)]
#[UsesClass(InvalidFootballMatchException::class)]
#[UsesClass(InvalidFootballMatchStateException::class)]
#[UsesClass(Uuid::class)]
#[UsesClass(UuidGenerator::class)]
final class FootballMatchTest extends TestCase
{
    use CreatesMatches;

    public function testNewMatchIsNotStartedAndHasNoGoals(): void
    {
        $match = self::newMatch(' Mexico ', 'Canada');

        self::assertSame('Mexico', $match->homeTeam);
        self::assertSame('Canada', $match->awayTeam);
        self::assertSame(0, $match->homeScore);
        self::assertSame(0, $match->awayScore);
        self::assertNull($match->startMatchTime);
        self::assertNull($match->finishMatchTime);
    }

    public function testMatchKeepsGivenId(): void
    {
        $matchId = Uuid::fromString('ffd44b6a-005e-4a56-9c1f-1f59c33ab2f7');

        $match = new FootballMatch($matchId, 'Mexico', 'Canada');

        self::assertSame($matchId, $match->matchId);
    }

    #[DataProvider('invalidTeams')]
    public function testInvalidTeamsAreRejected(string $homeTeam, string $awayTeam): void
    {
        $this->expectException(InvalidFootballMatchException::class);

        self::newMatch($homeTeam, $awayTeam);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidTeams(): array
    {
        return [
            'empty home team' => ['', 'Canada'],
            'blank away team' => ['Mexico', '   '],
            'same team' => ['Spain', 'Spain'],
            'same team, different case' => ['Spain', ' SPAIN '],
        ];
    }

    public function testStartMatchSetsStartTime(): void
    {
        $match = self::newMatch('Mexico', 'Canada');

        $match->startMatch();

        self::assertNotNull($match->startMatchTime);
        self::assertNull($match->finishMatchTime);
    }

    public function testMatchCannotBeStartedTwice(): void
    {
        $match = self::startedMatch();

        $this->expectException(InvalidFootballMatchStateException::class);

        $match->startMatch();
    }

    public function testUpdateScoreReplacesScore(): void
    {
        $match = self::startedMatch();

        $match->updateScore(0, 5);

        self::assertSame(0, $match->homeScore);
        self::assertSame(5, $match->awayScore);
    }

    public function testScoreCanBeLowered(): void
    {
        $match = self::startedMatch();
        $match->updateScore(2, 1);

        $match->updateScore(1, 1);

        self::assertSame(1, $match->homeScore);
        self::assertSame(1, $match->awayScore);
    }

    public function testNegativeScoreIsRejected(): void
    {
        $match = self::startedMatch();

        $this->expectException(InvalidFootballMatchException::class);

        $match->updateScore(1, -1);
    }

    public function testScoreCannotBeUpdatedBeforeStart(): void
    {
        $match = self::newMatch('Mexico', 'Canada');

        $this->expectException(InvalidFootballMatchStateException::class);

        $match->updateScore(1, 0);
    }

    public function testScoreCannotBeUpdatedAfterFinish(): void
    {
        $match = self::startedMatch();
        $match->finishMatch();

        $this->expectException(InvalidFootballMatchStateException::class);

        $match->updateScore(1, 0);
    }

    public function testFinishMatchSetsFinishTimeAndKeepsScore(): void
    {
        $match = self::startedMatch();
        $match->updateScore(3, 1);

        $match->finishMatch();

        self::assertNotNull($match->finishMatchTime);
        self::assertSame(3, $match->homeScore);
        self::assertSame(1, $match->awayScore);
    }

    public function testMatchCannotBeFinishedBeforeStart(): void
    {
        $match = self::newMatch('Mexico', 'Canada');

        $this->expectException(InvalidFootballMatchStateException::class);

        $match->finishMatch();
    }

    public function testMatchCannotBeFinishedTwice(): void
    {
        $match = self::startedMatch();
        $match->finishMatch();

        $this->expectException(InvalidFootballMatchStateException::class);

        $match->finishMatch();
    }

    private static function startedMatch(): FootballMatch
    {
        $match = self::newMatch('Mexico', 'Canada');
        $match->startMatch();

        return $match;
    }
}
