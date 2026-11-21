<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Queries\ReservationQueries;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seller_gets_the_current_reservations_of_their_own_offers_only(): void
    {
        $seller = UserModel::factory()->create();
        $own = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $ownEnded = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $ownTrashed = OfferModelFactory::new()->ownedBy($seller->id)->inTrash()->create();
        $foreign = OfferModelFactory::new()->create();
        $current = ReservationModelFactory::new()->forOffer($own->id)->create();
        ReservationModelFactory::new()->forOffer($ownEnded->id)->ended(ReservationEnd::Sold)->create();
        ReservationModelFactory::new()->forOffer($ownTrashed->id)->create();
        ReservationModelFactory::new()->forOffer($foreign->id)->create();

        $ids = (new ReservationQueries)->currentVisibleTo($seller->id, managesAll: false)->pluck('id')->all();

        $this->assertSame([$current->id], $ids);
    }

    public function test_whoever_manages_all_offers_gets_every_current_reservation_outside_the_trash(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $one = ReservationModelFactory::new()->create();
        $two = ReservationModelFactory::new()->create();
        ReservationModelFactory::new()->forOffer(OfferModelFactory::new()->inTrash()->create()->id)->create();

        $ids = (new ReservationQueries)->currentVisibleTo($admin->id, managesAll: true)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$one->id, $two->id], $ids);
    }
}
