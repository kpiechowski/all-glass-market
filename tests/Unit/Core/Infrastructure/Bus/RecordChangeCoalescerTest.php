<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Infrastructure\Bus;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Core\Domain\Events\RecordChanged;
use App\Core\Infrastructure\Bus\RecordChangeCoalescer;
use PHPUnit\Framework\TestCase;

class RecordChangeCoalescerTest extends TestCase
{
    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, array{added: list<int>, removed: list<int>}>  $relations
     */
    private function event(ChangeKind $kind, array $changes = [], array $relations = [], int $id = 1): RecordChanged
    {
        return new RecordChanged('M', $id, $kind, $changes, $relations, Actor::user(1));
    }

    /**
     * @param  list<RecordChanged>  $events
     * @return list<RecordChanged>
     */
    private function coalesce(array $events): array
    {
        return (new RecordChangeCoalescer)->coalesce($events);
    }

    public function test_changed_folds_into_created_keeping_the_kind(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Created, ['name' => ['old' => null, 'new' => 'a']]),
            $this->event(ChangeKind::Changed, ['name' => ['old' => 'a', 'new' => 'b']], ['tags' => ['added' => [1], 'removed' => []]]),
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(ChangeKind::Created, $result[0]->kind);
        $this->assertSame(['name' => ['old' => null, 'new' => 'b']], $result[0]->changes);
        $this->assertSame(['tags' => ['added' => [1], 'removed' => []]], $result[0]->relationChanges);
    }

    public function test_fields_keep_first_old_and_last_new_and_a_reverted_field_is_dropped(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Changed, ['price' => ['old' => 1, 'new' => 2], 'stock' => ['old' => 5, 'new' => 6]]),
            $this->event(ChangeKind::Changed, ['price' => ['old' => 2, 'new' => 3], 'stock' => ['old' => 6, 'new' => 5]]),
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(['price' => ['old' => 1, 'new' => 3]], $result[0]->changes);
    }

    public function test_relation_diffs_unite_and_an_added_then_removed_id_cancels_out(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Changed, [], ['tags' => ['added' => [1, 2], 'removed' => [9]]]),
            $this->event(ChangeKind::Changed, [], ['tags' => ['added' => [3], 'removed' => [1]]]),
        ]);

        $this->assertSame(['tags' => ['added' => [2, 3], 'removed' => [9]]], $result[0]->relationChanges);
    }

    public function test_a_changed_that_ends_up_empty_is_dropped(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Changed, [], ['tags' => ['added' => [1], 'removed' => []]]),
            $this->event(ChangeKind::Changed, [], ['tags' => ['added' => [], 'removed' => [1]]]),
        ]);

        $this->assertSame([], $result);
    }

    public function test_deleted_and_restored_start_new_events_in_order(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Changed, ['a' => ['old' => 1, 'new' => 2]]),
            $this->event(ChangeKind::Deleted),
            $this->event(ChangeKind::Restored),
            $this->event(ChangeKind::Changed, ['a' => ['old' => 2, 'new' => 3]]),
        ]);

        $this->assertSame(
            [ChangeKind::Changed, ChangeKind::Deleted, ChangeKind::Restored, ChangeKind::Changed],
            array_map(fn (RecordChanged $e) => $e->kind, $result),
        );
    }

    public function test_different_records_are_not_folded_together(): void
    {
        $result = $this->coalesce([
            $this->event(ChangeKind::Changed, ['a' => ['old' => 1, 'new' => 2]], id: 1),
            $this->event(ChangeKind::Changed, ['a' => ['old' => 1, 'new' => 2]], id: 2),
        ]);

        $this->assertCount(2, $result);
    }
}
