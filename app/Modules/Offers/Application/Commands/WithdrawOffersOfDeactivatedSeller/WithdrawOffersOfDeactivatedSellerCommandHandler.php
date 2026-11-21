<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller;

use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-35, FR-34: only the System runs this. Each Offer the entity lets go is withdrawn (the reason is the
 * System actor's, which Audit shows); the rest, the Trash included, stay as they are. The owner gets one
 * Journal entry for the lot, and none when nothing was withdrawn. Open Inquiries close through FR-23.
 */
final class WithdrawOffersOfDeactivatedSellerCommandHandler
{
    public function __construct(
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(WithdrawOffersOfDeactivatedSellerCommand $command): void
    {
        if (! $command->actor->isSystem()) {
            throw new AuthorizationException;
        }

        $withdrawn = 0;

        foreach ($this->offers->idsOfOwner($command->ownerId) as $offerId) {
            $offer = $this->offers->get($offerId);

            try {
                $offer->withdrawBecauseSellerDeactivated();
            } catch (OfferRefused) {
                continue;
            }

            $this->offers->save($offer);
            $withdrawn++;
        }

        if ($withdrawn > 0) {
            $this->journal->withdrawnBecauseSellerDeactivated($command->actor, $command->ownerId, $withdrawn);
        }
    }
}
