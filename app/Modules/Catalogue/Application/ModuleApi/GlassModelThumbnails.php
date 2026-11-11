<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\Services\ThumbnailSvg;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Policies\ThumbnailDrawingPolicy;
use App\Modules\Catalogue\Domain\Repositories\ThumbnailMappingRepository;
use App\Modules\Catalogue\Domain\ValueObjects\ThumbnailSettings;

/**
 * D-57: the thumbnail of a Glass model, drawn from the catalogue settings. The admin cards use it
 * and so will the Glass model summary for Offers (ticket 08). The settings are read once per
 * request (the container scopes this class).
 */
final class GlassModelThumbnails
{
    private ?ThumbnailSettings $settings = null;

    public function __construct(
        private readonly ThumbnailMappingRepository $mappings,
        private readonly ThumbnailSvg $svg,
    ) {}

    /**
     * @param  array<int, string>  $equipment  value names by dictionary value id
     */
    public function for(?int $positionId, ?int $colourId, array $equipment): Thumbnail
    {
        $this->settings ??= ThumbnailSettings::of($this->mappings->all());

        $drawing = ThumbnailDrawingPolicy::draw($this->settings, $positionId, $colourId, $equipment);

        return new Thumbnail(
            $this->svg->drawing($drawing),
            array_map(
                fn (array $item): array => ['svg' => $this->svg->pictogram($item['pictogram']), 'label' => $item['label']],
                $drawing->pictograms,
            ),
        );
    }

    /** A shipped shape drawn on its own, for the settings page. */
    public function shapeSvg(GlassShape $shape, bool $mirrored = false): string
    {
        return $this->svg->shape($shape, $mirrored, ThumbnailDrawingPolicy::CLEAR_FILL, ThumbnailDrawingPolicy::CLEAR_OPACITY);
    }

    public function pictogramSvg(Pictogram $pictogram): string
    {
        return $this->svg->pictogram($pictogram);
    }
}
