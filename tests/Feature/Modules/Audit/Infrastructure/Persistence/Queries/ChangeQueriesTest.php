<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Infrastructure\Persistence\Queries;

use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\Infrastructure\Persistence\Queries\ChangeQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_record_returns_only_that_records_entries_newest_first(): void
    {
        $older = ChangeModel::factory()->forRecord('App\\Thing', 5)->create(['occurred_at' => now()->subDay()]);
        $newer = ChangeModel::factory()->forRecord('App\\Thing', 5)->create(['occurred_at' => now()]);
        ChangeModel::factory()->forRecord('App\\Thing', 6)->create();
        ChangeModel::factory()->forRecord('App\\Other', 5)->create();

        $ids = app(ChangeQueries::class)->forRecord('App\\Thing', 5)->pluck('id')->all();

        $this->assertSame([$newer->id, $older->id], $ids);
    }

    public function test_all_returns_every_entry_newest_first(): void
    {
        $older = ChangeModel::factory()->forRecord('App\\Thing', 5)->create(['occurred_at' => now()->subDay()]);
        $newer = ChangeModel::factory()->forRecord('App\\Other', 9)->create(['occurred_at' => now()]);

        $this->assertSame([$newer->id, $older->id], app(ChangeQueries::class)->all()->pluck('id')->all());
    }
}
