<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;

/** FR-30: the VAT rate in basis points (2300 = 23%), so every price calculation stays in integers. */
final readonly class VatRate
{
    private const int INITIAL_BASIS_POINTS = 2300;

    private function __construct(public int $basisPoints) {}

    public static function fromBasisPoints(int $basisPoints): self
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw OfferRefused::vatRateIsInvalid();
        }

        return new self($basisPoints);
    }

    /** D-54: the rate before a Super Admin sets one. */
    public static function initial(): self
    {
        return new self(self::INITIAL_BASIS_POINTS);
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}
