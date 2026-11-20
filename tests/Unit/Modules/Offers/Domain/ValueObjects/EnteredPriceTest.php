<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use PHPUnit\Framework\TestCase;

/** The price the owner types, kept in grosze. */
class EnteredPriceTest extends TestCase
{
    public function test_a_price_is_kept_in_grosze(): void
    {
        $this->assertSame(12345, EnteredPrice::fromGrosze(12345)->grosze);
    }

    public function test_a_negative_price_is_refused(): void
    {
        $this->expectException(OfferRefused::class);

        EnteredPrice::fromGrosze(-1);
    }
}
