<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain;

interface FootballMatchRepository
{
    public function save(FootballMatch $match): void;

    public function remove(Uuid $matchId): void;

    public function find(Uuid $matchId): ?FootballMatch;

    /**
     * @return list<FootballMatch>
     */
    public function all(): array;
}
