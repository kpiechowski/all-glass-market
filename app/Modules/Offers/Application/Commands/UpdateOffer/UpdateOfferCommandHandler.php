<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\UpdateOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-06, §3: the owner edits their Offer, an admin any Offer; the owner never changes and a Draft stays a Draft. */
final class UpdateOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
    ) {}

    #[CommandHandler]
    public function handle(UpdateOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $this->authorizer->can($command->actor, OffersPermission::ManageAll))) {
            throw OfferRefused::notYours();
        }

        $content = $this->contents->assemble(
            $command->glassModelId,
            $command->identificationHint,
            $command->producerId,
            $command->year,
            $command->condition,
            $command->rating,
            $command->description,
            $command->privateNotes,
            $command->priceGrosze,
        );

        $offer->revise(
            $content,
            $this->contents->glassModelCanBePicked($content),
            $this->contents->producerCanBePicked($content),
        );
        $this->offers->save($offer);
    }
}
