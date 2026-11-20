<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SendOfferForApproval;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/** §7 Draft/Rejected to Pending, or to Active for an admin's own Offer with a Glass model (FR-13). Only the owner sends. */
final class SendOfferForApprovalCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
    ) {}

    #[CommandHandler]
    public function handle(SendOfferForApprovalCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $offer = $this->offers->get($command->offerId);

        if ($offer->ownerId() !== $userId) {
            throw OfferRefused::notYours();
        }

        $offer->send($this->authorizer->can($command->actor, OffersPermission::ManageAll), CarbonImmutable::now());
        $this->offers->save($offer);
    }
}
