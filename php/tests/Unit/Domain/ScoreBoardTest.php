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

    public function testNewBoardIsEmpty(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());

        self::assertSame([], $board->summary());
        self::assertSame([], $board->finishedGames());
    }

    public function testStartGameStartsMatchAndPutsItOnBoard(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');

        $board->startGame($match);

        self::assertNotNull($match->startMatchTime);
        self::assertSame([$match], $board->summary());
    }

    public function testStartGameStoresMatchInGivenRepository(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $board = new ScoreBoard($repository);
        $match = self::newMatch('Mexico', 'Canada');

        $board->startGame($match);

        self::assertSame($match, $repository->find($match->matchId));
    }

    #[DataProvider('matchesWithBusyTeam')]
    public function testTeamCannotPlayTwoMatchesAtOnce(string $homeTeam, string $awayTeam): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $live = self::newMatch('Mexico', 'Canada');
        $board->startGame($live);
        $rejected = self::newMatch($homeTeam, $awayTeam);

        try {
            $board->startGame($rejected);
            self::fail('Expected the match to be rejected.');
        } catch (TeamAlreadyPlayingException) {
            self::assertNull($rejected->startMatchTime);
            self::assertSame([$live], $board->summary());
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
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');
        $board->startGame($match);

        $board->finishGame($match);

        self::assertNotNull($match->finishMatchTime);
        self::assertSame([], $board->summary());
        self::assertSame([$match], $board->finishedGames());
    }

    public function testTeamsCanPlayAgainAfterTheirMatchIsFinished(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $first = self::newMatch('Mexico', 'Canada');
        $board->startGame($first);
        $board->finishGame($first);
        $second = self::newMatch('Mexico', 'Canada');

        $board->startGame($second);

        self::assertSame([$second], $board->summary());
    }

    public function testMatchThatIsNotOnBoardCannotBeFinished(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());

        $this->expectException(FootballMatchNotFoundException::class);

        $board->finishGame(self::newMatch('Mexico', 'Canada'));
    }

    public function testMatchCannotBeFinishedTwice(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');
        $board->startGame($match);
        $board->finishGame($match);

        $this->expectException(FootballMatchNotFoundException::class);

        $board->finishGame($match);
    }

    public function testUpdateScoreChangesScoreOfMatchOnBoard(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');
        $board->startGame($match);

        $board->updateScore($match, 0, 5);

        self::assertSame(0, $match->homeScore);
        self::assertSame(5, $match->awayScore);
        self::assertSame([$match], $board->summary());
    }

    public function testNegativeScoreIsRejected(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');
        $board->startGame($match);

        $this->expectException(InvalidFootballMatchException::class);

        $board->updateScore($match, -1, 0);
    }

    public function testScoreOfMatchThatIsNotOnBoardCannotBeUpdated(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());

        $this->expectException(FootballMatchNotFoundException::class);

        $board->updateScore(self::newMatch('Mexico', 'Canada'), 1, 0);
    }

    public function testScoreOfFinishedMatchCannotBeUpdated(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $match = self::newMatch('Mexico', 'Canada');
        $board->startGame($match);
        $board->finishGame($match);

        $this->expectException(FootballMatchNotFoundException::class);

        $board->updateScore($match, 1, 0);
    }

    public function testSummaryIsOrderedByTotalScoreThenMostRecentlyStarted(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        self::playExample($board);

        self::assertSame(self::EXAMPLE_SUMMARY, self::describe($board->summary()));
    }

    public function testFinishedGamesUseSameOrderRegardlessOfFinishOrder(): void
    {
        $board = new ScoreBoard(new InMemoryFootballMatchRepository());
        $matches = self::playExample($board);

        foreach ([2, 0, 4, 1, 3] as $index) {
            $board->finishGame($matches[$index]);
        }

        self::assertSame([], $board->summary());
        self::assertSame(self::EXAMPLE_SUMMARY, self::describe($board->finishedGames()));
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
