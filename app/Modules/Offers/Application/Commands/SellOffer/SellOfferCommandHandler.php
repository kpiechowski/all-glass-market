<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Commands\SellOffer;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Services\OwnerJournal;
use App\Modules\Offers\Domain\Entities\Sale;
use App\Modules\Offers\Domain\Enums\OffersPermission;
use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\BuyersPricePolicy;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Users\Application\ModuleApi\AccountTypes;
use Carbon\CarbonImmutable;
use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * FR-18, FR-34, RULE-05, RULE-09, RULE-10: the Offer becomes Sold, a current Reservation ends as sold and the
 * Sale is recorded, in one step. The Sale keeps the entered price, the VAT rate now in force and the buyer's price
 * (RULE-08, by the owner's current Account type) as they are at this moment.
 */
final class SellOfferCommandHandler
{
    public function __construct(
        private Authorizer $authorizer,
        private OfferRepository $offers,
        private ReservationRepository $reservations,
        private SaleRepository $sales,
        private VatRateSetting $vatRate,
        private AccountTypes $accountTypes,
        private OwnerJournal $journal,
    ) {}

    #[CommandHandler]
    public function handle(SellOfferCommand $command): void
    {
        $this->authorizer->ensure($command->actor, OffersPermission::Create);
        $userId = $command->actor->id ?? throw new AuthorizationException;

        $channel = SaleChannel::tryFrom($command->channel) ?? throw OfferRefused::saleChannelIsUnknown();

        $offer = $this->offers->get($command->offerId);
        $managesAll = $this->authorizer->can($command->actor, OffersPermission::ManageAll);

        if (! OfferDomainPolicy::canBeSeenBy($offer->ownerId(), $userId, $managesAll)) {
            throw OfferRefused::notYours();
        }

        $reservation = $this->reservations->findCurrentForOffer($command->offerId);
        $reservation?->guardCanBeHandledBy($managesAll);

        $offer->sell();

        $now = CarbonImmutable::now();
        $enteredPrice = $offer->content()->price ?? throw OfferRefused::priceRequiredToSell();
        $rate = $this->vatRate->current();
        $buyersPrice = BuyersPricePolicy::buyersPriceGrosze(
            $enteredPrice->grosze,
            OwnerAccountType::from((string) $this->accountTypes->of($offer->ownerId())),
            $rate,
        );

        $sale = Sale::record($command->offerId, $channel, $command->salePriceGrosze, $enteredPrice, $rate, $buyersPrice, $userId, $now);
        $reservation?->end(ReservationEnd::Sold, $now);

        $this->offers->save($offer);

        if ($reservation !== null) {
            $this->reservations->save($reservation);
        }

        $this->sales->add($sale);

        $this->journal->sold($command->actor, $offer);
    }
}
