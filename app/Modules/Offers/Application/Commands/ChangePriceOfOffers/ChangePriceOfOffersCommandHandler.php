<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangePriceOfOffers;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\PriceChange;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-29, RULE-14, D-31, D-53: changes the price of each selected Offer the actor sees (a seller's own, an admin's
 * any). Reserved, Sold, trashed and unpriced Offers and prices that would not stay above zero are skipped. The
 * status never changes and nothing goes to approval; an admin's change of another's Offer goes to its owner's Journal.
 */
final class ChangePriceOfOffersCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    /** @return array{done: int, skipped: int} */
    #[CommandHandler]
    public function handle(ChangePriceOfOffersCommand $command): array
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;
        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        $change = PriceChange::from($command->kind, $command->value);

        $done = 0;
        $skipped = 0;

        foreach (array_unique($command->offerIds) as $offerId) {
            $offer = $this->offers->find($offerId);

            if ($offer === null || ! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
                $skipped++;

                continue;
            }

            try {
                $changed = $offer->changePrice($change);
            } catch (OfferRefused) {
                $skipped++;

                continue;
            }

            if (! $changed) {
                $skipped++;

                continue;
            }

            $this->offers->save($offer);
            $this->journal->changedByAdmin($command->actor, $offer);
            $done++;
        }

        return ['done' => $done, 'skipped' => $skipped];
    }
}
