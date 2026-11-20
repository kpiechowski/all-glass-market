<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\CreateOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Application\Services\OfferNumberGenerator;
use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use Ecotone\Modelling\Attribute\CommandHandler;

/** WF-01 steps 1, 2 and 5: the creator becomes the owner, the Draft gets its number. */
final class CreateOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
        private OfferNumberGenerator $numbers,
    ) {}

    #[CommandHandler]
    public function handle(CreateOfferCommand $command): int
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $ownerId = $command->actor->id ?? throw new AuthorizationException;

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

        $offer = Offer::draft(
            $this->numbers->next(),
            $ownerId,
            $content,
            $this->contents->glassModelCanBePicked($content),
            $this->contents->producerCanBePicked($content),
        );

        $offer->arrangePhotos(OfferPhotos::from($command->publicPhotos, $command->privatePhotos), $ownerId);

        return $this->offers->add($offer);
    }
}
