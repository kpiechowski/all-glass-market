<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Listeners;

use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Events\GlassModelRestored;
use App\Modules\Catalogue\Domain\Events\GlassModelWithdrawn;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Domain\Repositories\OfferRepository;

/** §9, §10: a Glass model changed, was withdrawn or restored in Catalogue; adverts of its candidate Offers refresh. */
final class AnnounceCandidatesWhenGlassModelChanges
{
    public function __construct(private OfferRepository $offers) {}

    public function handle(GlassModelChanged|GlassModelWithdrawn|GlassModelRestored $event): void
    {
        foreach ($this->offers->candidateIdsOfGlassModel($event->glassModelId) as $offerId) {
            OfferChangedWhileCandidate::dispatch($offerId);
        }
    }
}
