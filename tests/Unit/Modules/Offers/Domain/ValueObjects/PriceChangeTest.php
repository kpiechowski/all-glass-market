<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\ValueObjects;

use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\PriceChange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** FR-29: amount ± zł, percent ± %, or a fixed price of at least 1 zł; a result of 0 or less gives no price. */
class PriceChangeTest extends TestCase
{
    /** @return array<string, array{0: string, 1: int, 2: int, 3: ?int}> kind, value, price in grosze, expected grosze */
    public static function changes(): array
    {
        return [
            'amount up' => ['amount', 500, 10000, 10500],
            'amount down' => ['amount', -500, 10000, 9500],
            'amount down to zero' => ['amount', -10000, 10000, null],
            'amount below zero' => ['amount', -10001, 10000, null],
            'percent up (basis points)' => ['percent', 1000, 10000, 11000],
            'percent down' => ['percent', -2550, 10000, 7450],
            'percent rounds to grosze' => ['percent', 333, 10001, 10334],
            'percent 50 down on 1 zł' => ['percent', -5000, 100, 50],
            'percent 100 down' => ['percent', -10000, 10000, null],
            'fixed' => ['fixed', 25000, 10000, 25000],
        ];
    }

    #[DataProvider('changes')]
    public function test_it_applies_to_a_price(string $kind, int $value, int $grosze, ?int $expected): void
    {
        $result = PriceChange::from($kind, $value)->applyTo(EnteredPrice::fromGrosze($grosze));

        $this->assertSame($expected, $result?->grosze);
    }

    public function test_a_fixed_price_below_one_zloty_is_refused(): void
    {
        $this->expectException(OfferRefused::class);

        PriceChange::from('fixed', 99);
    }

    public function test_an_unknown_kind_is_refused(): void
    {
        $this->expectException(OfferRefused::class);

        PriceChange::from('double', 2);
    }
}
