<?php

declare(strict_types=1);

namespace App\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Enums\PriceChangeKind;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Policies\OfferDomainPolicy;

/**
 * FR-29, D-53: a change of the entered price. The value is in grosze for an amount (signed) and a fixed
 * price (at least 1 zł), and in hundredths of a percent for a percent change (signed).
 */
final readonly class PriceChange
{
    private function __construct(
        private PriceChangeKind $kind,
        private int $value,
    ) {}

    public static function from(string $kind, int $value): self
    {
        $kind = PriceChangeKind::tryFrom($kind) ?? throw OfferRefused::priceChangeIsInvalid();

        if ($kind === PriceChangeKind::Fixed && $value < OfferDomainPolicy::MIN_SENT_PRICE_GROSZE) {
            throw OfferRefused::priceChangeIsInvalid();
        }

        return new self($kind, $value);
    }

    /** The new price, or null when it would not be above zero. */
    public function applyTo(EnteredPrice $price): ?EnteredPrice
    {
        $grosze = match ($this->kind) {
            PriceChangeKind::Amount => $price->grosze + $this->value,
            PriceChangeKind::Percent => (int) round($price->grosze * (10000 + $this->value) / 10000),
            PriceChangeKind::Fixed => $this->value,
        };

        return $grosze > 0 ? EnteredPrice::fromGrosze($grosze) : null;
    }
}
