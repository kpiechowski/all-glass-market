<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\SellOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Offers\Application\Commands\ChangeVatRate\ChangeVatRateCommand;
use App\Modules\Offers\Application\Commands\SellOffer\SellOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferSettingsModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Users\Application\ModuleApi\AccountTypes;
use App\Modules\Users\Domain\Repositories\UserRepository;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** FR-18, FR-34, RULE-05, RULE-09, RULE-10: the Sale records the prices and the VAT rate at that moment. */
class SellOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function sell(Actor $actor, int $offerId, string $channel = 'olx', int $price = 18000): void
    {
        app(CommandBus::class)->send(new SellOfferCommand($actor, $offerId, $channel, $price));
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

    public function test_the_owner_sells_an_active_offer_and_the_sale_records_prices_rate_channel_and_who(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->sell(Actor::user($owner->id), $offer->id, 'olx', 17000);

        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        $sale = SaleModel::query()->sole();
        $this->assertSame($offer->id, $sale->offer_id);
        $this->assertSame(SaleChannel::Olx, $sale->channel);
        $this->assertSame(17000, $sale->sale_price_grosze);
        $this->assertSame(15000, $sale->entered_price_grosze);
        $this->assertSame(2300, $sale->vat_rate_basis_points);
        $this->assertSame(18450, $sale->buyers_price_grosze);
        $this->assertSame($owner->id, $sale->marked_by_id);
        $this->assertNotNull($sale->sold_at);
        $this->assertNull($sale->returned_at);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_company_owner_has_the_entered_price_as_the_buyers_price(): void
    {
        $owner = UserModel::factory()->company()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->sell(Actor::user($owner->id), $offer->id);

        $this->assertSame(15000, SaleModel::query()->sole()->buyers_price_grosze);
    }

    public function test_the_sale_keeps_its_recorded_values_when_the_vat_rate_changes_later(): void
    {
        $owner = UserModel::factory()->create();
        $superAdmin = UserModel::factory()->superAdmin()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->sell(Actor::user($owner->id), $offer->id);
        app(CommandBus::class)->send(new ChangeVatRateCommand(Actor::user($superAdmin->id), 800));

        $sale = SaleModel::query()->sole();
        $this->assertSame(15000, $sale->entered_price_grosze);
        $this->assertSame(2300, $sale->vat_rate_basis_points);
        $this->assertSame(18450, $sale->buyers_price_grosze);
    }

    public function test_a_sale_after_a_rate_change_records_the_new_rate(): void
    {
        OfferSettingsModelFactory::new()->create(['vat_rate_basis_points' => 800]);
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(10000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->sell(Actor::user($owner->id), $offer->id);

        $sale = SaleModel::query()->sole();
        $this->assertSame(800, $sale->vat_rate_basis_points);
        $this->assertSame(10800, $sale->buyers_price_grosze);
    }

    public function test_selling_a_reserved_offer_ends_its_reservation_as_sold(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($owner->id, false)->create();

        $this->sell(Actor::user($owner->id), $offer->id);

        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        $reservation = ReservationModel::query()->sole();
        $this->assertSame(ReservationEnd::Sold, $reservation->ended_as);
        $this->assertNull($reservation->is_current);
    }

    public function test_an_admin_sells_over_an_admins_reservation_and_the_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create();

        $this->sell(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        $this->assertSame($admin->id, SaleModel::query()->sole()->marked_by_id);
        $this->assertSame($owner->id, JournalEntryModel::query()->where('subject_id', $offer->id)->sole()->user_id);
    }

    public function test_the_owner_cannot_sell_over_an_admins_reservation_and_nothing_changes(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create();

        $this->assertSame('offers::exceptions.reservation_is_locked', $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id)));

        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $this->assertSame(0, SaleModel::query()->count());
        $this->assertTrue(ReservationModel::query()->sole()->is_current);
    }

    public function test_an_offer_that_is_neither_active_nor_reserved_is_refused(): void
    {
        $owner = UserModel::factory()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Pending, OfferStatus::Sold, OfferStatus::Withdrawn] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->ownedBy($owner->id)->create();

            $this->assertSame('offers::exceptions.cannot_be_sold', $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id)), $status->value);
        }

        $this->assertSame(0, SaleModel::query()->count());
    }

    public function test_an_unknown_channel_and_a_price_of_zero_are_refused_and_leave_the_offer_active(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.sale_channel_is_unknown', $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id, 'allegro')));
        $this->assertSame('offers::exceptions.sale_price_is_invalid', $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id, 'olx', 0)));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame(0, SaleModel::query()->count());
    }

    public function test_another_seller_cannot_sell_it(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $stranger = UserModel::factory()->create();

        $this->assertSame('offers::exceptions.not_yours', $this->refusalKey(fn () => $this->sell(Actor::user($stranger->id), $offer->id)));
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->expectException(AuthorizationException::class);

        $this->sell(Actor::system('test'), $offer->id);
    }

    public function test_the_status_change_the_reservation_end_and_the_sale_are_one_step_in_the_change_history(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($owner->id, false)->create();
        DB::table('audit_changes')->delete();

        $this->sell(Actor::user($owner->id), $offer->id);

        $status = ChangeModel::query()->where('record_type', OfferModel::AUDIT_KEY)->where('record_id', $offer->id)->sole();
        $this->assertSame(['old' => 'reserved', 'new' => 'sold'], $status->changes['status']);
        $this->assertSame($owner->id, $status->actor_id);
        $this->assertSame('sold', ChangeModel::query()->where('record_type', ReservationModel::AUDIT_KEY)->sole()->changes['ended_as']['new']);
        $this->assertSame(
            2300,
            ChangeModel::query()->where('record_type', SaleModel::AUDIT_KEY)->sole()->changes['vat_rate_basis_points']['new'],
        );
    }

    public function test_a_refused_sale_leaves_no_history(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Draft)->ownedBy($owner->id)->create();
        DB::table('audit_changes')->delete();

        $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id));

        $this->assertSame(0, ChangeModel::query()->count());
    }

    public function test_an_unknown_account_type_of_the_owner_is_a_refusal_and_nothing_is_sold(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->priced(15000)->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $users = \Mockery::mock(UserRepository::class);
        $users->shouldReceive('accountTypesOf')->andReturn([$owner->id => 'mystery']);
        $this->app->instance(AccountTypes::class, new AccountTypes($users));

        $key = $this->refusalKey(fn () => $this->sell(Actor::user($owner->id), $offer->id));

        $this->assertSame('offers::exceptions.owner_account_type_is_unknown', $key);
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame(0, SaleModel::query()->count());
    }
}
