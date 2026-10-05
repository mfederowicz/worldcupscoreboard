<?php

declare(strict_types=1);

namespace WorldCupScoreBoard\Tests\Unit\Infrastructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use WorldCupScoreBoard\Domain\FootballMatch;
use WorldCupScoreBoard\Infrastructure\InMemoryFootballMatchRepository;

#[CoversClass(InMemoryFootballMatchRepository::class)]
#[UsesClass(FootballMatch::class)]
final class InMemoryFootballMatchRepositoryTest extends TestCase
{
    public function testNewRepositoryIsEmpty(): void
    {
        $repository = new InMemoryFootballMatchRepository();

        self::assertSame([], $repository->all());
    }

    public function testSavedMatchIsFoundById(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = new FootballMatch('Mexico', 'Canada');

        $repository->save($match);

        self::assertSame($match, $repository->find($match->matchId));
    }

    public function testUnknownIdIsNotFound(): void
    {
        $repository = new InMemoryFootballMatchRepository();

        self::assertNull($repository->find('unknown-id'));
    }

    public function testAllReturnsEverySavedMatch(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $first = new FootballMatch('Mexico', 'Canada');
        $second = new FootballMatch('Spain', 'Brazil');

        $repository->save($first);
        $repository->save($second);

        self::assertSame([$first, $second], $repository->all());
    }

    public function testSavingSameMatchTwiceDoesNotDuplicateIt(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = new FootballMatch('Mexico', 'Canada');

        $repository->save($match);
        $repository->save($match);

        self::assertSame([$match], $repository->all());
    }

    public function testRemovedMatchIsNoLongerFound(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $removed = new FootballMatch('Mexico', 'Canada');
        $kept = new FootballMatch('Spain', 'Brazil');
        $repository->save($removed);
        $repository->save($kept);

        $repository->remove($removed->matchId);

        self::assertNull($repository->find($removed->matchId));
        self::assertSame([$kept], $repository->all());
    }

    public function testRemovingUnknownIdDoesNothing(): void
    {
        $repository = new InMemoryFootballMatchRepository();
        $match = new FootballMatch('Mexico', 'Canada');
        $repository->save($match);

        $repository->remove('unknown-id');

        self::assertSame([$match], $repository->all());
    }
}
