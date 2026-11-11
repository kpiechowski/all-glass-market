<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlassModelQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_loads_what_the_list_shows(): void
    {
        GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        $model = (new GlassModelQueries)->listing()->first();

        $this->assertTrue($model->relationLoaded('mainEurocode'));
        $this->assertSame('8613AGS', $model->mainEurocode->code);
    }

    public function test_active_and_withdrawn_scopes_exclude_each_other(): void
    {
        $active = GlassModelModelFactory::new()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();
        $queries = new GlassModelQueries;

        $this->assertSame([$active->id], $queries->onlyActive($queries->listing())->pluck('id')->all());
        $this->assertSame([$withdrawn->id], $queries->onlyWithdrawn($queries->listing())->pluck('id')->all());
    }
}
