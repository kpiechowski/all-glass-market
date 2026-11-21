<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Domain\Repositories\OfferRepository;

/**
 * §10, FR-28: adverts follow a buyer's price change, but only of Offers that are candidates; the event is raised
 * for every priced Offer.
 */
final class AnnounceCandidatesWhenBuyersPriceChanges
{
    public function __construct(private OfferRepository $offers) {}

    public function handle(OfferBuyersPriceChanged $event): void
    {
        if ($this->offers->isCandidate($event->offerId)) {
            OfferChangedWhileCandidate::dispatch($event->offerId);
        }
    }
}
