<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Entities\Reservation;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Repositories\ReservationRepository;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RULE-05: one current Reservation per Offer, backed by the database. */
class EloquentReservationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): ReservationRepository
    {
        return app(ReservationRepository::class);
    }

    public function test_it_finds_the_current_reservation_of_the_offer_and_not_an_ended_one_or_another_offers(): void
    {
        $offer = OfferModelFactory::new()->create();
        $other = OfferModelFactory::new()->create();
        ReservationModelFactory::new()->forOffer($offer->id)->ended(ReservationEnd::Released)->create();
        $current = ReservationModelFactory::new()->forOffer($offer->id)->create();
        ReservationModelFactory::new()->forOffer($other->id)->create();

        $this->assertSame($current->id, $this->repository()->findCurrentForOffer($offer->id)?->id());
    }

    public function test_it_finds_nothing_when_every_reservation_ended(): void
    {
        $offer = OfferModelFactory::new()->create();
        ReservationModelFactory::new()->forOffer($offer->id)->ended(ReservationEnd::Sold)->create();

        $this->assertNull($this->repository()->findCurrentForOffer($offer->id));
    }

    public function test_an_ended_reservation_stays_after_a_new_one_is_made(): void
    {
        $offer = OfferModelFactory::new()->create();
        $author = UserModel::factory()->create();
        ReservationModelFactory::new()->forOffer($offer->id)->ended(ReservationEnd::Released)->create();

        app(ActorStack::class)->run(Actor::user($author->id), fn () => $this->repository()->add(
            Reservation::make($offer->id, $author->id, false, 'Nowy', CarbonImmutable::now()),
        ));

        $this->assertSame(2, ReservationModel::query()->count());
    }

    public function test_the_database_refuses_a_second_current_reservation(): void
    {
        $offer = OfferModelFactory::new()->create();
        ReservationModelFactory::new()->forOffer($offer->id)->create();

        $this->expectException(QueryException::class);

        ReservationModelFactory::new()->forOffer($offer->id)->create();
    }

    public function test_it_removes_every_reservation_of_the_offer_only(): void
    {
        $offer = OfferModelFactory::new()->create();
        $other = OfferModelFactory::new()->create();
        ReservationModelFactory::new()->forOffer($offer->id)->ended(ReservationEnd::Released)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->create();
        $kept = ReservationModelFactory::new()->forOffer($other->id)->create();

        app(ActorStack::class)->run(Actor::user(1), fn () => $this->repository()->removeAllOfOffer($offer->id));

        $this->assertSame([$kept->id], ReservationModel::query()->pluck('id')->all());
    }
}
