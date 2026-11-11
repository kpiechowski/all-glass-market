<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Domain\Policies;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\ValueObjects\ThumbnailDrawing;
use App\Modules\Catalogue\Domain\ValueObjects\ThumbnailSettings;

/**
 * FR-18, D-54-D-56: the one place that decides how a Glass model's thumbnail looks, from its
 * position, colour and equipment and the catalogue settings. Pure: no Filament, no I/O, so Offers
 * (the Glass model summary) draws the same thumbnail as the admin cards.
 */
final class ThumbnailDrawingPolicy
{
    /** Neutral clear glass: no colour, or a colour without a mapping. */
    public const string CLEAR_FILL = '#cfe6ee';

    public const int CLEAR_OPACITY = 45;

    /**
     * @param  array<int, string>  $equipment  value names by dictionary value id
     */
    public static function draw(ThumbnailSettings $settings, ?int $positionId, ?int $colourId, array $equipment): ThumbnailDrawing
    {
        $position = $positionId === null ? null : $settings->for($positionId);
        $colour = $colourId === null ? null : $settings->for($colourId);

        $pictograms = [];
        foreach ($equipment as $valueId => $name) {
            $pictograms[] = [
                'pictogram' => $settings->for($valueId)?->pictogram() ?? Pictogram::Universal,
                'label' => $name,
            ];
        }

        usort($pictograms, fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return new ThumbnailDrawing(
            shape: $position?->shape() ?? GlassShape::Unknown,
            mirrored: $position?->isMirrored() ?? false,
            fill: $colour?->fill()?->value ?? self::CLEAR_FILL,
            opacity: $colour?->fill() === null ? self::CLEAR_OPACITY : ($colour->opacity() ?? 100),
            pictograms: $pictograms,
        );
    }
}
