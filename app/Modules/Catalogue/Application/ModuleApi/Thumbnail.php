<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

/**
 * A drawn thumbnail (FR-18): the glass as SVG markup, and one entry per equipment value with the
 * pictogram's SVG and the value's full name for its tooltip.
 */
final readonly class Thumbnail
{
    /** @param  list<array{svg: string, label: string}>  $pictograms  in name order */
    public function __construct(
        public string $svg,
        public array $pictograms,
    ) {}
}
