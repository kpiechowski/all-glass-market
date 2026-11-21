<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ResumeOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-24, FR-13: the owner or an admin resumes a Withdrawn Offer. Whether the Offer's owner is an admin is
 * the fact the entity needs; the entity decides whether the Offer may skip approval.
 */
final class ResumeOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
    ) {}

    #[CommandHandler]
    public function handle(ResumeOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);

        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
            throw OfferRefused::notYours();
        }

        $ownerIsAdmin = $this->authorizer->can(Actor::user($offer->ownerId()), OffersPermission::ManageAll);

        $offer->resume($ownerIsAdmin, CarbonImmutable::now());
        $this->offers->save($offer);
    }
}
