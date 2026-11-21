<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\OutdateInquiriesOfOffer;

use App\Modules\Offers\Application\Services\InquirySenderJournal;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-23, FR-34, D-41: every open Inquiry of the Offer becomes Outdated and its sender is told (not when the sender
 * is the one acting). Nobody sends this by hand: the reaction to `OfferStoppedBeingActive` does, with the Actor
 * that already passed authorization in the command that changed the Offer, so the handler checks no permission.
 */
final class OutdateInquiriesOfOfferCommandHandler
{
    public function __construct(
        private InquiryRepository $inquiries,
        private OfferRepository $offers,
        private InquirySenderJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(OutdateInquiriesOfOfferCommand $command): void
    {
        $open = $this->inquiries->openOfOffer($command->offerId);

        if ($open === []) {
            return;
        }

        $offer = $this->offers->get($command->offerId);

        foreach ($open as $inquiry) {
            $inquiry->outdate();
            $this->inquiries->save($inquiry);

            $this->journal->outdated($command->actor, $inquiry, $offer);
        }
    }
}
