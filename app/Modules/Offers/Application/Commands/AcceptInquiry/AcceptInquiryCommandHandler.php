<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\AcceptInquiry;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\InquirySenderJournal;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-22, WF-05, RULE-05, RULE-10: the Inquiry is accepted, the Offer reserved (the same `Offer::reserve` as any
 * reservation) and the Reservation recorded in the sending admin's name, locked for the seller and linked to the
 * Inquiry, in one step. The Inquiry is saved before the Offer so the reaction to the Offer leaving Active (FR-23)
 * finds it already answered and outdates only the others.
 */
final class AcceptInquiryCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private InquiryRepository $inquiries,
        private OfferRepository $offers,
        private ReservationRepository $reservations,
        private InquirySenderJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(AcceptInquiryCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $inquiry = $this->inquiries->get($command->inquiryId);
        $offer = $this->offers->get($inquiry->offerId());

        if ($offer->ownerId() !== $userId) {
            throw OfferRefused::notYours();
        }

        $now = CarbonImmutable::now();
        $inquiry->accept($now);
        $reservation = Reservation::make($offer->id() ?? 0, $inquiry->senderId(), true, $inquiry->note(), $now, $inquiry->id());

        $offer->reserve($this->reservations->findCurrentForOffer((int) $offer->id()) !== null);

        $this->inquiries->save($inquiry);
        $this->offers->save($offer);
        $this->reservations->add($reservation);

        $this->journal->accepted($command->actor, $inquiry, $offer);
    }
}
