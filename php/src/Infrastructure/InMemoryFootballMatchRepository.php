<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Infrastructure;

use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\FootballMatchRepository;

final class InMemoryFootballMatchRepository implements FootballMatchRepository
{
    /**
     * @var array<string, FootballMatch>
     */
    private array $matches = [];

    public function save(FootballMatch $match): void
    {
        $this->matches[$match->matchId] = $match;
    }

    public function remove(string $matchId): void
    {
        unset($this->matches[$matchId]);
    }

    public function find(string $matchId): ?FootballMatch
    {
        return $this->matches[$matchId] ?? null;
    }

    public function all(): array
    {
        return array_values($this->matches);
    }
}
