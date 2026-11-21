<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\OwnerAccountType;
use App\Modules\Offers\Domain\Policies\BuyersPricePolicy;
use App\Modules\Offers\Domain\ValueObjects\VatRate;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seller_gets_only_their_own_offers(): void
    {
        $seller = UserModel::factory()->create();
        $mine = OfferModelFactory::new()->ownedBy($seller->id)->create();
        OfferModelFactory::new()->ownedBy(UserModel::factory()->create()->id)->create();

        $ids = (new OfferQueries)->visibleTo($seller->id, managesAll: false)->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_whoever_manages_all_offers_gets_every_offer(): void
    {
        $admin = UserModel::factory()->admin()->create();
        OfferModelFactory::new()->count(2)->create();

        $this->assertCount(2, (new OfferQueries)->visibleTo($admin->id, managesAll: true)->get());
    }

    public function test_the_approval_queue_holds_pending_offers_only(): void
    {
        $pending = OfferModelFactory::new()->pending()->create();
        OfferModelFactory::new()->create();
        OfferModelFactory::new()->status(OfferStatus::Rejected)->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->create();

        $this->assertSame([$pending->id], (new OfferQueries)->pendingApproval()->pluck('id')->all());
    }

    public function test_a_seller_never_gets_an_offer_in_the_trash_but_an_admin_can_open_it(): void
    {
        $seller = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $live = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $trashed = OfferModelFactory::new()->ownedBy($seller->id)->inTrash()->create();

        $this->assertSame([$live->id], (new OfferQueries)->visibleTo($seller->id, managesAll: false)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$live->id, $trashed->id], (new OfferQueries)->visibleTo($admin->id, managesAll: true)->pluck('id')->all());
    }

    public function test_outside_the_trash_and_only_the_trash_split_the_offers(): void
    {
        $live = OfferModelFactory::new()->create();
        $trashed = OfferModelFactory::new()->inTrash()->create();
        $queries = new OfferQueries;

        $this->assertSame([$live->id], $queries->outsideTrash(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all());
        $this->assertSame([$trashed->id], $queries->onlyTrash(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all());
    }

    public function test_a_pending_offer_in_the_trash_is_not_in_the_approval_queue(): void
    {
        $pending = OfferModelFactory::new()->pending()->create();
        OfferModelFactory::new()->pending()->inTrash()->create();

        $this->assertSame([$pending->id], (new OfferQueries)->pendingApproval()->pluck('id')->all());
        $this->assertSame(
            [$pending->id],
            (new OfferQueries)->onlyPending(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all(),
        );
    }

    public function test_the_sql_buyers_price_matches_the_policy_on_edge_values(): void
    {
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $prices = [1, 2, 3, 49, 50, 99, 100, 101, 1234, 4999, 5000, 12345, 15000, 99999, 1000000];
        $rates = [0, 1, 50, 800, 2300, 2350, 10000];

        foreach ($prices as $price) {
            OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => $price]);
            OfferModelFactory::new()->ownedBy($company->id)->create(['price_grosze' => $price]);
        }

        foreach ($rates as $rate) {
            $queries = new OfferQueries;
            $rows = OfferModel::query()
                ->selectRaw('offers.*, ('.$queries->buyersPriceSql($rate).') as sql_buyers_price')
                ->get();

            $this->assertCount(count($prices) * 2, $rows);

            foreach ($rows as $row) {
                $owner = $row->owner_id === $company->id ? OwnerAccountType::Company : OwnerAccountType::Private;

                $this->assertSame(
                    BuyersPricePolicy::buyersPriceGrosze($row->price_grosze, $owner, VatRate::fromBasisPoints($rate)),
                    (int) $row->sql_buyers_price,
                    "price {$row->price_grosze}, rate {$rate}, {$owner->value}",
                );
            }
        }
    }

    public function test_offers_are_ordered_and_filtered_by_the_buyers_price(): void
    {
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $dearPrivate = OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => 20000]); // 24600
        $cheapPrivate = OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => 10000]); // 12300
        $midCompany = OfferModelFactory::new()->ownedBy($company->id)->create(['price_grosze' => 20000]); // 20000
        $unpriced = OfferModelFactory::new()->ownedBy($company->id)->create(['price_grosze' => null]);
        $queries = new OfferQueries;

        $ascending = $queries->orderedByBuyersPrice(OfferModel::query()->whereNotNull('price_grosze'), 'asc', 2300)->pluck('id')->all();
        $descending = $queries->orderedByBuyersPrice(OfferModel::query()->whereNotNull('price_grosze'), 'desc', 2300)->pluck('id')->all();
        $inRange = $queries->withBuyersPriceBetween(OfferModel::query(), 12300, 20000, 2300)->pluck('id')->all();
        $atLeast = $queries->withBuyersPriceBetween(OfferModel::query(), 20001, null, 2300)->pluck('id')->all();
        $atMost = $queries->withBuyersPriceBetween(OfferModel::query(), null, 12299, 2300)->pluck('id')->all();

        $this->assertSame([$cheapPrivate->id, $midCompany->id, $dearPrivate->id], $ascending);
        $this->assertSame([$dearPrivate->id, $midCompany->id, $cheapPrivate->id], $descending);
        $this->assertEqualsCanonicalizing([$cheapPrivate->id, $midCompany->id], $inRange);
        $this->assertSame([$dearPrivate->id], $atLeast);
        $this->assertSame([], $atMost);
        $this->assertNotContains($unpriced->id, $inRange);
    }

    public function test_the_stat_counts_cover_own_offers_outside_the_trash(): void
    {
        $seller = UserModel::factory()->create();
        OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Active)->count(2)->create();
        OfferModelFactory::new()->ownedBy($seller->id)->pending()->create();
        OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Active)->inTrash()->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->create();

        $counts = (new OfferQueries)->countsByStatusOf($seller->id);

        $this->assertSame(['active' => 2, 'pending' => 1], $counts);
    }

    public function test_sold_in_month_counts_own_sales_of_that_month_without_returned_ones(): void
    {
        $seller = UserModel::factory()->create();
        $now = now()->setDate(2026, 11, 15)->setTime(12, 0);
        $own = OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Sold)->create();
        $counted = SaleModelFactory::new()->forOffer($own->id)->create(['sold_at' => $now->copy()->startOfMonth()]);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->ownedBy($seller->id)->create()->id)->create(['sold_at' => $now->copy()->endOfMonth()]);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->ownedBy($seller->id)->create()->id)->create(['sold_at' => $now->copy()->subMonth()->endOfMonth()]);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->ownedBy($seller->id)->create()->id)->create(['sold_at' => $now->copy()->addMonth()->startOfMonth()]);
        SaleModelFactory::new()->forOffer($own->id)->returned($seller->id)->create(['sold_at' => $now]);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->create()->id)->create(['sold_at' => $now]);

        $this->assertNotNull($counted);
        $this->assertSame(2, (new OfferQueries)->soldInMonthOf($seller->id, $now));
    }

    public function test_the_displayed_stars_filter_counts_a_new_piece_as_five(): void
    {
        $five = OfferModelFactory::new()->used(5)->create();
        $four = OfferModelFactory::new()->used(4)->create();
        $new = OfferModelFactory::new()->create(['condition' => 'new', 'rating' => null]);
        $queries = new OfferQueries;

        $this->assertEqualsCanonicalizing([$five->id, $new->id], $queries->withDisplayedStars(OfferModel::query(), 5)->pluck('id')->all());
        $this->assertSame([$four->id], $queries->withDisplayedStars(OfferModel::query(), 4)->pluck('id')->all());
    }

    private function approvedAt(CarbonImmutable $at, ?int $ownerId = null, bool $trashed = false): OfferModel
    {
        $factory = OfferModelFactory::new();
        $factory = $ownerId === null ? $factory : $factory->ownedBy($ownerId);
        $factory = $trashed ? $factory->inTrash() : $factory;

        return $factory->create(['first_approved_at' => $at]);
    }

    public function test_the_monthly_activity_has_twelve_months_with_zeros_where_nothing_happened(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');

        $months = (new OfferQueries)->monthlyActivity($now);

        $this->assertCount(12, $months);
        $this->assertSame('2025-12', $months[0]['month']);
        $this->assertSame('2026-11', $months[11]['month']);
        foreach ($months as $month) {
            $this->assertSame(0, $month['added']);
            $this->assertSame(0, $month['sold']);
        }
    }

    public function test_the_monthly_activity_counts_offers_by_first_approval_and_sales_by_sold_at(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');
        $this->approvedAt(CarbonImmutable::parse('2026-11-01 00:00:00'));
        $this->approvedAt(CarbonImmutable::parse('2026-11-30 23:59:59'));
        $this->approvedAt(CarbonImmutable::parse('2026-08-15 10:00:00'));
        $offer = OfferModelFactory::new()->create();
        SaleModelFactory::new()->forOffer($offer->id)->create(['sold_at' => '2026-11-05 09:00:00']);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->create()->id)->create(['sold_at' => '2026-09-05 09:00:00']);

        $months = collect((new OfferQueries)->monthlyActivity($now))->keyBy('month');

        $this->assertSame(2, $months['2026-11']['added']);
        $this->assertSame(1, $months['2026-08']['added']);
        $this->assertSame(0, $months['2026-10']['added']);
        $this->assertSame(1, $months['2026-11']['sold']);
        $this->assertSame(1, $months['2026-09']['sold']);
        $this->assertSame(0, $months['2026-10']['sold']);
    }

    public function test_the_monthly_activity_window_includes_its_first_and_last_instants_and_nothing_outside(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');
        $this->approvedAt(CarbonImmutable::parse('2025-12-01 00:00:00'));
        $this->approvedAt(CarbonImmutable::parse('2025-11-30 23:59:59'));
        $this->approvedAt(CarbonImmutable::parse('2026-12-01 00:00:00'));
        $offer = OfferModelFactory::new()->create();
        SaleModelFactory::new()->forOffer($offer->id)->create(['sold_at' => '2025-12-01 00:00:00']);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->create()->id)->create(['sold_at' => '2025-11-30 23:59:59']);
        SaleModelFactory::new()->forOffer(OfferModelFactory::new()->create()->id)->create(['sold_at' => '2026-12-01 00:00:00']);

        $months = (new OfferQueries)->monthlyActivity($now);

        $this->assertSame(1, array_sum(array_column($months, 'added')));
        $this->assertSame(1, array_sum(array_column($months, 'sold')));
        $this->assertSame(1, $months[0]['added']);
        $this->assertSame(1, $months[0]['sold']);
    }

    public function test_a_returned_sale_is_not_counted_as_sold(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');
        $seller = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($seller->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($seller->id)->create(['sold_at' => '2026-11-03 09:00:00']);
        SaleModelFactory::new()->forOffer($offer->id)->create(['sold_at' => '2026-11-04 09:00:00']);

        $this->assertSame(1, (new OfferQueries)->monthlyActivity($now)[11]['sold']);
    }

    public function test_an_offer_in_the_trash_counts_in_neither_line_and_nor_does_its_sale(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');
        $this->approvedAt(CarbonImmutable::parse('2026-11-02 10:00:00'), trashed: true);
        $trashed = OfferModelFactory::new()->inTrash()->create();
        SaleModelFactory::new()->forOffer($trashed->id)->create(['sold_at' => '2026-11-04 09:00:00']);

        $this->assertSame(['added' => 0, 'sold' => 0], array_intersect_key((new OfferQueries)->monthlyActivity($now)[11], ['added' => 0, 'sold' => 0]));
    }

    public function test_the_monthly_activity_can_be_scoped_to_one_seller(): void
    {
        $now = CarbonImmutable::parse('2026-11-21 12:00:00');
        $mine = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $this->approvedAt(CarbonImmutable::parse('2026-11-02 10:00:00'), $mine->id);
        $this->approvedAt(CarbonImmutable::parse('2026-11-02 10:00:00'), $other->id);
        $this->approvedAt(CarbonImmutable::parse('2026-11-02 10:00:00'), $other->id);
        $myOffer = OfferModelFactory::new()->ownedBy($mine->id)->create();
        SaleModelFactory::new()->forOffer($myOffer->id)->create(['sold_at' => '2026-11-04 09:00:00']);
        foreach (range(1, 3) as $ignored) {
            SaleModelFactory::new()->forOffer(OfferModelFactory::new()->ownedBy($other->id)->create()->id)->create(['sold_at' => '2026-11-04 09:00:00']);
        }

        $queries = new OfferQueries;

        $this->assertSame(1, $queries->monthlyActivity($now, $mine->id)[11]['added']);
        $this->assertSame(1, $queries->monthlyActivity($now, $mine->id)[11]['sold']);
        $this->assertSame(3, $queries->monthlyActivity($now)[11]['added']);
        $this->assertSame(4, $queries->monthlyActivity($now)[11]['sold']);
    }

    public function test_sellers_with_offers_lists_each_owner_once_by_name(): void
    {
        $zed = UserModel::factory()->create(['name' => 'Zed']);
        $ann = UserModel::factory()->create(['name' => 'Ann']);
        UserModel::factory()->create(['name' => 'Nobody']);
        OfferModelFactory::new()->ownedBy($zed->id)->count(2)->create();
        OfferModelFactory::new()->ownedBy($ann->id)->create();

        $this->assertSame([$ann->id => 'Ann', $zed->id => 'Zed'], (new OfferQueries)->sellersWithOffers());
    }

    public function test_it_counts_offers_by_glass_model_for_the_asked_models_only(): void
    {
        $first = GlassModelModelFactory::new()->create();
        $second = GlassModelModelFactory::new()->create();
        $unasked = GlassModelModelFactory::new()->create();
        OfferModelFactory::new()->count(2)->withGlassModel($first->id)->create();
        OfferModelFactory::new()->withGlassModel($second->id)->create();
        OfferModelFactory::new()->withGlassModel($unasked->id)->create();
        OfferModelFactory::new()->create();

        $counts = (new OfferQueries)->countByGlassModel(OfferModel::query(), [$first->id, $second->id, 999999]);

        $this->assertSame([$first->id => 2, $second->id => 1], $counts);
    }

    public function test_only_status_keeps_the_status_outside_the_trash(): void
    {
        $active = OfferModelFactory::new()->status(OfferStatus::Active)->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->inTrash()->create();
        OfferModelFactory::new()->pending()->create();

        $ids = (new OfferQueries)->onlyStatus(OfferModel::query(), OfferStatus::Active)->pluck('id')->all();

        $this->assertSame([$active->id], $ids);
    }

    public function test_of_glass_models_keeps_offers_of_those_models_only(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $other = GlassModelModelFactory::new()->create();
        $mine = OfferModelFactory::new()->withGlassModel($model->id)->create();
        OfferModelFactory::new()->withGlassModel($other->id)->create();
        OfferModelFactory::new()->create();

        $ids = (new OfferQueries)->ofGlassModels(OfferModel::query(), [$model->id])->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_without_glass_model_keeps_offers_that_have_none(): void
    {
        $model = GlassModelModelFactory::new()->create();
        $bare = OfferModelFactory::new()->withHint('OEM 1')->create();
        OfferModelFactory::new()->withGlassModel($model->id)->create();

        $ids = (new OfferQueries)->withoutGlassModel(OfferModel::query())->pluck('id')->all();

        $this->assertSame([$bare->id], $ids);
    }

    public function test_find_returns_the_offer_or_nothing(): void
    {
        $offer = OfferModelFactory::new()->create();

        $this->assertSame($offer->id, (new OfferQueries)->find($offer->id)?->id);
        $this->assertNull((new OfferQueries)->find(999999));
    }
}
