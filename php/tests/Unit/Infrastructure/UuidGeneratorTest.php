<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\Uuid;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;

#[CoversClass(UuidGenerator::class)]
#[UsesClass(Uuid::class)]
final class UuidGeneratorTest extends TestCase
{
    public function testGeneratesVersion4Uuid(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            new UuidGenerator()->generate()->toString(),
        );
    }

    public function testEachGeneratedValueIsDifferent(): void
    {
        $generator = new UuidGenerator();

        self::assertNotSame($generator->generate()->toString(), $generator->generate()->toString());
    }
}
