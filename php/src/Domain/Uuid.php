<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Domain;

use InvalidArgumentException;

final readonly class Uuid
{
    private const string PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $value): self
    {
        $value = strtolower($value);

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" is not a valid UUID.', $value));
        }

        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
