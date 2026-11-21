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
use UnexpectedValueException;

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

    public function test_a_row_that_cannot_become_a_valid_entity_is_refused_naming_the_record(): void
    {
        $model = SaleModelFactory::new()->make(['id' => 52, 'vat_rate_basis_points' => 20000]);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('#52');

        (new SaleMapper)->toEntity($model);
    }

    public function test_an_unreturned_sale_is_open_and_a_returned_one_is_not(): void
    {
        $mapper = new SaleMapper;

        $this->assertTrue($mapper->toRow($this->sale(null, null, null))['is_open']);
        $this->assertNull($mapper->toRow($this->sale(CarbonImmutable::parse('2026-11-22 08:30:00'), 'Powód', 2))['is_open']);
    }
}
