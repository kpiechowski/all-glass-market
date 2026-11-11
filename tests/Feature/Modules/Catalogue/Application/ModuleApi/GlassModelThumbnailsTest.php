<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelThumbnails;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Policies\ThumbnailDrawingPolicy;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-18, D-57. */
class GlassModelThumbnailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_draws_from_the_stored_mappings(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        ThumbnailMappingModelFactory::new()->forPosition($position, GlassShape::Rear, true)->create();
        ThumbnailMappingModelFactory::new()->forColour($colour, '#336699', 30)->create();
        ThumbnailMappingModelFactory::new()->forEquipment($equipment, Pictogram::Camera)->create();

        $thumbnail = app(GlassModelThumbnails::class)->for($position->id, $colour->id, [$equipment->id => 'Kamera cofania']);

        $this->assertStringContainsString(GlassShape::Rear->outline(), $thumbnail->svg);
        $this->assertStringContainsString('scale(-1 1)', $thumbnail->svg);
        $this->assertStringContainsString('fill="#336699"', $thumbnail->svg);
        $this->assertSame('Kamera cofania', $thumbnail->pictograms[0]['label']);
        $this->assertStringContainsString(Pictogram::Camera->markup(), $thumbnail->pictograms[0]['svg']);
    }

    public function test_unmapped_values_get_the_defaults(): void
    {
        $thumbnail = app(GlassModelThumbnails::class)->for(null, null, [5 => 'Coś']);

        $this->assertStringContainsString(GlassShape::Unknown->outline(), $thumbnail->svg);
        $this->assertStringContainsString('fill="'.ThumbnailDrawingPolicy::CLEAR_FILL.'"', $thumbnail->svg);
        $this->assertStringContainsString(Pictogram::Universal->markup(), $thumbnail->pictograms[0]['svg']);
    }
}
