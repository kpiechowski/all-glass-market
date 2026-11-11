<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Application\Services;

use App\Modules\Catalogue\Application\Services\ThumbnailSvg;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use PHPUnit\Framework\TestCase;

/** FR-15, FR-16, FR-18: the markup follows the drawing. */
class ThumbnailSvgTest extends TestCase
{
    public function test_the_shape_carries_its_outline_fill_and_opacity(): void
    {
        $svg = (new ThumbnailSvg)->shape(GlassShape::Roof, false, '#1a8a3c', 60);

        $this->assertStringContainsString('d="'.GlassShape::Roof->outline().'"', $svg);
        $this->assertStringContainsString('fill="#1a8a3c"', $svg);
        $this->assertStringContainsString('fill-opacity="0.6"', $svg);
        $this->assertStringNotContainsString('scale(-1 1)', $svg);
    }

    public function test_a_mirrored_shape_is_flipped_horizontally(): void
    {
        $this->assertStringContainsString('scale(-1 1)', (new ThumbnailSvg)->shape(GlassShape::SideDrop, true, '#ffffff', 50));
    }

    public function test_every_shipped_shape_and_pictogram_draws(): void
    {
        foreach (GlassShape::cases() as $shape) {
            $this->assertStringContainsString('<path', (new ThumbnailSvg)->shape($shape, false, '#ffffff', 50));
        }

        foreach (Pictogram::cases() as $pictogram) {
            $this->assertStringContainsString($pictogram->markup(), (new ThumbnailSvg)->pictogram($pictogram));
        }
    }
}
