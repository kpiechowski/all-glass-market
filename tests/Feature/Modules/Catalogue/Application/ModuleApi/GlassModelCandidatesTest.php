<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelCandidates;
use App\Modules\Catalogue\Domain\Contracts\Candidate;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\MatchRoute;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-22, D-49. */
class GlassModelCandidatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_candidates_with_what_they_matched(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('8888AGN')->create();
        GlassModelModelFactory::new()->withMainEurocode('1111XYZ')->create();

        $candidates = app(GlassModelCandidates::class)->search('888');

        $this->assertEquals([new Candidate($model->id, MatchRoute::Eurocode, '8888AGN')], $candidates);
    }

    public function test_a_text_that_is_too_short_finds_nothing(): void
    {
        GlassModelModelFactory::new()->withMainEurocode('8888AGN')->create();

        $this->assertSame([], app(GlassModelCandidates::class)->search('88'));
    }

    public function test_withdrawn_models_are_candidates_only_on_request_and_filters_apply(): void
    {
        $front = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $active = GlassModelModelFactory::new()->withMainEurocode('2222AAA')->withPosition($front)->create();
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('2222BBB')->withPosition($front)->withdrawn()->create();
        GlassModelModelFactory::new()->withMainEurocode('2222CCC')->create();
        $api = app(GlassModelCandidates::class);

        $this->assertSame([$active->id], array_map(fn (Candidate $c): int => $c->glassModelId, $api->search('2222', positionId: $front->id)));
        $this->assertContains($withdrawn->id, array_map(fn (Candidate $c): int => $c->glassModelId, $api->search('2222', positionId: $front->id, includeWithdrawn: true)));
    }

    public function test_matching_ids_are_not_limited(): void
    {
        for ($i = 10; $i < 35; $i++) {
            GlassModelModelFactory::new()->withMainEurocode('AAA'.$i)->create();
        }
        $api = app(GlassModelCandidates::class);

        $this->assertCount(20, $api->search('AAA'));
        $this->assertCount(25, $api->matchingIds('AAA'));
    }

    public function test_matching_ids_without_a_text_follow_the_filters(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $vehicle = VehicleModelFactory::new()->ofMake($make->id)->create();
        $byPosition = GlassModelModelFactory::new()->withPosition($position)->create();
        $byColour = GlassModelModelFactory::new()->withColour($colour)->create();
        $byMake = GlassModelModelFactory::new()->fitting($vehicle)->create();
        $withdrawn = GlassModelModelFactory::new()->withPosition($position)->withdrawn()->create();
        GlassModelModelFactory::new()->create();
        $api = app(GlassModelCandidates::class);

        $this->assertSame([$byPosition->id], $api->matchingIds('', positionId: $position->id));
        $this->assertSame([$byColour->id], $api->matchingIds('  ', colourId: $colour->id));
        $this->assertSame([$byMake->id], $api->matchingIds('88', makeId: $make->id));
        $this->assertSame([$byPosition->id, $withdrawn->id], $api->matchingIds('', positionId: $position->id, includeWithdrawn: true));
    }

    public function test_matching_ids_without_a_text_and_without_a_filter_are_empty(): void
    {
        GlassModelModelFactory::new()->create();

        $this->assertSame([], app(GlassModelCandidates::class)->matchingIds(''));
        $this->assertSame([], app(GlassModelCandidates::class)->matchingIds('', includeWithdrawn: true));
    }
}
