<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\CatalogueDictionaries;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelSummaries;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;

/**
 * §10, RULE-12: the offer summary for adverts, read by integrations after an announcement. It answers only for a
 * candidate (Active or Reserved, outside the Trash); for anything else the channel takes the advert down.
 */
final class OfferAdvertSummaries
{
    public function __construct(
        private OfferRepository $offers,
        private GlassModelSummaries $glassModels,
        private CatalogueDictionaries $dictionaries,
        private BuyersPrices $buyersPrices,
    ) {}

    public function of(int $offerId): ?OfferAdvertSummary
    {
        $offer = $this->offers->find($offerId);

        if (! $offer instanceof Offer || ! $this->isCandidate($offer)) {
            return null;
        }

        $content = $offer->content();

        return new OfferAdvertSummary(
            offerId: $offerId,
            number: $offer->number()->value,
            glassModel: $content->glass->glassModelId === null ? null : $this->glassModels->of($content->glass->glassModelId),
            producerId: $content->producerId,
            producer: $content->producerId === null ? null : ($this->dictionaries->namesOf([$content->producerId])[$content->producerId] ?? null),
            year: $content->year,
            condition: $content->condition?->value,
            rating: $content->rating?->stars,
            showsFiveStars: $content->condition === OfferCondition::New,
            description: $content->description,
            publicPhotos: $offer->photos()->public,
            buyersPriceGrosze: $this->buyersPrices->forOffer($offer->ownerId(), $content->price?->grosze),
            reserved: $offer->status() === OfferStatus::Reserved,
        );
    }

    private function isCandidate(Offer $offer): bool
    {
        return OfferDomainPolicy::isCandidate($offer->status(), $offer->isInTrash());
    }
}
