<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\DuplicateOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OfferContentAssembler;
use App\Modules\Offers\Application\Services\OfferNumberGenerator;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-27, RULE-07: the copy belongs to whoever duplicates, an admin included. */
final class DuplicateOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private OfferContentAssembler $contents,
        private OfferNumberGenerator $numbers,
    ) {}

    #[CommandHandler]
    public function handle(DuplicateOfferCommand $command): int
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $source = $this->offers->get($command->offerId);

        if (! OfferDomainPolicy::canBeSeenBy($source->ownerId(), $userId, $this->authorizer->can($command->actor, OffersPermission::ManageAll))) {
            throw OfferRefused::notYours();
        }

        $copy = $source->duplicate(
            $this->numbers->next(),
            $userId,
            $this->contents->glassModelCanBePicked($source->content()),
        );

        return $this->offers->add($copy);
    }
}
