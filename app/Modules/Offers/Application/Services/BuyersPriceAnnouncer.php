<?php

declare(strict_types=1);

namespace App\Modules\Offers\Application\Services;

use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Domain\Policies\BuyersPricePolicy;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use Carbon\CarbonImmutable;

/** FR-28: raises "buyer's price changed" for each Offer whose derived price actually moved. */
final class BuyersPriceAnnouncer
{
    /**
     * An owner changed Account type: the rate stays, the type differs.
     *
     * @param  list<array{id: int, grosze: int}>  $offers
     */
    public function afterAccountTypeChange(array $offers, OwnerAccountType $previous, OwnerAccountType $current, VatRate $rate): void
    {
        foreach ($offers as $offer) {
            $this->announce(
                $offer['id'],
                BuyersPricePolicy::buyersPriceGrosze($offer['grosze'], $previous, $rate),
                BuyersPricePolicy::buyersPriceGrosze($offer['grosze'], $current, $rate),
            );
        }
    }

    /**
     * The VAT rate changed: each owner keeps the Account type.
     *
     * @param  list<array{id: int, ownerId: int, grosze: int}>  $offers
     * @param  array<int, string>  $accountTypes  Account type key by owner id
     */
    public function afterVatRateChange(array $offers, array $accountTypes, VatRate $previous, VatRate $current): void
    {
        foreach ($offers as $offer) {
            $owner = OwnerAccountType::tryFrom($accountTypes[$offer['ownerId']] ?? '');

            if ($owner === null) {
                continue;
            }

            $this->announce(
                $offer['id'],
                BuyersPricePolicy::buyersPriceGrosze($offer['grosze'], $owner, $previous),
                BuyersPricePolicy::buyersPriceGrosze($offer['grosze'], $owner, $current),
            );
        }
    }

    private function announce(int $offerId, int $before, int $after): void
    {
        if ($before !== $after) {
            event(new OfferBuyersPriceChanged($offerId, $before, $after, CarbonImmutable::now()));
        }
    }
}
