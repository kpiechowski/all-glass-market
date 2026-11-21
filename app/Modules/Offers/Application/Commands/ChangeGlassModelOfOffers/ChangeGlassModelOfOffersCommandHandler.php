<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-14, FR-34, RULE-14: replaces the Glass model of each selected Offer; the status stays and the owner gets a
 * Journal entry (not for the admin's own Offer). The picker's id is client state, so the model must exist and not
 * be withdrawn, or nothing changes. Offers in the Trash and those that already have the model are skipped.
 */
final class ChangeGlassModelOfOffersCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
        private OwnerJournal $journal,
    ) {}

    /** @return array{done: int, skipped: int} */
    #[CommandHandler]
    public function handle(ChangeGlassModelOfOffersCommand $command): array
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $canBePicked = $this->contents->glassModelIdCanBePicked($command->glassModelId);

        if (! $canBePicked) {
            throw OfferRefused::glassModelCannotBePicked();
        }

        $done = 0;
        $skipped = 0;

        foreach (array_unique($command->offerIds) as $offerId) {
            $offer = $this->offers->find($offerId);

            if ($offer === null) {
                $skipped++;

                continue;
            }

            try {
                $changed = $offer->changeGlassModel($command->glassModelId, $canBePicked);
            } catch (OfferRefused) {
                $skipped++;

                continue;
            }

            if (! $changed) {
                $skipped++;

                continue;
            }

            $this->offers->save($offer);
            $this->journal->glassModelChanged($command->actor, $offer);
            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }
}
