<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Contracts\CandidateQuery;
use App\Modules\Catalogue\Domain\Contracts\CandidateSearch;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\MatchRoute;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-22, D-49: ordering, labels, one result per model, withdrawn on request, filters, limit. */
class EloquentCandidateSearchTest extends TestCase
{
    use RefreshDatabase;

    private CandidateSearch $search;

    protected function setUp(): void
    {
        parent::setUp();

        $this->search = app(CandidateSearch::class);
    }

    public function test_it_orders_eurocode_hits_then_oem_hits_then_vehicle_hits(): void
    {
        $vehicleHit = $this->modelFitting('Audi 861 sedan');
        $oemHit = GlassModelModelFactory::new()->withMainEurocode('1111AAA')->create();
        $this->oem($oemHit, '861-000');
        $eurocodeHit = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        $ids = array_map(fn ($candidate) => [$candidate->glassModelId, $candidate->route], $this->search->search(new CandidateQuery('861')));

        $this->assertSame([
            [$eurocodeHit->id, MatchRoute::Eurocode],
            [$oemHit->id, MatchRoute::Oem],
            [$vehicleHit->id, MatchRoute::Vehicle],
        ], $ids);
    }

    public function test_every_candidate_says_what_it_matched(): void
    {
        $eurocode = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        $oem = GlassModelModelFactory::new()->create();
        $this->oem($oem, '1K0-845-011');
        $vehicle = $this->modelFitting('Audi A4 B8');

        $this->assertSame('8613AGS', $this->search->search(new CandidateQuery('8613'))[0]->matched);
        $this->assertSame('1K0-845-011', $this->search->search(new CandidateQuery('845'))[0]->matched);
        $this->assertSame('Audi A4 B8', $this->search->search(new CandidateQuery('A4 B8'))[0]->matched);
        $this->assertSame($vehicle->id, $this->search->search(new CandidateQuery('A4 B8'))[0]->glassModelId);
        $this->assertNotNull($eurocode);
    }

    public function test_a_further_eurocode_finds_the_model_and_is_the_matched_value(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();
        GlassModelEurocodeModelFactory::new()->state(['glass_model_id' => $model->id, 'code' => '9999ZZZ', 'is_main' => false])->create();

        $candidates = $this->search->search(new CandidateQuery('9999'));

        $this->assertSame([$model->id], array_map(fn ($c) => $c->glassModelId, $candidates));
        $this->assertSame('9999ZZZ', $candidates[0]->matched);
    }

    public function test_a_model_matching_by_several_routes_appears_once_under_its_best_route(): void
    {
        $model = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->fitting(VehicleModelFactory::new()->named('Audi 8613 coupe')->create())->create();
        $this->oem($model, '8613-X');

        $candidates = $this->search->search(new CandidateQuery('8613'));

        $this->assertCount(1, $candidates);
        $this->assertSame(MatchRoute::Eurocode, $candidates[0]->route);
    }

    public function test_one_oem_code_on_several_models_gives_every_model(): void
    {
        $first = GlassModelModelFactory::new()->create();
        $second = GlassModelModelFactory::new()->create();
        $this->oem($first, '1K0-845-011');
        $this->oem($second, '1K0 845 011');

        $ids = array_map(fn ($c) => $c->glassModelId, $this->search->search(new CandidateQuery('1k0845011')));

        $this->assertSame([$first->id, $second->id], $ids);
    }

    public function test_oem_codes_are_compared_ignoring_spaces_dashes_and_dots(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $this->oem($model, '1K0-845.011 A');

        $this->assertSame([$model->id], $this->search->matchingIds(new CandidateQuery('1K0 845-011a')));
        $this->assertSame([$model->id], $this->search->matchingIds(new CandidateQuery('845011')));
    }

    public function test_a_vehicle_is_found_by_its_name_fields_and_make(): void
    {
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->named('Skoda')->create();
        $vehicle = VehicleModelFactory::new()->named('Octavia II')->ofMake($make->id)->state(['generation' => 'Facelift', 'argic_code' => 'ZQ99'])->create();
        $model = GlassModelModelFactory::new()->fitting($vehicle)->create();

        foreach (['octavia', 'skoda octavia', 'facelift', 'zq99'] as $text) {
            $this->assertSame([$model->id], $this->search->matchingIds(new CandidateQuery($text)), $text);
        }
        $this->assertSame([], $this->search->matchingIds(new CandidateQuery('skoda fabia')));
    }

    public function test_a_text_below_the_minimum_fragment_finds_nothing(): void
    {
        GlassModelModelFactory::new()->withMainEurocode('8613AGS')->create();

        $this->assertSame([], $this->search->search(new CandidateQuery('86')));
        $this->assertSame([], $this->search->search(new CandidateQuery('')));
    }

    public function test_withdrawn_models_are_found_only_on_request(): void
    {
        $withdrawn = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withdrawn()->create();

        $this->assertSame([], $this->search->matchingIds(new CandidateQuery('8613')));
        $this->assertSame([$withdrawn->id], $this->search->matchingIds(new CandidateQuery('8613', includeWithdrawn: true)));
    }

    public function test_filters_narrow_every_route(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $make = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $fits = VehicleModelFactory::new()->ofMake($make->id)->create();
        $wanted = GlassModelModelFactory::new()->withMainEurocode('8613AGS')->withPosition($position)->withColour($colour)->fitting($fits)->create();
        GlassModelModelFactory::new()->withMainEurocode('8613BGS')->withColour($colour)->fitting($fits)->create();
        GlassModelModelFactory::new()->withMainEurocode('8613CGS')->withPosition($position)->fitting($fits)->create();
        GlassModelModelFactory::new()->withMainEurocode('8613DGS')->withPosition($position)->withColour($colour)->create();

        $ids = $this->search->matchingIds(new CandidateQuery('8613', positionId: $position->id, colourId: $colour->id, makeId: $make->id));

        $this->assertSame([$wanted->id], $ids);
    }

    public function test_the_limit_counts_across_routes_and_matching_ids_ignore_it(): void
    {
        foreach (range(1, 3) as $n) {
            GlassModelModelFactory::new()->withMainEurocode('8613A0'.$n)->create();
        }
        $oem = GlassModelModelFactory::new()->create();
        $this->oem($oem, '8613-1');

        $limited = $this->search->search(new CandidateQuery('8613', limit: 2));

        $this->assertCount(2, $limited);
        $this->assertSame([MatchRoute::Eurocode, MatchRoute::Eurocode], array_map(fn ($c) => $c->route, $limited));
        $this->assertCount(4, $this->search->matchingIds(new CandidateQuery('8613', limit: 2)));
        $this->assertGreaterThan(0, GlassModelModel::query()->count());
    }

    private function oem(GlassModelModel $model, string $code): void
    {
        GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $model->id])->code($code)->create();
    }

    private function modelFitting(string $vehicleName): GlassModelModel
    {
        return GlassModelModelFactory::new()->fitting(VehicleModelFactory::new()->named($vehicleName)->create())->create();
    }
}
