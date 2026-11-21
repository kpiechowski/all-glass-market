<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\UserInterface\Components;

use App\Modules\Catalogue\Application\ModuleApi\Thumbnail;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Policies\ThumbnailDrawingPolicy;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/** FR-15, FR-16, FR-18: `<x-catalogue::glass-thumbnail>` draws the DTO's values. */
class GlassThumbnailComponentTest extends TestCase
{
    private function render(Thumbnail $thumbnail, string $attributes = ''): string
    {
        return Blade::render('<x-catalogue::glass-thumbnail :thumbnail="$thumbnail" '.$attributes.' />', ['thumbnail' => $thumbnail]);
    }

    public function test_a_mapped_thumbnail_draws_its_shape_fill_flip_and_pictograms(): void
    {
        $html = $this->render(new Thumbnail(GlassShape::Roof, true, '#1a8a3c', 60, [
            ['pictogram' => Pictogram::Camera, 'label' => 'Kamera <cofania>'],
        ]));

        $this->assertStringContainsString('d="'.GlassShape::Roof->outline().'"', $html);
        $this->assertStringContainsString('fill="#1a8a3c"', $html);
        $this->assertStringContainsString('fill-opacity="0.6"', $html);
        $this->assertStringContainsString('scale(-1 1)', $html);
        $this->assertStringContainsString(Pictogram::Camera->markup(), $html);
        $this->assertStringContainsString('glass-thumbnail__pictogram', $html);
        $this->assertStringContainsString('aria-label="Kamera &lt;cofania&gt;"', $html);
    }

    public function test_an_unmapped_thumbnail_draws_the_dashed_default(): void
    {
        $html = $this->render(new Thumbnail(GlassShape::Unknown, false, ThumbnailDrawingPolicy::CLEAR_FILL, ThumbnailDrawingPolicy::CLEAR_OPACITY, []));

        $this->assertStringContainsString('d="'.GlassShape::Unknown->outline().'"', $html);
        $this->assertStringContainsString('stroke-dasharray="5 4"', $html);
        $this->assertStringNotContainsString('scale(-1 1)', $html);
        $this->assertStringContainsString('fill="'.ThumbnailDrawingPolicy::CLEAR_FILL.'"', $html);
    }

    public function test_pictograms_can_be_left_out_and_attributes_reach_the_wrapper(): void
    {
        $html = $this->render(
            new Thumbnail(GlassShape::Roof, false, '#ffffff', 50, [['pictogram' => Pictogram::Camera, 'label' => 'Kamera']]),
            ':pictograms="false" class="glass-cell__thumbnail"',
        );

        $this->assertStringNotContainsString('glass-thumbnail__pictogram', $html);
        $this->assertStringContainsString('class="glass-thumbnail glass-cell__thumbnail"', $html);
    }
}
