<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Modules\Offers\Domain\Entities\Sale;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\SaleMapper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleMapperTest extends TestCase
{
    use RefreshDatabase;

    private function sale(?CarbonImmutable $returnedAt, ?string $reason, ?int $returnedBy): Sale
    {
        return Sale::reconstitute(
            6,
            3,
            SaleChannel::WooCommerce,
            17000,
            EnteredPrice::fromGrosze(15000),
            VatRate::fromBasisPoints(2300),
            18450,
            9,
            CarbonImmutable::parse('2026-11-21 10:00:00'),
            $returnedAt,
            $reason,
            $returnedBy,
        );
    }

    public function test_a_sale_survives_the_round_trip(): void
    {
        $mapper = new SaleMapper;
        $entity = $this->sale(null, null, null);

        $model = SaleModelFactory::new()->make(['id' => 6] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_a_returned_sale_survives_the_round_trip(): void
    {
        $mapper = new SaleMapper;
        $entity = $this->sale(CarbonImmutable::parse('2026-11-22 08:30:00'), 'Klient zrezygnował', 2);

        $model = SaleModelFactory::new()->make(['id' => 6] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }
}
