<?php

declare(strict_types=1);

namespace WorldCupScoreBoard;

use InvalidArgumentException;

final class Board implements ScoreBoard
{
    /**
     * @var list<FootballMatch>
     */
    private array $finishedMatches = [];

    public function __construct(
        private readonly FootballMatchRepository $matches = new InMemoryFootballMatchRepository(),
    ) {
    }

    public function startGame(FootballMatch $match): void
    {
        foreach ([$match->homeTeam, $match->awayTeam] as $team) {
            if ($this->isPlaying($team)) {
                throw new InvalidArgumentException(sprintf('Team "%s" is already playing.', $team));
            }
        }

        $match->startMatch();
        $this->matches->save($match);
    }

    public function finishGame(FootballMatch $match): void
    {
        $this->assertOnBoard($match);

        $match->finishMatch();
        $this->matches->remove($match->matchId);
        $this->finishedMatches[] = $match;
    }

    public function updateScore(FootballMatch $match, int $homeScore, int $awayScore): void
    {
        $this->assertOnBoard($match);

        $match->updateScore($homeScore, $awayScore);
        $this->matches->save($match);
    }

    public function summary(): array
    {
        return self::byTotalScore($this->matches->all());
    }

    public function finishedGames(): array
    {
        return self::byTotalScore($this->finishedMatches);
    }

    /**
     * Highest total score first; equal totals: most recently started first.
     *
     * @param list<FootballMatch> $matches
     *
     * @return list<FootballMatch>
     */
    private static function byTotalScore(array $matches): array
    {
        usort($matches, static fn (FootballMatch $a, FootballMatch $b): int =>
            [$b->homeScore + $b->awayScore, $b->startMatchTime] <=> [$a->homeScore + $a->awayScore, $a->startMatchTime]);

        return $matches;
    }

    private function assertOnBoard(FootballMatch $match): void
    {
        if ($this->matches->find($match->matchId) === null) {
            throw new FootballMatchNotFoundException(sprintf('Match "%s" is not on the board.', $match->matchId));
        }
    }

    private function isPlaying(string $team): bool
    {
        $team = mb_strtolower($team);

        foreach ($this->matches->all() as $live) {
            if ($team === mb_strtolower($live->homeTeam) || $team === mb_strtolower($live->awayTeam)) {
                return true;
            }
        }

        return false;
    }
}
