<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\ChangeVatRate;

use App\Core\Contracts\Authorizer;
use App\Modules\Offers\Application\Services\BuyersPriceAnnouncer;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Users\Application\ModuleApi\AccountTypes;
use Ecotone\Modelling\Attribute\CommandHandler;

/** FR-28, FR-30: saves the rate and announces every Offer whose buyer's price moved with it. */
final class ChangeVatRateCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private VatRateSetting $setting,
        private OfferRepository $offers,
        private AccountTypes $accountTypes,
        private BuyersPriceAnnouncer $announcer,
    ) {}

    #[CommandHandler]
    public function handle(ChangeVatRateCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::ChangeVatRate);

        $rate = VatRate::fromBasisPoints($command->basisPoints);
        $previous = $this->setting->current();

        if ($rate->equals($previous)) {
            return;
        }

        $this->setting->change($rate);

        $priced = $this->offers->pricedOffers();
        $types = $this->accountTypes->ofMany(array_values(array_unique(array_column($priced, 'ownerId'))));

        $this->announcer->afterVatRateChange($priced, $types, $previous, $rate);
    }
}
