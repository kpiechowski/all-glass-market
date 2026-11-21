<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffers;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-24, RULE-14: withdraws each selected Offer the actor sees (a seller's own, an admin's any) under the
 * single action's rules; the rest are skipped. Each Offer leaving Active is noticed on save (FR-23).
 */
final class WithdrawOffersCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    /** @return array{done: int, skipped: int} */
    #[CommandHandler]
    public function handle(WithdrawOffersCommand $command): array
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;
        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        $done = 0;
        $skipped = 0;

        foreach (array_unique($command->offerIds) as $offerId) {
            $offer = $this->offers->find($offerId);

            if ($offer === null || ! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
                $skipped++;

                continue;
            }

            try {
                $offer->withdraw();
            } catch (OfferRefused) {
                $skipped++;

                continue;
            }

            $this->offers->save($offer);
            $this->journal->withdrawn($command->actor, $offer);
            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }
}
