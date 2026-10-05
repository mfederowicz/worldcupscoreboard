<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Domain\Uuid;
use WorldCupScoreBoard\Infrastructure\InMemoryFootballMatchRepository;
use WorldCupScoreBoard\Infrastructure\UuidGenerator;
use WorldCupScoreBoard\Tests\Unit\CreatesMatches;

#[CoversClass(InMemoryFootballMatchRepository::class)]
#[UsesClass(FootballMatch::class)]
#[UsesClass(Uuid::class)]
#[UsesClass(UuidGenerator::class)]
final class InMemoryFootballMatchRepositoryTest extends TestCase
{
    use CreatesMatches;

    public function testNewRepositoryIsEmpty(): void
    {
        $repository = new InMemoryFootballMatchRepository();

        self::assertSame([], $repository->all());
    }

    public function testSavedMatchIsFoundById(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = self::newMatch('Mexico', 'Canada');

        $repository->save($match);

        self::assertSame($match, $repository->find($match->matchId));
    }

    public function testUnknownIdIsNotFound(): void
    {
        $repository = new InMemoryFootballMatchRepository();

        self::assertNull($repository->find(self::unknownId()));
    }

    public function testAllReturnsEverySavedMatch(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $first = self::newMatch('Mexico', 'Canada');
        $second = self::newMatch('Spain', 'Brazil');

        $repository->save($first);
        $repository->save($second);

        self::assertSame([$first, $second], $repository->all());
    }

    public function testSavingSameMatchTwiceDoesNotDuplicateIt(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = self::newMatch('Mexico', 'Canada');

        $repository->save($match);
        $repository->save($match);

        self::assertSame([$match], $repository->all());
    }

    public function testRemovedMatchIsNoLongerFound(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $removed = self::newMatch('Mexico', 'Canada');
        $kept = self::newMatch('Spain', 'Brazil');
        $repository->save($removed);
        $repository->save($kept);

        $repository->remove($removed->matchId);

        self::assertNull($repository->find($removed->matchId));
        self::assertSame([$kept], $repository->all());
    }

    public function testRemovingUnknownIdDoesNothing(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = self::newMatch('Mexico', 'Canada');
        $repository->save($match);

        $repository->remove(self::unknownId());

        self::assertSame([$match], $repository->all());
    }

    private static function unknownId(): Uuid
    {
        return Uuid::fromString('00000000-0000-4000-8000-000000000000');
    }
}
