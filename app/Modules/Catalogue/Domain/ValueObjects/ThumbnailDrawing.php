<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;

/**
 * What the drawing rules decided for one Glass model (FR-18): the shape, whether it is mirrored,
 * the fill with its opacity, and the pictograms with the full name of each value as tooltip.
 */
final readonly class ThumbnailDrawing
{
    /** @param  list<array{pictogram: Pictogram, label: string}>  $pictograms */
    public function __construct(
        public GlassShape $shape,
        public bool $mirrored,
        public string $fill,
        public int $opacity,
        public array $pictograms,
    ) {}
}
