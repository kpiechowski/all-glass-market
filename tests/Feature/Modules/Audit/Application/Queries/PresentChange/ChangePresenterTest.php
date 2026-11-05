<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Queries\PresentChange;

use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\ModuleApi\AuditableRecordType;
use App\Modules\Audit\Application\ModuleApi\TrackedRelation;
use App\Modules\Audit\Application\ModuleApi\ValuePresenter;
use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Application\Queries\PresentChange\PresentedLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Audit\ProducerModel;
use Tests\Fixtures\Audit\RegistersThings;
use Tests\Fixtures\Audit\ThingModel;
use Tests\Fixtures\Audit\ThingStatus;
use Tests\TestCase;

class ChangePresenterTest extends TestCase
{
    use RefreshDatabase, RegistersThings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpThings();
        app(AuditableRecords::class)->register(new AuditableRecordType(
            'things.thing', ThingModel::class, 'Rzecz', fn (): string => 'x', fn (): ?string => null,
            fieldLabels: ['name' => 'Nazwa', 'producer_id' => 'Producent', 'price' => 'Cena', 'status' => 'Status', 'due' => 'Termin', 'tags' => 'Tagi', 'flag' => 'Flaga'],
            valuePresenters: [
                'producer_id' => ValuePresenter::reference(ProducerModel::class),
                'price' => ValuePresenter::money(),
                'status' => ValuePresenter::enum(ThingStatus::class),
                'due' => ValuePresenter::date(),
                'tags' => ValuePresenter::list(),
            ],
            trackedRelations: ['producers' => new TrackedRelation('Producenci', ValuePresenter::reference(ProducerModel::class))],
        ));
    }

    public function test_a_reference_shows_the_current_name_after_a_rename(): void
    {
        $id = DB::table('audit_producers')->insertGetId(['name' => 'Stary']);
        DB::table('audit_producers')->where('id', $id)->update(['name' => 'Nowy']);

        $line = $this->present(['producer_id' => ['old' => $id, 'new' => $id]])[0];

        $this->assertSame('Producent', $line->label);
        $this->assertSame('Nowy', $line->new->text);
        $this->assertFalse($line->new->removed);
    }

    public function test_a_soft_deleted_reference_shows_its_name_marked_removed(): void
    {
        $id = DB::table('audit_producers')->insertGetId(['name' => 'Pilkington', 'deleted_at' => now()]);

        $line = $this->present(['producer_id' => ['old' => null, 'new' => $id]])[0];

        $this->assertSame('Pilkington', $line->new->text);
        $this->assertTrue($line->new->removed);
        $this->assertFalse($line->old->removed);
    }

    public function test_a_reference_gone_entirely_shows_its_id_marked_removed(): void
    {
        $line = $this->present(['producer_id' => ['old' => 999, 'new' => null]])[0];

        $this->assertSame('999', $line->old->text);
        $this->assertTrue($line->old->removed);
    }

    public function test_a_field_no_longer_registered_shows_under_its_technical_name_with_raw_values(): void
    {
        $line = $this->present(['legacy_code' => ['old' => 'A1', 'new' => ['x' => 1]]])[0];

        $this->assertSame('legacy_code', $line->label);
        $this->assertSame('A1', $line->old->text);
        $this->assertSame('{"x":1}', $line->new->text);
    }

    public function test_a_whole_record_type_no_longer_registered_shows_raw_values(): void
    {
        $lines = app(ChangePresenter::class)->present('App\\Gone', ['name' => ['old' => 'a', 'new' => 'b']], ['tags' => ['added' => [1], 'removed' => []]]);

        $this->assertSame('name', $lines[0]->label);
        $this->assertSame('tags', $lines[1]->label);
        $this->assertSame('1', $lines[1]->added[0]->text);
    }

    public function test_values_are_shown_by_their_presenter(): void
    {
        $lines = $this->present([
            'price' => ['old' => 1200, 'new' => 1250.5],
            'status' => ['old' => 'draft', 'new' => 'active'],
            'due' => ['old' => '2026-11-05', 'new' => '2026-11-06 14:30:00'],
            'tags' => ['old' => null, 'new' => ['a', 'b']],
            'flag' => ['old' => false, 'new' => true],
        ]);

        $this->assertSame(['1 200 zł', '1 250,50 zł'], [$lines[0]->old->text, $lines[0]->new->text]);
        $this->assertSame(['Szkic', 'Aktywna'], [$lines[1]->old->text, $lines[1]->new->text]);
        $this->assertSame(['05.11.2026', '06.11.2026 14:30'], [$lines[2]->old->text, $lines[2]->new->text]);
        $this->assertSame(['—', 'a, b'], [$lines[3]->old->text, $lines[3]->new->text]);
        $this->assertSame(['nie', 'tak'], [$lines[4]->old->text, $lines[4]->new->text]);
    }

    public function test_relation_items_are_named_added_and_removed(): void
    {
        $kept = DB::table('audit_producers')->insertGetId(['name' => 'Saint-Gobain']);

        $line = app(ChangePresenter::class)->present('things.thing', [], ['producers' => ['added' => [$kept], 'removed' => [999]]])[0];

        $this->assertSame('Producenci', $line->label);
        $this->assertSame('Saint-Gobain', $line->added[0]->text);
        $this->assertTrue($line->removed[0]->removed);
        $this->assertSame('999', $line->removed[0]->text);
    }

    /**
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @return list<PresentedLine>
     */
    private function present(array $changes): array
    {
        return app(ChangePresenter::class)->present('things.thing', $changes, []);
    }
}
