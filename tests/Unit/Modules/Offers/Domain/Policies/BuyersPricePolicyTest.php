<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Policies;

use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Policies\BuyersPricePolicy;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use PHPUnit\Framework\TestCase;

class BuyersPricePolicyTest extends TestCase
{
    public function test_a_company_owner_pays_the_entered_price(): void
    {
        $this->assertSame(10000, BuyersPricePolicy::buyersPriceGrosze(10000, OwnerAccountType::Company, VatRate::fromBasisPoints(2300)));
    }

    public function test_a_private_owner_adds_vat(): void
    {
        $this->assertSame(12300, BuyersPricePolicy::buyersPriceGrosze(10000, OwnerAccountType::Private, VatRate::fromBasisPoints(2300)));
    }

    public function test_the_price_is_rounded_to_grosze_half_up(): void
    {
        $rate = VatRate::fromBasisPoints(2300);

        $this->assertSame(1230, BuyersPricePolicy::buyersPriceGrosze(1000, OwnerAccountType::Private, $rate));
        // 99 * 1.23 = 121.77
        $this->assertSame(122, BuyersPricePolicy::buyersPriceGrosze(99, OwnerAccountType::Private, $rate));
        // 50 * 1.23 = 61.5 rounds up
        $this->assertSame(62, BuyersPricePolicy::buyersPriceGrosze(50, OwnerAccountType::Private, $rate));
        // 2 * 1.23 = 2.46 rounds down
        $this->assertSame(2, BuyersPricePolicy::buyersPriceGrosze(2, OwnerAccountType::Private, $rate));
    }

    public function test_a_rate_change_changes_a_private_owners_price_only(): void
    {
        $reduced = VatRate::fromBasisPoints(800);

        $this->assertSame(10800, BuyersPricePolicy::buyersPriceGrosze(10000, OwnerAccountType::Private, $reduced));
        $this->assertSame(10000, BuyersPricePolicy::buyersPriceGrosze(10000, OwnerAccountType::Company, $reduced));
    }

    public function test_a_fractional_rate_stays_exact_in_integer_arithmetic(): void
    {
        // 12.5% of 999 = 124.875 -> 1123.875 rounds to 1124
        $this->assertSame(1124, BuyersPricePolicy::buyersPriceGrosze(999, OwnerAccountType::Private, VatRate::fromBasisPoints(1250)));
    }
}
