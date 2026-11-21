<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RestoreOffer;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-25: an admin restores an Offer from the Trash; it returns with the status it had. */
final class RestoreOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
    ) {}

    #[CommandHandler]
    public function handle(RestoreOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $offer = $this->offers->get($command->offerId);

        $offer->restoreFromTrash();
        $this->offers->save($offer);
    }
}
