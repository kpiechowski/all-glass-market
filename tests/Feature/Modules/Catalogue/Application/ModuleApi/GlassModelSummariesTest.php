<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummary;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelEurocodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelOemCodeModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** FR-21, D-48, D-57. */
class GlassModelSummariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_summarises_one_model_with_everything_as_plain_values(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przód')->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielona')->create();
        $camera = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->named('Kamera')->create();
        $heated = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->named('Grzana')->create();
        $vehicle = VehicleModelFactory::new()->named('Audi A4 B8')->create();
        $model = GlassModelModelFactory::new()->withMainEurocode('8888AGN')
            ->withPosition($position)->withColour($colour)->withEquipment($camera, $heated)->fitting($vehicle)->create();
        GlassModelEurocodeModelFactory::new()->state(['glass_model_id' => $model->id, 'code' => '1111ABC', 'is_main' => false])->create();
        GlassModelOemCodeModelFactory::new()->state(['glass_model_id' => $model->id])->code('8K0-845-099')->create();

        $summary = app(GlassModelSummaries::class)->of($model->id);

        $this->assertInstanceOf(GlassModelSummary::class, $summary);
        $this->assertSame($model->id, $summary->id);
        $this->assertSame('8888AGN', $summary->mainEurocode);
        $this->assertSame(['8888AGN', '1111ABC'], $summary->eurocodes);
        $this->assertSame(['8K0-845-099'], $summary->oemCodes);
        $this->assertSame('Przód', $summary->position);
        $this->assertSame('Zielona', $summary->colour);
        $this->assertSame(['Grzana', 'Kamera'], $summary->equipment);
        $this->assertSame(['Audi A4 B8'], $summary->vehicles);
        $this->assertFalse($summary->withdrawn);
        $this->assertStringContainsString('<svg', $summary->thumbnail->svg);
        $this->assertCount(2, $summary->thumbnail->pictograms);
    }

    public function test_a_model_without_colour_has_none_and_a_withdrawn_one_says_so(): void
    {
        $model = GlassModelModelFactory::new()->withdrawn()->create();

        $summary = app(GlassModelSummaries::class)->of($model->id);

        $this->assertNull($summary->colour);
        $this->assertTrue($summary->withdrawn);
    }

    public function test_an_unknown_id_has_no_summary(): void
    {
        $this->assertNull(app(GlassModelSummaries::class)->of(999));
    }

    public function test_many_ids_come_keyed_by_id_and_skip_unknown_ones(): void
    {
        $first = GlassModelModelFactory::new()->create();
        $second = GlassModelModelFactory::new()->create();

        $summaries = app(GlassModelSummaries::class)->ofMany([$first->id, $second->id, 999]);

        $this->assertSame([$first->id, $second->id], array_keys($summaries));
        $this->assertSame($second->id, $summaries[$second->id]->id);
    }

    public function test_many_ids_cost_the_same_queries_as_two(): void
    {
        $count = function (int $models): int {
            $ids = [];
            for ($i = 0; $i < $models; $i++) {
                $ids[] = GlassModelModelFactory::new()
                    ->withEquipment(DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create())
                    ->fitting(VehicleModelFactory::new()->create())->create()->id;
            }
            $api = app(GlassModelSummaries::class);
            $api->ofMany($ids); // the thumbnail settings are read once per request: warm them
            DB::flushQueryLog();
            DB::enableQueryLog();
            $api->ofMany($ids);
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $few = $count(2);
        $many = $count(8);

        $this->assertSame($few, $many);
    }
}
