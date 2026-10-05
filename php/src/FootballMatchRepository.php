<?php

declare(strict_types=1);

namespace WorldCupScoreBoard;

interface FootballMatchRepository
{
    public function save(FootballMatch $match): void;

    public function remove(string $matchId): void;

    public function find(string $matchId): ?FootballMatch;

    /**
     * @return list<FootballMatch>
     */
    public function all(): array;
}
