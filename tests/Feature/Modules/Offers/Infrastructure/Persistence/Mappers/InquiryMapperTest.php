<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\InquiryMapper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_answered_inquiry_survives_the_round_trip(): void
    {
        $mapper = new InquiryMapper;
        $entity = Inquiry::reconstitute(5, 3, 9, SaleChannel::Olx, 'Pan Kowalski', InquiryState::Accepted, CarbonImmutable::parse('2026-11-21 10:00:00'), CarbonImmutable::parse('2026-11-21 11:00:00'), CarbonImmutable::parse('2026-11-21 12:00:00'));

        $model = InquiryModelFactory::new()->make(['id' => 5] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_a_sent_inquiry_without_a_channel_survives_the_round_trip(): void
    {
        $mapper = new InquiryMapper;
        $entity = Inquiry::reconstitute(5, 3, 9, null, 'Pan Kowalski', InquiryState::Sent, CarbonImmutable::parse('2026-11-21 10:00:00'), null, null);

        $model = InquiryModelFactory::new()->make(['id' => 5] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }
}
