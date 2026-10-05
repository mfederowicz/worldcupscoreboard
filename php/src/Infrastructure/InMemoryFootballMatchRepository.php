<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Infrastructure;

use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\FootballMatchRepository;
use WorldCupScoreBoard\Domain\Uuid;

final class InMemoryFootballMatchRepository implements FootballMatchRepository
{
    /**
     * @var array<string, FootballMatch>
     */
    private array $matches = [];

    public function save(FootballMatch $match): void
    {
        $this->matches[$match->matchId->toString()] = $match;
    }

    public function remove(Uuid $matchId): void
    {
        unset($this->matches[$matchId->toString()]);
    }

    public function find(Uuid $matchId): ?FootballMatch
    {
        return $this->matches[$matchId->toString()] ?? null;
    }

    public function all(): array
    {
        return array_values($this->matches);
    }
}
