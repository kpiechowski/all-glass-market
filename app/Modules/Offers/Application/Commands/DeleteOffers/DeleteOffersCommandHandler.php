<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DeleteOffers;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-25, RULE-14: moves each selected Offer to the Trash; Reserved ones and those already in it are skipped. */
final class DeleteOffersCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    /** @return array{done: int, skipped: int} */
    #[CommandHandler]
    public function handle(DeleteOffersCommand $command): array
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $done = 0;
        $skipped = 0;
        $now = CarbonImmutable::now();

        foreach (array_unique($command->offerIds) as $offerId) {
            $offer = $this->offers->find($offerId);

            if ($offer === null) {
                $skipped++;

                continue;
            }

            try {
                $offer->moveToTrash($now);
            } catch (OfferRefused) {
                $skipped++;

                continue;
            }

            $this->offers->save($offer);
            $this->journal->deleted($command->actor, $offer);
            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }
}
