<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;

/** The price the owner types, in grosze. A Draft may hold any price from 0; sending for approval asks for 1 zł (FR-06, later). */
final readonly class EnteredPrice
{
    private function __construct(public int $grosze) {}

    public static function fromGrosze(int $grosze): self
    {
        if ($grosze < 0) {
            throw OfferRefused::priceIsInvalid();
        }

        return new self($grosze);
    }
}
