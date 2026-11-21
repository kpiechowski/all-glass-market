<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;

/**
 * A thumbnail (FR-18) as values only: the glass shape, whether it is mirrored, its fill and opacity,
 * and one pictogram per equipment value with the value's full name for its tooltip. It is drawn by
 * `<x-catalogue::glass-thumbnail>`.
 */
final readonly class Thumbnail
{
    /** @param  list<array{pictogram: Pictogram, label: string}>  $pictograms  in name order */
    public function __construct(
        public GlassShape $shape,
        public bool $mirrored,
        public string $fill,
        public int $opacity,
        public array $pictograms,
    ) {}
}
