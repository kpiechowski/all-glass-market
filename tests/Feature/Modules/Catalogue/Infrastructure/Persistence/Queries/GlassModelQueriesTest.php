<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlassModelQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_loads_what_the_list_shows(): void
    {
        GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        $model = app(GlassModelQueries::class)->listing()->first();

        $this->assertTrue($model->relationLoaded('mainEurocode'));
        $this->assertSame('8613AGS', $model->mainEurocode->code);
    }

    public function test_active_and_withdrawn_scopes_exclude_each_other(): void
    {
        $active = GlassModelModelFactory::new()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();
        $queries = app(GlassModelQueries::class);

        $this->assertSame([$active->id], $queries->onlyActive($queries->listing())->pluck('id')->all());
        $this->assertSame([$withdrawn->id], $queries->onlyWithdrawn($queries->listing())->pluck('id')->all());
    }

    public function test_move_targets_match_any_eurocode_and_leave_out_the_given_model_and_non_matches(): void
    {
        $source = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $match = GlassModelModelFactory::new()->withMainEurocode('8613BGS')->withdrawn()->create();
        GlassModelModelFactory::new()->withMainEurocode('9999XXX')->create();

        $ids = app(GlassModelQueries::class)->moveTargets('8613', $source->id)->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
    }

    public function test_listing_loads_what_the_cards_show(): void
    {
        $vehicles = [VehicleModelFactory::new()->create(), VehicleModelFactory::new()->create()];
        GlassModelModelFactory::new()->fitting(...$vehicles)->create();

        $model = app(GlassModelQueries::class)->listing()->first();

        $this->assertSame(2, $model->vehicles_count);
        foreach (['eurocodes', 'oemCodes', 'equipment', 'position', 'colour'] as $relation) {
            $this->assertTrue($model->relationLoaded($relation), $relation);
        }
    }

    public function test_candidates_come_in_search_order_each_carrying_its_route_and_matched_value(): void
    {
        $vehicleHit = GlassModelModelFactory::new()->fitting(VehicleModelFactory::new()->named('Audi 861 sedan')->create())->create();
        $oemHit = GlassModelModelFactory::new()->create();
        GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $oemHit->id])->code('861-000')->create();
        $eurocodeHit = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelModelFactory::new()->withMainEurocode('7777XXX')->create();

        $models = app(GlassModelQueries::class)->candidates('861')->get();

        $this->assertSame([$eurocodeHit->id, $oemHit->id, $vehicleHit->id], $models->pluck('id')->all());
        $this->assertSame(['eurocode', 'oem', 'vehicle'], $models->pluck('match_route')->all());
        $this->assertSame(['8613AGS', '861-000', 'Audi 861 sedan'], $models->pluck('matched_value')->all());
        $this->assertTrue($models->first()->relationLoaded('eurocodes'));
    }

    public function test_candidates_are_empty_when_nothing_matches(): void
    {
        GlassModelModelFactory::new()->create();

        $this->assertSame([], app(GlassModelQueries::class)->candidates('zzzz')->pluck('id')->all());
    }

    public function test_the_filters_include_and_exclude(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $queries = app(GlassModelQueries::class);
        $match = GlassModelModelFactory::new()->withPosition($position)->withColour($colour)
            ->fitting(VehicleModelFactory::new()->ofMake($make->id)->create())->create();
        GlassModelModelFactory::new()->create();

        $this->assertSame([$match->id], $queries->ofPosition($queries->listing(), $position->id)->pluck('id')->all());
        $this->assertSame([$match->id], $queries->ofColour($queries->listing(), $colour->id)->pluck('id')->all());
        $this->assertSame([$match->id], $queries->fittingMake($queries->listing(), $make->id)->pluck('id')->all());
    }
}
