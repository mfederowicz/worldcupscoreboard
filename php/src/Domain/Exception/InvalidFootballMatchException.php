<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain\Exception;

use InvalidArgumentException;

final class InvalidFootballMatchException extends InvalidArgumentException implements ScoreBoardException
{
}
