<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Audit\Domain\Entities;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Domain\Entities\Change;
use App\Modules\Audit\Domain\Exceptions\ChangeRefused;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ChangeTest extends TestCase
{
    private const FIELDS = ['name' => ['old' => 'a', 'new' => 'b']];

    private const RELATIONS = ['tags' => ['added' => [1], 'removed' => []]];

    public function test_a_created_entry_keeps_the_initial_values(): void
    {
        $change = $this->record(ChangeKind::Created, changes: ['name' => ['old' => null, 'new' => 'b']]);

        $this->assertSame(['name' => ['old' => null, 'new' => 'b']], $change->changes());
    }

    public function test_a_changed_entry_keeps_fields_and_relations(): void
    {
        $change = $this->record(ChangeKind::Changed, changes: self::FIELDS, relations: self::RELATIONS);

        $this->assertSame(self::FIELDS, $change->changes());
        $this->assertSame(self::RELATIONS, $change->relationChanges());
    }

    public function test_a_changed_entry_with_nothing_changed_is_refused(): void
    {
        $this->expectException(ChangeRefused::class);

        $this->record(ChangeKind::Changed);
    }

    public function test_a_deleted_entry_keeps_the_last_title_and_no_values(): void
    {
        $change = $this->record(ChangeKind::Deleted, title: 'Rzecz A', changes: self::FIELDS, relations: self::RELATIONS);

        $this->assertSame('Rzecz A', $change->title());
        $this->assertSame([], $change->changes());
        $this->assertSame([], $change->relationChanges());
    }

    public function test_a_restored_entry_holds_no_values_and_no_title(): void
    {
        $change = $this->record(ChangeKind::Restored, title: 'Rzecz A', changes: self::FIELDS);

        $this->assertNull($change->title());
        $this->assertSame([], $change->changes());
    }

    public function test_the_title_is_kept_for_deleted_entries_only(): void
    {
        $this->assertNull($this->record(ChangeKind::Created, title: 'x', changes: self::FIELDS)->title());
    }

    public function test_the_actor_is_a_user_or_a_system_with_a_reason(): void
    {
        $user = $this->record(ChangeKind::Created, actorId: 3, reason: null);
        $system = $this->record(ChangeKind::Created, actorId: null, reason: 'Harmonogram');

        $this->assertSame(3, $user->actorId());
        $this->assertSame('Harmonogram', $system->actorReason());
    }

    public function test_an_entry_with_both_or_neither_actor_is_refused(): void
    {
        foreach ([[3, 'Harmonogram'], [null, null], [null, '  ']] as [$id, $reason]) {
            try {
                $this->record(ChangeKind::Created, actorId: $id, reason: $reason);
                $this->fail('Expected ChangeRefused');
            } catch (ChangeRefused) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int|string>, removed: list<int|string>}>  $relations
     */
    private function record(
        ChangeKind $kind,
        ?string $title = null,
        array $changes = [],
        array $relations = [],
        ?int $actorId = 1,
        ?string $reason = null,
    ): Change {
        return Change::record('App\\Thing', 5, $kind, $title, $changes, $relations, $actorId, $reason, new DateTimeImmutable);
    }
}
