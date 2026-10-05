<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Infrastructure;

use Symfony\Component\Uid\Uuid as SymfonyUuid;
use WorldCupScoreBoard\Domain\IdGenerator;
use WorldCupScoreBoard\Domain\Uuid;

final class UuidGenerator implements IdGenerator
{
    public function generate(): Uuid
    {
        return Uuid::fromString(SymfonyUuid::v4()->toRfc4122());
    }
}
