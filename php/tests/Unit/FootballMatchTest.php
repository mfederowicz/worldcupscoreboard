<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\FootballMatch;

#[CoversClass(FootballMatch::class)]
final class FootballMatchTest extends TestCase
{
    public function testNewMatchIsNotStartedAndHasNoGoals(): void
    {
        $match = new FootballMatch(' Mexico ', 'Canada');

        self::assertSame('Mexico', $match->homeTeam);
        self::assertSame('Canada', $match->awayTeam);
        self::assertSame(0, $match->homeScore);
        self::assertSame(0, $match->awayScore);
        self::assertNull($match->startMatchTime);
        self::assertNull($match->finishMatchTime);
    }

    public function testEachMatchGetsItsOwnId(): void
    {
        $first = new FootballMatch('Mexico', 'Canada');
        $second = new FootballMatch('Mexico', 'Canada');

        self::assertNotSame($first->matchId, $second->matchId);
    }

    #[DataProvider('invalidTeams')]
    public function testInvalidTeamsAreRejected(string $homeTeam, string $awayTeam): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FootballMatch($homeTeam, $awayTeam);
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
        $match = new FootballMatch('Mexico', 'Canada');

        $match->startMatch();

        self::assertNotNull($match->startMatchTime);
        self::assertNull($match->finishMatchTime);
    }

    public function testMatchCannotBeStartedTwice(): void
    {
        $match = self::startedMatch();

        $this->expectException(LogicException::class);

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

        $this->expectException(InvalidArgumentException::class);

        $match->updateScore(1, -1);
    }

    public function testScoreCannotBeUpdatedBeforeStart(): void
    {
        $match = new FootballMatch('Mexico', 'Canada');

        $this->expectException(LogicException::class);

        $match->updateScore(1, 0);
    }

    public function testScoreCannotBeUpdatedAfterFinish(): void
    {
        $match = self::startedMatch();
        $match->finishMatch();

        $this->expectException(LogicException::class);

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
        $match = new FootballMatch('Mexico', 'Canada');

        $this->expectException(LogicException::class);

        $match->finishMatch();
    }

    public function testMatchCannotBeFinishedTwice(): void
    {
        $match = self::startedMatch();
        $match->finishMatch();

        $this->expectException(LogicException::class);

        $match->finishMatch();
    }

    private static function startedMatch(): FootballMatch
    {
        $match = new FootballMatch('Mexico', 'Canada');
        $match->startMatch();

        return $match;
    }
}
