<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ReserveOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-16, FR-34, RULE-05, RULE-10: the Offer becomes Reserved and the Reservation is recorded in one step (the
 * command's transaction). Whether the author is an admin is kept on the Reservation, since it locks the owner out.
 */
final class ReserveOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private ReservationRepository $reservations,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(ReserveOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);
        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
            throw OfferRefused::notYours();
        }

        $now = CarbonImmutable::now();
        $reservation = Reservation::make($command->offerId, $userId, $managesAll, $command->note, $now);

        $offer->reserve($this->reservations->findCurrentForOffer($command->offerId) !== null);

        $this->offers->save($offer);
        $this->reservations->add($reservation);

        $this->journal->reserved($command->actor, $offer);
    }
}
