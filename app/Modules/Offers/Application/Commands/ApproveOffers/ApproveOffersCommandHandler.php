<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ApproveOffers;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-11: approves the selected Pending and Rejected Offers that have a Glass model, skips the rest and counts both. */
final class ApproveOffersCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    /** @return array{approved: int, skipped: int} */
    #[CommandHandler]
    public function handle(ApproveOffersCommand $command): array
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $approved = 0;
        $skipped = 0;
        $now = CarbonImmutable::now();

        foreach (array_unique($command->offerIds) as $offerId) {
            $offer = $this->offers->find($offerId);

            if ($offer === null || ! OfferDomainPolicy::canBeApproved($offer->status(), $offer->content()->glass->glassModelId !== null)) {
                $skipped++;

                continue;
            }

            $offer->approve($now);
            $this->offers->save($offer);
            $this->journal->approved($command->actor, $offer);
            $approved++;
        }

        return ['approved' => $approved, 'skipped' => $skipped];
    }
}
