<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use WorldCupScoreBoard\Board;
use WorldCupScoreBoard\Command\BoardCommand;
use WorldCupScoreBoard\FootballMatch;
use WorldCupScoreBoard\InMemoryFootballMatchRepository;

#[CoversClass(BoardCommand::class)]
#[UsesClass(Board::class)]
#[UsesClass(FootballMatch::class)]
#[UsesClass(InMemoryFootballMatchRepository::class)]
final class BoardCommandTest extends TestCase
{
    public function testCommandIsNamedBoard(): void
    {
        self::assertSame('board', new BoardCommand()->getName());
    }

    public function testShowsEachMatchBeingStartedAndScored(): void
    {
        $output = self::runCommand();

        self::assertStringStartsWith("World Cup finals begin:\n\nStarted: Mexico - Canada\nScore:   Mexico 0 - Canada 5\n", $output);
        self::assertStringContainsString("Started: Argentina - Australia\nScore:   Argentina 3 - Australia 1\n", $output);
    }

    public function testShowsSummaryOfAllMatchesOrderedByTotalScore(): void
    {
        $expected = <<<'TEXT'

            Summary (5 matches in progress):
            1. Uruguay 6 - Italy 6
            2. Spain 10 - Brazil 2
            3. Mexico 0 - Canada 5
            4. Argentina 3 - Australia 1
            5. Germany 2 - France 2

            TEXT;

        self::assertStringContainsString($expected, self::runCommand());
    }

    public function testEndsWithSummaryWithoutFinishedMatchesAndFinishedGames(): void
    {
        $expected = <<<'TEXT'

            Finished: Spain 10 - Brazil 2
            Finished: Germany 2 - France 2

            Summary after finishing (3 matches in progress):
            1. Uruguay 6 - Italy 6
            2. Mexico 0 - Canada 5
            3. Argentina 3 - Australia 1

            Finished games (2):
            1. Spain 10 - Brazil 2
            2. Germany 2 - France 2

            TEXT;

        self::assertStringEndsWith($expected, self::runCommand());
    }

    private static function runCommand(): string
    {
        $tester = new CommandTester(new BoardCommand(delayInMilliseconds: 0));

        $tester->execute([]);
        $tester->assertCommandIsSuccessful();

        return $tester->getDisplay(true);
    }
}
