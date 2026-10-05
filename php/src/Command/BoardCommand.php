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

    public function __construct(private readonly int $delayInMilliseconds = 500)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $board = new Board();

        foreach (self::MATCHES as [$homeTeam, $awayTeam, $homeScore, $awayScore]) {
            $match = new FootballMatch($homeTeam, $awayTeam);

            $board->startGame($match);
            $output->writeln(sprintf('Started: %s - %s', $match->homeTeam, $match->awayTeam));

            usleep($this->delayInMilliseconds * 1000);

            $board->updateScore($match, $homeScore, $awayScore);
            $output->writeln('Score:   ' . self::describe($match));
        }

        $output->writeln(['', 'Summary:']);

        foreach ($board->summary() as $position => $match) {
            $output->writeln(sprintf('%d. %s', $position + 1, self::describe($match)));
        }

        return Command::SUCCESS;
    }

    private static function describe(FootballMatch $match): string
    {
        return sprintf('%s %d - %s %d', $match->homeTeam, $match->homeScore, $match->awayTeam, $match->awayScore);
    }
}
