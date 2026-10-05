<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WorldCupScoreBoard\Board;
use WorldCupScoreBoard\FootballMatch;

#[AsCommand(name: 'board', description: 'Plays example matches and shows the score board summary')]
final class BoardCommand extends Command
{
    private const array MATCHES = [
        ['Mexico', 'Canada', 0, 5],
        ['Spain', 'Brazil', 10, 2],
        ['Germany', 'France', 2, 2],
        ['Uruguay', 'Italy', 6, 6],
        ['Argentina', 'Australia', 3, 1],
    ];

    /**
     * Positions in MATCHES of the matches that are finished during the demo.
     */
    private const array FINISHED = [1, 2];

    public function __construct(private readonly int $delayInMilliseconds = 500)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $board = new Board();
        $matches = [];

        $output->writeln(['World Cup finals begin:', '']);

        foreach (self::MATCHES as [$homeTeam, $awayTeam, $homeScore, $awayScore]) {
            $match = new FootballMatch($homeTeam, $awayTeam);

            $board->startGame($match);
            $output->writeln(sprintf('Started: %s - %s', $match->homeTeam, $match->awayTeam));

            usleep($this->delayInMilliseconds * 1000);

            $board->updateScore($match, $homeScore, $awayScore);
            $output->writeln('Score:   ' . self::describe($match));

            $matches[] = $match;
        }

        self::writeList($output, 'Summary (%d matches in progress):', $board->summary());

        $output->writeln('');

        foreach (self::FINISHED as $index) {
            $board->finishGame($matches[$index]);
            $output->writeln('Finished: ' . self::describe($matches[$index]));
        }

        self::writeList($output, 'Summary after finishing (%d matches in progress):', $board->summary());
        self::writeList($output, 'Finished games (%d):', $board->finishedGames());

        return Command::SUCCESS;
    }

    /**
     * @param list<FootballMatch> $matches
     */
    private static function writeList(OutputInterface $output, string $title, array $matches): void
    {
        $output->writeln(['', sprintf($title, count($matches))]);

        foreach ($matches as $position => $match) {
            $output->writeln(sprintf('%d. %s', $position + 1, self::describe($match)));
        }
    }

    private static function describe(FootballMatch $match): string
    {
        return sprintf('%s %d - %s %d', $match->homeTeam, $match->homeScore, $match->awayTeam, $match->awayScore);
    }
}
