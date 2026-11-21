<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\AnnounceBuyersPriceChanges;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\BuyersPriceAnnouncer;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use Ecotone\Modelling\Attribute\CommandHandler;

final class AnnounceBuyersPriceChangesCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private VatRateSetting $setting,
        private BuyersPriceAnnouncer $announcer,
    ) {}

    #[CommandHandler]
    public function handle(AnnounceBuyersPriceChangesCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ManageAll);

        $previous = OwnerAccountType::tryFrom($command->previousAccountType);
        $current = OwnerAccountType::tryFrom($command->accountType);

        if ($previous === null || $current === null) {
            return;
        }

        $this->announcer->afterAccountTypeChange(
            $this->offers->pricedOffersOfOwner($command->ownerId),
            $previous,
            $current,
            $this->setting->current(),
        );
    }
}
