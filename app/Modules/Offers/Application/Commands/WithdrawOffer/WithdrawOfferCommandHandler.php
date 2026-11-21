<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\WithdrawOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-24, FR-34: the owner or an admin withdraws the Offer; the owner is told when an admin did. */
final class WithdrawOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(WithdrawOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $this->authorizer->can($command->actor, OffersPermission::ManageAll))) {
            throw OfferRefused::notYours();
        }

        $offer->withdraw();
        $this->offers->save($offer);

        $this->journal->withdrawn($command->actor, $offer);
    }
}
