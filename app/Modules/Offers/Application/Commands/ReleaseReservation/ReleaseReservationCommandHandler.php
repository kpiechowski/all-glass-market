<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReleaseReservation;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-17, FR-34, RULE-05, RULE-10: the Offer is Active again and the Reservation ends released, in one step. */
final class ReleaseReservationCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private ReservationRepository $reservations,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(ReleaseReservationCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);
        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
            throw OfferRefused::notYours();
        }

        $offer->release();

        $reservation = $this->reservations->findCurrentForOffer($command->offerId) ?? throw OfferRefused::cannotBeReleased();
        $reservation->guardCanBeHandledBy($managesAll);
        $reservation->end(ReservationEnd::Released, CarbonImmutable::now());

        $this->offers->save($offer);
        $this->reservations->save($reservation);

        $this->journal->released($command->actor, $offer);
    }
}
