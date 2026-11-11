<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\Application\Commands\SaveThumbnailSettings;

use App\Core\Contracts\Authorizer;
use App\Modules\Catalogue\Domain\Entities\ThumbnailMapping;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Repositories\DictionaryValueRepository;
use App\Modules\Catalogue\Domain\Repositories\ThumbnailMappingRepository;
use App\Modules\Catalogue\Domain\ValueObjects\FillColour;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-15-FR-17, RULE-12: writes mappings only. It changes no Glass model and raises no event, because
 * a mapping change is not a change of any Glass model (D-57).
 */
final class SaveThumbnailSettingsCommandHandler
{
    /** FR-16: the opacity of a colour whose form left it blank. */
    private const int FULL_OPACITY = 100;

    public function __construct(
        private Authorizer $authorizer,
        private DictionaryValueRepository $values,
        private ThumbnailMappingRepository $mappings,
    ) {}

    #[CommandHandler]
    public function handle(SaveThumbnailSettingsCommand $command): void
    {
        $this->authorizer->ensure($command->actor, CataloguePermission::Manage);

        foreach ($command->positions as $valueId => $position) {
            $this->apply($valueId, fn (Dictionary $dictionary): ?ThumbnailMapping => $position['shape'] === null
                ? null
                : ThumbnailMapping::forPosition($valueId, $dictionary, GlassShape::from($position['shape']), $position['mirrored']));
        }

        foreach ($command->colours as $valueId => $colour) {
            $this->apply($valueId, fn (Dictionary $dictionary): ?ThumbnailMapping => $colour['fill'] === null
                ? null
                : ThumbnailMapping::forColour($valueId, $dictionary, FillColour::from($colour['fill']), $colour['opacity'] ?? self::FULL_OPACITY));
        }

        foreach ($command->equipment as $valueId => $pictogram) {
            $this->apply($valueId, fn (Dictionary $dictionary): ?ThumbnailMapping => $pictogram === null
                ? null
                : ThumbnailMapping::forEquipment($valueId, $dictionary, Pictogram::from($pictogram)));
        }
    }

    /**
     * @param  callable(Dictionary): ?ThumbnailMapping  $wanted
     */
    private function apply(int $valueId, callable $wanted): void
    {
        $dictionary = $this->values->get($valueId)->dictionary();
        $existing = $this->mappings->findForValue($valueId);
        $mapping = $wanted($dictionary);

        if ($mapping === null) {
            if ($existing !== null) {
                $this->mappings->remove($existing);
            }

            return;
        }

        if ($existing === null) {
            $this->mappings->add($mapping);

            return;
        }

        $existing->replaceWith($mapping);
        $this->mappings->save($existing);
    }
}
