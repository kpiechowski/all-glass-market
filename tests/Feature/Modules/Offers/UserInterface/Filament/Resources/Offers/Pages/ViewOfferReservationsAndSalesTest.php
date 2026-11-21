<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\OfferResource;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\EditOffer;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-16 to FR-20: reserve, release, sell and return, each landing through its command; the seller-vs-admin lock. */
class ViewOfferReservationsAndSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    private function openOffer(object $offer): Testable
    {
        return Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()]);
    }

    public function test_a_seller_reserves_releases_and_sells_their_own_offer(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->openOffer($offer)
            ->callAction('reserve', ['note' => 'Pan Kowalski'])
            ->assertNotified(__('offers::resources.offer.actions.reserve.done'));
        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $this->assertSame('Pan Kowalski', ReservationModel::query()->sole()->note);

        $this->openOffer($offer)->callAction('releaseReservation')->assertNotified(__('offers::resources.offer.actions.release.done'));
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);

        $this->openOffer($offer)
            ->callAction('sell', ['channel' => SaleChannel::Olx->value, 'price' => '170.00'])
            ->assertNotified(__('offers::resources.offer.actions.sell.done'));

        $sale = SaleModel::query()->sole();
        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        $this->assertSame(17000, $sale->sale_price_grosze);
        $this->assertSame(18450, $sale->buyers_price_grosze);
    }

    public function test_the_sell_form_is_prefilled_with_the_buyers_price_and_the_panel_channel(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->openOffer($offer)
            ->mountAction('sell')
            ->assertSchemaStateSet(['channel' => SaleChannel::Panel, 'price' => '184.50']);
    }

    public function test_an_admins_reservation_is_visible_to_the_seller_but_locked(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create(['note' => 'Telefon od Kowalskiego']);

        $this->openOffer($offer)
            ->assertSee('Telefon od Kowalskiego')
            ->assertSee($admin->name)
            ->assertSee(__('offers::resources.offer.reservation_locked'))
            ->assertActionHidden('releaseReservation')
            ->assertActionHidden('sell')
            ->assertActionHidden('withdraw')
            ->assertActionHidden('delete');
    }

    public function test_a_sellers_own_reservation_can_be_released_or_sold_by_the_seller_and_by_an_admin(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($owner->id, false)->create();

        $this->actingAs($owner);
        $this->openOffer($offer)->assertActionVisible('releaseReservation')->assertActionVisible('sell')->assertActionHidden('reserve');

        $this->actingAs(UserModel::factory()->admin()->create());
        $this->openOffer($offer)->assertActionVisible('releaseReservation')->assertActionVisible('sell');
    }

    public function test_an_admin_releases_and_sells_over_an_admins_reservation(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create();

        $this->openOffer($offer)->callAction('releaseReservation');
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);

        $this->openOffer($offer)->callAction('reserve', ['note' => 'Znowu'])->callAction('sell', ['channel' => 'panel', 'price' => '184.50']);
        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
    }

    public function test_a_seller_cannot_edit_a_reserved_or_sold_offer_and_an_admin_can(): void
    {
        $owner = UserModel::factory()->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();

        $this->actingAs($owner);
        $this->assertFalse(OfferResource::canEdit($reserved));
        $this->assertFalse(OfferResource::canEdit($sold));
        $this->openOffer($reserved)->assertActionHidden('edit');
        Livewire::test(EditOffer::class, ['record' => $reserved->getRouteKey()])->assertForbidden();

        $this->actingAs(UserModel::factory()->admin()->create());
        $this->assertTrue(OfferResource::canEdit($reserved));
        $this->assertTrue(OfferResource::canEdit($sold));
    }

    public function test_only_an_admin_returns_a_sale_and_the_sale_stays_listed_as_returned(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->actingAs($owner);
        $this->openOffer($offer)->assertActionHidden('returnSale')->assertSee(__('offers::resources.offer.sale_state.valid'));

        $this->actingAs($admin);
        $this->openOffer($offer)
            ->callAction('returnSale', ['reason' => 'Klient zrezygnował'])
            ->assertNotified(__('offers::resources.offer.actions.return_sale.done'));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->openOffer($offer)->assertSee(__('offers::resources.offer.sale_state.returned'))->assertSee('Klient zrezygnował');
    }

    public function test_a_seller_sees_the_sale_price_of_their_sale_but_not_the_recorded_vat_details(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create(['sale_price_grosze' => 17000, 'vat_rate_basis_points' => 2300]);

        $this->actingAs($owner);
        $this->openOffer($offer)->assertSee('170,00')->assertDontSee(__('offers::resources.offer.fields.vat_rate_at_sale'));

        $this->actingAs(UserModel::factory()->admin()->create());
        $this->openOffer($offer)->assertSee(__('offers::resources.offer.fields.vat_rate_at_sale'))->assertSee('23 %');
    }

    public function test_the_list_row_offers_the_same_actions_and_hides_the_locked_ones(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $locked = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($locked->id)->madeBy($admin->id, true)->create();

        Livewire::test(ListOffers::class)
            ->assertActionVisible(TestAction::make('reserve')->table($active))
            ->assertActionVisible(TestAction::make('sell')->table($active))
            ->assertActionHidden(TestAction::make('releaseReservation')->table($locked))
            ->assertActionHidden(TestAction::make('sell')->table($locked));
    }
}
