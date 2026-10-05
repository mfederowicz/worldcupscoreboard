<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain\Exception;

use RuntimeException;

final class FootballMatchNotFoundException extends RuntimeException implements ScoreBoardException
{
}
