<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Domain\Events;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use Error;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecordChangedTest extends TestCase
{
    public function test_it_carries_model_kind_field_changes_relation_changes_and_actor(): void
    {
        $actor = Actor::user(3);
        $event = new RecordChanged(
            'App\\Offer',
            5,
            ChangeKind::Changed,
            ['price' => ['old' => 10, 'new' => 20]],
            ['tags' => ['added' => [1], 'removed' => [2]]],
            $actor,
        );

        $this->assertSame('App\\Offer', $event->modelClass);
        $this->assertSame(5, $event->id);
        $this->assertSame(ChangeKind::Changed, $event->kind);
        $this->assertSame(['price' => ['old' => 10, 'new' => 20]], $event->changes);
        $this->assertSame(['tags' => ['added' => [1], 'removed' => [2]]], $event->relationChanges);
        $this->assertSame($actor, $event->actor);
    }

    /** @return array<string, array{ChangeKind}> */
    public static function kinds(): array
    {
        return array_combine(
            array_map(fn (ChangeKind $k) => $k->value, ChangeKind::cases()),
            array_map(fn (ChangeKind $k) => [$k], ChangeKind::cases()),
        );
    }

    #[DataProvider('kinds')]
    public function test_it_carries_every_kind_of_change(ChangeKind $kind): void
    {
        $event = new RecordChanged('App\\Offer', 5, $kind, [], [], Actor::system('import'));

        $this->assertSame($kind, $event->kind);
        $this->assertTrue($event->actor->isSystem());
    }

    public function test_it_is_immutable(): void
    {
        $event = new RecordChanged('App\\Offer', 5, ChangeKind::Created, [], [], Actor::user(1));

        $this->expectException(Error::class);

        $event->id = 6;
    }
}
