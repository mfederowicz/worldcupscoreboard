<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain;

interface IdGenerator
{
    public function generate(): Uuid;
}
