<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\SaveThumbnailSettings;

use App\Core\Domain\Actor;

/**
 * The whole catalogue settings page (FR-15-FR-17), each map keyed by dictionary value id. An entry
 * with no shape, fill or pictogram means "unmapped": the mapping is removed and FR-18's default
 * applies. A value left out of its map is not touched.
 */
final readonly class SaveThumbnailSettingsCommand
{
    /**
     * @param  array<int, array{shape: ?string, mirrored: bool}>  $positions
     * @param  array<int, array{fill: ?string, opacity: ?int}>  $colours
     * @param  array<int, ?string>  $equipment  pictogram by value id
     */
    public function __construct(
        public Actor $actor,
        public array $positions = [],
        public array $colours = [],
        public array $equipment = [],
    ) {}
}
