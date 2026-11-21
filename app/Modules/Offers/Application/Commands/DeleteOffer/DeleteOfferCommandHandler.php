<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOffer;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-25, FR-34: only an admin deletes; the Offer goes to the Trash with its status and the owner is told. */
final class DeleteOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(DeleteOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $offer = $this->offers->get($command->offerId);

        $offer->moveToTrash(CarbonImmutable::now());
        $this->offers->save($offer);

        $this->journal->deleted($command->actor, $offer);
    }
}
