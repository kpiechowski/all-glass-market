<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Services;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\ValueObjects\ThumbnailDrawing;

/**
 * Writes the SVG of a drawing decided by ThumbnailDrawingPolicy. Only shipped geometry, a validated
 * #rrggbb fill and an integer opacity reach the markup (no custom shapes, FR-16). Strokes use
 * `currentColor`, so the theme colours the outline.
 */
final class ThumbnailSvg
{
    public function shape(GlassShape $shape, bool $mirrored, string $fill, int $opacity): string
    {
        $flip = $mirrored ? ' transform="translate(100 0) scale(-1 1)"' : '';
        $dash = $shape === GlassShape::Unknown ? ' stroke-dasharray="5 4"' : '';

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" class="glass-thumbnail__shape" aria-hidden="true">'
            .'<g%s><path d="%s" fill="%s" fill-opacity="%s" stroke="currentColor" stroke-width="2" stroke-linejoin="round"%s/></g></svg>',
            GlassShape::VIEW_BOX,
            $flip,
            $shape->outline(),
            $fill,
            $opacity / 100,
            $dash,
        );
    }

    public function drawing(ThumbnailDrawing $drawing): string
    {
        return $this->shape($drawing->shape, $drawing->mirrored, $drawing->fill, $drawing->opacity);
    }

    public function pictogram(Pictogram $pictogram): string
    {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" class="glass-thumbnail__pictogram" fill="none" stroke="currentColor" '
            .'stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%s</svg>',
            Pictogram::VIEW_BOX,
            $pictogram->markup(),
        );
    }
}
