<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\Exception\FootballMatchNotFoundException;
use WorldCupScoreBoard\Domain\Exception\InvalidFootballMatchException;
use WorldCupScoreBoard\Domain\Exception\TeamAlreadyPlayingException;
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\ScoreBoard;
use WorldCupScoreBoard\Domain\Uuid;
use WorldCupScoreBoard\Infrastructure\InMemoryFootballMatchRepository;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;
use WorldCupScoreBoard\Tests\Unit\CreatesMatches;

#[CoversClass(ScoreBoard::class)]
#[UsesClass(FootballMatch::class)]
#[UsesClass(FootballMatchNotFoundException::class)]
#[UsesClass(InMemoryFootballMatchRepository::class)]
#[UsesClass(InvalidFootballMatchException::class)]
#[UsesClass(TeamAlreadyPlayingException::class)]
#[UsesClass(Uuid::class)]
#[UsesClass(UuidGenerator::class)]
final class ScoreBoardTest extends TestCase
{
    use CreatesMatches;

    private const array EXAMPLE = [
        ['Mexico', 'Canada', 0, 5],
        ['Spain', 'Brazil', 10, 2],
        ['Germany', 'France', 2, 2],
        ['Uruguay', 'Italy', 6, 6],
        ['Argentina', 'Australia', 3, 1],
    ];

    private const array EXAMPLE_SUMMARY = [
        'Uruguay 6 - Italy 6',
        'Spain 10 - Brazil 2',
        'Mexico 0 - Canada 5',
        'Argentina 3 - Australia 1',
        'Germany 2 - France 2',
    ];

    private InMemoryFootballMatchRepository $repository;

    private ScoreBoard $board;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = new InMemoryFootballMatchRepository();
        $this->board = new ScoreBoard($this->repository);
    }

    public function testNewBoardIsEmpty(): void
    {
        self::assertSame([], $this->board->summary());
        self::assertSame([], $this->board->finishedGames());
    }

    public function testStartGameStartsMatchAndPutsItOnBoard(): void
    {
        $match = self::newMatch('Mexico', 'Canada');

        $this->board->startGame($match);

        self::assertNotNull($match->startMatchTime);
        self::assertSame([$match], $this->board->summary());
    }

    public function testStartGameStoresMatchInGivenRepository(): void
    {
        $match = self::newMatch('Mexico', 'Canada');

        $this->board->startGame($match);

        self::assertSame($match, $this->repository->find($match->matchId));
    }

    #[DataProvider('matchesWithBusyTeam')]
    public function testTeamCannotPlayTwoMatchesAtOnce(string $homeTeam, string $awayTeam): void
    {
        $live = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($live);
        $rejected = self::newMatch($homeTeam, $awayTeam);

        try {
            $this->board->startGame($rejected);
            self::fail('Expected the match to be rejected.');
        } catch (TeamAlreadyPlayingException) {
            self::assertNull($rejected->startMatchTime);
            self::assertSame([$live], $this->board->summary());
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function matchesWithBusyTeam(): array
    {
        return [
            'home team is playing' => ['Mexico', 'Spain'],
            'away team is playing' => ['Spain', 'Canada'],
            'sides swapped' => ['Canada', 'Mexico'],
            'different case' => ['MEXICO', 'Spain'],
        ];
    }

    public function testFinishGameFinishesMatchAndRemovesItFromBoard(): void
    {
        $match = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($match);

        $this->board->finishGame($match);

        self::assertNotNull($match->finishMatchTime);
        self::assertSame([], $this->board->summary());
        self::assertSame([$match], $this->board->finishedGames());
    }

    public function testTeamsCanPlayAgainAfterTheirMatchIsFinished(): void
    {
        $first = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($first);
        $this->board->finishGame($first);
        $second = self::newMatch('Mexico', 'Canada');

        $this->board->startGame($second);

        self::assertSame([$second], $this->board->summary());
    }

    public function testMatchThatIsNotOnBoardCannotBeFinished(): void
    {
        $this->expectException(FootballMatchNotFoundException::class);

        $this->board->finishGame(self::newMatch('Mexico', 'Canada'));
    }

    public function testMatchCannotBeFinishedTwice(): void
    {
        $match = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($match);
        $this->board->finishGame($match);

        $this->expectException(FootballMatchNotFoundException::class);

        $this->board->finishGame($match);
    }

    public function testUpdateScoreChangesScoreOfMatchOnBoard(): void
    {
        $match = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($match);

        $this->board->updateScore($match, 0, 5);

        self::assertSame(0, $match->homeScore);
        self::assertSame(5, $match->awayScore);
        self::assertSame([$match], $this->board->summary());
    }

    public function testNegativeScoreIsRejected(): void
    {
        $match = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($match);

        $this->expectException(InvalidFootballMatchException::class);

        $this->board->updateScore($match, -1, 0);
    }

    public function testScoreOfMatchThatIsNotOnBoardCannotBeUpdated(): void
    {
        $this->expectException(FootballMatchNotFoundException::class);

        $this->board->updateScore(self::newMatch('Mexico', 'Canada'), 1, 0);
    }

    public function testScoreOfFinishedMatchCannotBeUpdated(): void
    {
        $match = self::newMatch('Mexico', 'Canada');
        $this->board->startGame($match);
        $this->board->finishGame($match);

        $this->expectException(FootballMatchNotFoundException::class);

        $this->board->updateScore($match, 1, 0);
    }

    public function testSummaryIsOrderedByTotalScoreThenMostRecentlyStarted(): void
    {
        self::playExample($this->board);

        self::assertSame(self::EXAMPLE_SUMMARY, self::describe($this->board->summary()));
    }

    public function testFinishedGamesUseSameOrderRegardlessOfFinishOrder(): void
    {
        $matches = self::playExample($this->board);

        foreach ([2, 0, 4, 1, 3] as $index) {
            $this->board->finishGame($matches[$index]);
        }

        self::assertSame([], $this->board->summary());
        self::assertSame(self::EXAMPLE_SUMMARY, self::describe($this->board->finishedGames()));
    }

    /**
     * @return list<FootballMatch>
     */
    private static function playExample(ScoreBoard $board): array
    {
        $matches = [];

        foreach (self::EXAMPLE as [$homeTeam, $awayTeam, $homeScore, $awayScore]) {
            $match = self::newMatch($homeTeam, $awayTeam);
            $board->startGame($match);
            $board->updateScore($match, $homeScore, $awayScore);
            $matches[] = $match;
        }

        return $matches;
    }

    /**
     * @param list<FootballMatch> $matches
     *
     * @return list<string>
     */
    private static function describe(array $matches): array
    {
        return array_map(
            static fn (FootballMatch $match): string => sprintf(
                '%s %d - %s %d',
                $match->homeTeam,
                $match->homeScore,
                $match->awayTeam,
                $match->awayScore,
            ),
            $matches,
        );
    }
}
