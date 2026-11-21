<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOfferPermanently;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Domain\Repositories\OfferPhotoStorage;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-26: from the Trash only, the Offer, its Reservations, Sales and Inquiries and its photo files are removed.
 */
final class DeleteOfferPermanentlyCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferPhotoStorage $photoStorage,
        private ReservationRepository $reservations,
        private SaleRepository $sales,
        private InquiryRepository $inquiries,
    ) {}

    #[CommandHandler]
    public function handle(DeleteOfferPermanentlyCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $offer = $this->offers->get($command->offerId);

        $offer->ensureCanBeDeletedPermanently();

        $this->reservations->removeAllOfOffer($command->offerId);
        $this->inquiries->removeAllOfOffer($command->offerId);
        $this->sales->removeAllOfOffer($command->offerId);
        $this->offers->remove($offer);
        $this->photoStorage->deleteAll($offer->photos());
    }
}
