<?php

declare(strict_types=1);

namespace App\Modules\Offers\UserInterface\Filament\Resources\Offers\Support;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelCardLink;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelLinkProvider;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;

/**
 * FR-25, Catalogue FR-04: the "N ofert" link on a Glass model card. It counts the Offers the viewer
 * may see, outside the Trash, every status, for a whole page of cards in one query, and leads to the
 * list narrowed to that model (ticket 10's filter). A model without Offers gets no link.
 */
final class OfferGlassModelLinks implements GlassModelLinkProvider
{
    public function __construct(private readonly OfferQueries $offers) {}

    public function linksFor(array $glassModelIds): array
    {
        if ($glassModelIds === [] || ! CurrentActor::can(OffersPermission::Create)) {
            return [];
        }

        $counts = $this->offers->countByGlassModel(
            $this->offers->outsideTrash($this->offers->visibleTo(CurrentActor::get()->id, CurrentActor::can(OffersPermission::ManageAll))),
            $glassModelIds,
        );

        $links = [];

        foreach ($counts as $glassModelId => $count) {
            $links[$glassModelId] = new GlassModelCardLink(
                label: trans_choice('offers::resources.offer.glass_model_link', $count),
                url: OfferResource::getUrl('index', ['filters' => ['glass_model' => ['glass_model_id' => $glassModelId]]]),
            );
        }

        return $links;
    }
}
