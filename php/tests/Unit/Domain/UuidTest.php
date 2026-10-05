<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\Uuid;

#[CoversClass(Uuid::class)]
final class UuidTest extends TestCase
{
    private const string VALUE = 'ffd44b6a-005e-4a56-9c1f-1f59c33ab2f7';

    public function testUuidIsCreatedFromString(): void
    {
        self::assertSame(self::VALUE, Uuid::fromString(self::VALUE)->toString());
    }

    public function testUuidIsNormalisedToLowerCase(): void
    {
        self::assertSame(self::VALUE, Uuid::fromString(strtoupper(self::VALUE))->toString());
    }

    public function testUuidsWithSameValueAreEqual(): void
    {
        self::assertEquals(Uuid::fromString(self::VALUE), Uuid::fromString(self::VALUE));
    }

    #[DataProvider('invalidValues')]
    public function testInvalidValueIsRejected(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Uuid::fromString($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidValues(): array
    {
        return [
            'empty' => [''],
            'not a uuid' => ['mexico-canada'],
            'too short' => ['ffd44b6a-005e-4a56-9c1f'],
            'trailing characters' => [self::VALUE . '-1'],
            'without dashes' => ['ffd44b6a005e4a569c1f1f59c33ab2f7'],
        ];
    }
}
