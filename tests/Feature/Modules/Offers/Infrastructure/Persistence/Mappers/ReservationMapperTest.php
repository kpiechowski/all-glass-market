<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\ReservationMapper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_current_reservation_survives_the_round_trip_and_is_marked_current(): void
    {
        $mapper = new ReservationMapper;
        $entity = Reservation::reconstitute(5, 3, 9, true, 'Pan Kowalski', CarbonImmutable::parse('2026-11-21 10:00:00'), null, null);

        $row = $mapper->toRow($entity);
        $model = ReservationModelFactory::new()->make(['id' => 5] + $row);

        $this->assertTrue($row['is_current']);
        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_an_ended_reservation_survives_the_round_trip_and_is_no_longer_current(): void
    {
        $mapper = new ReservationMapper;
        $entity = Reservation::reconstitute(5, 3, 9, false, 'Pan Kowalski', CarbonImmutable::parse('2026-11-21 10:00:00'), ReservationEnd::Released, CarbonImmutable::parse('2026-11-22 08:30:00'));

        $row = $mapper->toRow($entity);
        $model = ReservationModelFactory::new()->make(['id' => 5] + $row);

        $this->assertNull($row['is_current']);
        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_the_inquiry_a_reservation_came_from_survives_the_round_trip(): void
    {
        $mapper = new ReservationMapper;
        $entity = Reservation::reconstitute(5, 3, 9, true, 'Pan Kowalski', CarbonImmutable::parse('2026-11-21 10:00:00'), null, null, 12);

        $row = $mapper->toRow($entity);
        $model = ReservationModelFactory::new()->make(['id' => 5] + $row);

        $this->assertSame(12, $row['inquiry_id']);
        $this->assertEquals($entity, $mapper->toEntity($model));
    }
}
