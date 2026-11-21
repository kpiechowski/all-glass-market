<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use PHPUnit\Framework\TestCase;

class VatRateTest extends TestCase
{
    public function test_it_holds_basis_points(): void
    {
        $this->assertSame(2300, VatRate::fromBasisPoints(2300)->basisPoints);
        $this->assertSame(0, VatRate::fromBasisPoints(0)->basisPoints);
        $this->assertTrue(VatRate::fromBasisPoints(2300)->equals(VatRate::fromBasisPoints(2300)));
    }

    public function test_the_initial_rate_is_twenty_three_percent(): void
    {
        $this->assertSame(2300, VatRate::initial()->basisPoints);
    }

    public function test_a_negative_rate_or_one_above_a_hundred_percent_is_refused(): void
    {
        try {
            VatRate::fromBasisPoints(-1);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.vat_rate_is_invalid', $refusal->translationKey);
        }
    }

    public function test_a_rate_above_a_hundred_percent_is_refused(): void
    {
        try {
            VatRate::fromBasisPoints(10001);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.vat_rate_is_invalid', $refusal->translationKey);
        }
    }
}
