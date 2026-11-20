<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\ModuleApi;

use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\RecordChangesAfter;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-12 (Offers' read): a record's changes after the latest entry that changed a named field. */
class RecordChangesAfterTest extends TestCase
{
    use RefreshDatabase;

    private const TYPE = 'things.thing';

    protected function setUp(): void
    {
        parent::setUp();

        app(AuditableRecords::class)->register(new AuditableRecordType(
            self::TYPE, 'App\\Thing', 'Rzecz', fn (): string => 'x', fn (): ?string => null,
            fieldLabels: ['name' => 'Nazwa', 'code' => 'Kod', 'approved_at' => 'Zatwierdzono'],
            valuePresenters: ['code' => ValuePresenter::resolved(fn (mixed $id): ?string => $id === 7 ? 'EC-7' : null)],
        ));
    }

    private function entry(array $changes, ?int $recordId = 1, ChangeKind $kind = ChangeKind::Changed): ChangeModel
    {
        return ChangeModel::factory()->forRecord(self::TYPE, $recordId)->create(['changes' => $changes, 'kind' => $kind]);
    }

    public function test_it_returns_only_the_changes_after_the_milestone_entry_oldest_first(): void
    {
        $this->entry(['name' => ['old' => 'a', 'new' => 'b']]);
        $this->entry(['approved_at' => ['old' => null, 'new' => '2026-01-01 10:00:00'], 'name' => ['old' => 'b', 'new' => 'c']]);
        $this->entry(['name' => ['old' => 'c', 'new' => 'd']]);
        $this->entry(['code' => ['old' => 7, 'new' => 9]]);

        $lines = app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at');

        $this->assertCount(2, $lines);
        $this->assertSame('Nazwa', $lines[0]->label);
        $this->assertSame('c', $lines[0]->old->text);
        $this->assertSame('d', $lines[0]->new->text);
        $this->assertSame('Kod', $lines[1]->label);
        $this->assertSame('EC-7', $lines[1]->old->text);
        $this->assertSame('9', $lines[1]->new->text);
    }

    public function test_the_latest_milestone_counts(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => 'a', 'new' => 'b']]);
        $this->entry(['approved_at' => ['old' => 'x', 'new' => 'y']]);

        $this->assertSame([], app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at'));
    }

    public function test_it_is_null_when_the_field_never_changed(): void
    {
        $this->entry(['name' => ['old' => 'a', 'new' => 'b']]);

        $this->assertNull(app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at'));
    }

    public function test_other_records_and_other_types_do_not_leak_in(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => 'a', 'new' => 'b']], 2);
        ChangeModel::factory()->forRecord('other.type', 1)->create(['changes' => ['name' => ['old' => 'a', 'new' => 'b']]]);

        $this->assertSame([], app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at'));
    }

    public function test_a_field_edited_twice_shows_one_net_line(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => 'A', 'new' => 'B']]);
        $this->entry(['name' => ['old' => 'B', 'new' => 'C']]);

        $lines = app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at');

        $this->assertCount(1, $lines);
        $this->assertSame('A', $lines[0]->old->text);
        $this->assertSame('C', $lines[0]->new->text);
    }

    public function test_a_field_that_started_empty_keeps_the_empty_value_as_old(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => null, 'new' => 'b']]);
        $this->entry(['name' => ['old' => 'b', 'new' => 'c']]);

        $lines = app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at');

        $this->assertCount(1, $lines);
        $this->assertNotSame('b', $lines[0]->old?->text);
        $this->assertSame('c', $lines[0]->new->text);
    }

    public function test_a_field_changed_and_reverted_shows_no_line(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => 'A', 'new' => 'B']]);
        $this->entry(['name' => ['old' => 'B', 'new' => 'A']]);

        $this->assertSame([], app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at'));
    }

    public function test_excluded_fields_are_left_out(): void
    {
        $this->entry(['approved_at' => ['old' => null, 'new' => 'x']]);
        $this->entry(['name' => ['old' => 'A', 'new' => 'B'], 'code' => ['old' => 1, 'new' => 2]]);

        $lines = app(RecordChangesAfter::class)->lastChangeOf(self::TYPE, 1, 'approved_at', ['name']);

        $this->assertCount(1, $lines);
        $this->assertSame('Kod', $lines[0]->label);
    }
}
