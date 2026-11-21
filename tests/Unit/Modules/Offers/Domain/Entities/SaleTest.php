<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Offers\Domain\Entities;

use App\Modules\Offers\Domain\Entities\Sale;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/** FR-18, FR-19, RULE-09. */
class SaleTest extends TestCase
{
    private function now(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-11-21 10:00:00');
    }

    private function sale(int $salePrice = 18000): Sale
    {
        return Sale::record(4, SaleChannel::Olx, $salePrice, EnteredPrice::fromGrosze(15000), VatRate::initial(), 18450, 11, $this->now());
    }

    private function refusalKey(callable $action): string
    {
        try {
            $action();
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_a_sale_records_the_channel_the_prices_the_rate_and_who_marked_it(): void
    {
        $sale = $this->sale();

        $this->assertNull($sale->id());
        $this->assertSame(4, $sale->offerId());
        $this->assertSame(SaleChannel::Olx, $sale->channel());
        $this->assertSame(18000, $sale->salePriceGrosze());
        $this->assertSame(15000, $sale->enteredPrice()->grosze);
        $this->assertSame(2300, $sale->vatRate()->basisPoints);
        $this->assertSame(18450, $sale->buyersPriceGrosze());
        $this->assertSame(11, $sale->markedById());
        $this->assertEquals($this->now(), $sale->soldAt());
        $this->assertFalse($sale->isReturned());
    }

    public function test_the_sale_price_must_be_positive(): void
    {
        $this->assertSame('offers::exceptions.sale_price_is_invalid', $this->refusalKey(fn () => $this->sale(0)));
    }

    public function test_a_return_needs_a_reason_keeps_the_sale_and_happens_once(): void
    {
        $sale = $this->sale();

        $this->assertSame('offers::exceptions.return_reason_required', $this->refusalKey(fn () => $sale->markReturned('  ', 2, $this->now())));

        $sale->markReturned(' Klient zrezygnował ', 2, $this->now()->addDay());

        $this->assertTrue($sale->isReturned());
        $this->assertSame('Klient zrezygnował', $sale->returnReason());
        $this->assertSame(2, $sale->returnedById());
        $this->assertEquals($this->now()->addDay(), $sale->returnedAt());
        $this->assertSame(18000, $sale->salePriceGrosze());
        $this->assertSame('offers::exceptions.sale_already_returned', $this->refusalKey(fn () => $sale->markReturned('Jeszcze raz', 2, $this->now())));
    }
}
