<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\UpdateOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-06 to FR-08, §3: the owner edits their Offer, an admin any Offer; the owner never changes. The entity
 * decides the status (re-approval for a seller, never for an admin); an admin's change of another's Offer
 * goes to the owner's Journal.
 */
final class UpdateOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(UpdateOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);

        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
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

        $changed = $offer->edit(
            $content,
            OfferPhotos::from(
                $command->publicPhotos ?? $offer->photos()->public,
                $command->privatePhotos ?? $offer->photos()->private,
            ),
            $userId,
            $managesAll,
            $this->contents->glassModelCanBePicked($content),
            $this->contents->producerCanBePicked($content),
        );
        $this->offers->save($offer);

        if ($managesAll && $changed) {
            $this->journal->changedByAdmin($command->actor, $offer);
        }
    }
}
