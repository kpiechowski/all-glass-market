<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Modules\Catalogue\Domain\Events\ProducerChanged;
use App\Modules\Catalogue\Domain\Events\ProducerWithdrawn;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Domain\Repositories\OfferRepository;

/** §9, §10: a producer changed or was withdrawn in Catalogue; adverts of its candidate Offers refresh. */
final class AnnounceCandidatesWhenProducerChanges
{
    public function __construct(private OfferRepository $offers) {}

    public function handle(ProducerChanged|ProducerWithdrawn $event): void
    {
        foreach ($this->offers->candidateIdsOfProducer($event->producerId) as $offerId) {
            OfferChangedWhileCandidate::dispatch($offerId);
        }
    }
}
