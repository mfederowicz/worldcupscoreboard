<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain\Exception;

use LogicException;

final class InvalidFootballMatchStateException extends LogicException implements ScoreBoardException
{
}
