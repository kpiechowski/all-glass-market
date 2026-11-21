<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Mappers;

use App\Modules\Offers\Domain\Entities\Offer;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\ValueObjects\EnteredPrice;
use App\Modules\Offers\Domain\ValueObjects\GlassReference;
use App\Modules\Offers\Domain\ValueObjects\OfferContent;
use App\Modules\Offers\Domain\ValueObjects\OfferNumber;
use App\Modules\Offers\Domain\ValueObjects\OfferPhotos;
use App\Modules\Offers\Domain\ValueObjects\Rating;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Mappers\OfferMapper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferMapperTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_entity_with_a_glass_model_survives_the_round_trip(): void
    {
        $mapper = new OfferMapper;
        $content = OfferContent::from(GlassReference::from(4, null), 6, 2019, OfferCondition::Used, Rating::from(3), 'Opis', 'Notatka', EnteredPrice::fromGrosze(12050));
        $entity = Offer::reconstitute(7, OfferNumber::from('AG00000007'), 2, OfferStatus::Draft, $content, OfferPhotos::none());

        $model = OfferModelFactory::new()->make(['id' => 7] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_an_entity_with_only_a_hint_and_nothing_else_survives_the_round_trip(): void
    {
        $mapper = new OfferMapper;
        $content = OfferContent::from(GlassReference::from(null, 'VIN WVW123'), null, null, null, null, null, null, null);
        $entity = Offer::reconstitute(8, OfferNumber::from('AG00000008'), 2, OfferStatus::Draft, $content, OfferPhotos::from(['offers/public/2/b.jpg', 'offers/public/2/a.jpg'], ['offers/private/2/x.jpg']));

        $model = OfferModelFactory::new()->make(['id' => 8] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_the_status_and_both_approval_dates_survive_the_round_trip(): void
    {
        $mapper = new OfferMapper;
        $content = OfferContent::from(GlassReference::from(4, null), null, null, null, null, null, null, null);
        $entity = Offer::reconstitute(
            9,
            OfferNumber::from('AG00000009'),
            2,
            OfferStatus::Active,
            $content,
            OfferPhotos::none(),
            CarbonImmutable::parse('2026-01-02 10:00:00'),
            CarbonImmutable::parse('2026-03-04 12:30:00'),
        );

        $model = OfferModelFactory::new()->make(['id' => 9] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }

    public function test_withdrawal_and_trash_state_survive_the_round_trip(): void
    {
        $mapper = new OfferMapper;
        $content = OfferContent::from(GlassReference::from(4, null), null, null, null, null, null, null, null);
        $entity = Offer::reconstitute(
            10,
            OfferNumber::from('AG00000010'),
            2,
            OfferStatus::Withdrawn,
            $content,
            OfferPhotos::none(),
            CarbonImmutable::parse('2026-01-02 10:00:00'),
            CarbonImmutable::parse('2026-03-04 12:30:00'),
            true,
            CarbonImmutable::parse('2026-05-06 08:00:00'),
        );

        $model = OfferModelFactory::new()->make(['id' => 10] + $mapper->toRow($entity));

        $this->assertEquals($entity, $mapper->toEntity($model));
    }
}
