<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\ValueObjects\VatRate;

/** RULE-08, the one place the buyer's price is calculated. It is derived, never stored. */
final class BuyersPricePolicy
{
    private const int BASIS = 10000;

    /** Entered price for a Company owner; entered price x (1 + VAT rate) rounded half up to grosze for a Private owner. */
    public static function buyersPriceGrosze(int $enteredPriceGrosze, OwnerAccountType $owner, VatRate $rate): int
    {
        if ($owner === OwnerAccountType::Company) {
            return $enteredPriceGrosze;
        }

        return intdiv($enteredPriceGrosze * (self::BASIS + $rate->basisPoints) * 2 + self::BASIS, self::BASIS * 2);
    }
}
