<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Reservations\Pages;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Reservations\Pages\ListReservations;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-32: "Rezerwacje" lists the current Reservations on own Offers with their authors. */
class ListReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_sees_the_current_reservations_of_their_own_offers_with_authors(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($owner);
        $mine = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        $foreign = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();
        $ended = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $reservation = ReservationModelFactory::new()->forOffer($mine->id)->madeBy($admin->id, true)->create();
        ReservationModelFactory::new()->forOffer($foreign->id)->create();
        ReservationModelFactory::new()->forOffer($ended->id)->ended(ReservationEnd::Released)->create();

        Livewire::test(ListReservations::class)
            ->assertCanSeeTableRecords([$reservation])
            ->assertCountTableRecords(1)
            ->assertSee($admin->name);
    }

    public function test_an_admin_sees_every_current_reservation(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $one = ReservationModelFactory::new()->create();
        $two = ReservationModelFactory::new()->create();

        Livewire::test(ListReservations::class)->assertCanSeeTableRecords([$one, $two]);
    }
}
