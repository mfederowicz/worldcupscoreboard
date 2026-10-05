<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain\Exception;

use RuntimeException;

final class TeamAlreadyPlayingException extends RuntimeException implements ScoreBoardException
{
}
