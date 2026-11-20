<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\RejectOffer;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-09: Pending or Active to Rejected; the owner's Journal entry carries the reason. */
final class RejectOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(RejectOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $offer = $this->offers->get($command->offerId);

        $offer->reject($command->reason);
        $this->offers->save($offer);

        $this->journal->rejected($command->actor, $offer, trim($command->reason));
    }
}
