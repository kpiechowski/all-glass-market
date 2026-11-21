<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Offers\UserInterface\Filament\Widgets\OfferStatCards;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-31, FR-32: the status tabs, the filters and the seller's stat cards of the Offers list. */
class ListOffersTabsAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_has_every_tab_and_a_seller_every_tab_but_the_trash(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $adminTabs = array_keys(Livewire::test(ListOffers::class)->instance()->getTabs());

        $this->actingAs(UserModel::factory()->create());
        $sellerTabs = array_keys(Livewire::test(ListOffers::class)->instance()->getTabs());

        $this->assertSame(['all', 'pending', 'active', 'reserved', 'sold', 'withdrawn', 'rejected', 'drafts', 'trash'], $adminTabs);
        $this->assertSame(['all', 'pending', 'active', 'reserved', 'sold', 'withdrawn', 'rejected', 'drafts'], $sellerTabs);
    }

    public function test_each_status_tab_holds_its_status_and_leaves_the_trash_out(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $tabs = ['pending' => OfferStatus::Pending, 'active' => OfferStatus::Active, 'reserved' => OfferStatus::Reserved, 'sold' => OfferStatus::Sold,
            'withdrawn' => OfferStatus::Withdrawn, 'rejected' => OfferStatus::Rejected, 'drafts' => OfferStatus::Draft];
        $offers = [];
        foreach ($tabs as $key => $status) {
            $offers[$key] = OfferModelFactory::new()->status($status)->create();
        }
        $trashed = OfferModelFactory::new()->status(OfferStatus::Active)->inTrash()->create();

        foreach ($tabs as $key => $status) {
            $others = array_values(array_diff_key($offers, [$key => true]));
            Livewire::test(ListOffers::class)->set('activeTab', $key)
                ->assertCanSeeTableRecords([$offers[$key]])
                ->assertCanNotSeeTableRecords([...$others, $trashed]);
        }
    }

    public function test_the_all_tab_leaves_the_trash_out_and_the_trash_tab_holds_only_it(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $live = OfferModelFactory::new()->create();
        $trashed = OfferModelFactory::new()->inTrash()->create();

        Livewire::test(ListOffers::class)->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$trashed]);
        Livewire::test(ListOffers::class)->set('activeTab', 'trash')->assertCanSeeTableRecords([$trashed])->assertCanNotSeeTableRecords([$live]);
    }

    public function test_a_seller_never_sees_other_sellers_or_trashed_offers_on_any_tab(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $mine = OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Active)->create();
        $trashed = OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Active)->inTrash()->create();
        $theirs = OfferModelFactory::new()->status(OfferStatus::Active)->create();

        foreach (['all', 'active', 'trash'] as $tab) {
            $component = Livewire::test(ListOffers::class)->set('activeTab', $tab)->assertCanNotSeeTableRecords([$trashed, $theirs]);
            if ($tab !== 'trash') {
                $component->assertCanSeeTableRecords([$mine]);
            }
        }
    }

    public function test_the_list_shows_the_glass_model_summary_of_each_row(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przód')->create();
        $model = GlassModelModelFactory::new()->withMainEurocode('8888AGN')->withPosition($position)->create();
        OfferModelFactory::new()->withGlassModel($model->id)->create();

        Livewire::test(ListOffers::class)->assertSee('8888AGN')->assertSee('Przód');
    }

    public function test_the_text_position_and_colour_filters_go_through_catalogues_search(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $front = DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przód')->create();
        $green = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielona')->create();
        $frontModel = GlassModelModelFactory::new()->withMainEurocode('8888AGN')->withPosition($front)->create();
        $greenModel = GlassModelModelFactory::new()->withMainEurocode('7777BGN')->withColour($green)->create();
        $withFront = OfferModelFactory::new()->withGlassModel($frontModel->id)->create();
        $withGreen = OfferModelFactory::new()->withGlassModel($greenModel->id)->create();
        $withHint = OfferModelFactory::new()->withHint('Passat')->create();

        Livewire::test(ListOffers::class)
            ->filterTable('glass_text', ['text' => '8888'])
            ->assertCanSeeTableRecords([$withFront])->assertCanNotSeeTableRecords([$withGreen, $withHint]);
        Livewire::test(ListOffers::class)
            ->filterTable('position', ['value' => $front->id])
            ->assertCanSeeTableRecords([$withFront])->assertCanNotSeeTableRecords([$withGreen, $withHint]);
        Livewire::test(ListOffers::class)
            ->filterTable('colour', ['value' => $green->id])
            ->assertCanSeeTableRecords([$withGreen])->assertCanNotSeeTableRecords([$withFront, $withHint]);
    }

    public function test_the_without_glass_model_filter_keeps_offers_with_no_model(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $model = GlassModelModelFactory::new()->create();
        $with = OfferModelFactory::new()->withGlassModel($model->id)->create();
        $without = OfferModelFactory::new()->withHint('Passat')->create();

        Livewire::test(ListOffers::class)
            ->filterTable('without_glass_model')
            ->assertCanSeeTableRecords([$without])->assertCanNotSeeTableRecords([$with]);
    }

    public function test_the_glass_model_filter_can_be_set_from_the_url(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $target = GlassModelModelFactory::new()->create();
        $other = GlassModelModelFactory::new()->create();
        $onTarget = OfferModelFactory::new()->withGlassModel($target->id)->count(2)->create();
        $onOther = OfferModelFactory::new()->withGlassModel($other->id)->create();

        Livewire::withQueryParams(['filters' => ['glass_model' => ['glass_model_id' => (string) $target->id]]])
            ->test(ListOffers::class)
            ->assertCanSeeTableRecords($onTarget)
            ->assertCanNotSeeTableRecords([$onOther]);
    }

    public function test_the_seller_producer_condition_and_rating_filters_narrow_the_list(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();
        $seller = UserModel::factory()->create();
        $match = OfferModelFactory::new()->ownedBy($seller->id)->used(4)->create(['producer_id' => $producer->id]);
        $other = OfferModelFactory::new()->used(2)->create();
        $new = OfferModelFactory::new()->create(['condition' => 'new']);

        Livewire::test(ListOffers::class)->filterTable('owner_id', $seller->id)
            ->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other, $new]);
        Livewire::test(ListOffers::class)->filterTable('producer_id', $producer->id)
            ->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other, $new]);
        Livewire::test(ListOffers::class)->filterTable('condition', 'new')
            ->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$match, $other]);
        Livewire::test(ListOffers::class)->filterTable('rating', 4)
            ->assertCanSeeTableRecords([$match])->assertCanNotSeeTableRecords([$other, $new]);
        Livewire::test(ListOffers::class)->filterTable('rating', 5)
            ->assertCanSeeTableRecords([$new])->assertCanNotSeeTableRecords([$match, $other]);
    }

    public function test_the_price_range_works_on_the_buyers_price_and_the_column_sorts_by_it(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $dear = OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => 20000]); // 246 zł
        $cheap = OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => 10000]); // 123 zł
        $mid = OfferModelFactory::new()->ownedBy($company->id)->create(['price_grosze' => 20000]); // 200 zł

        Livewire::test(ListOffers::class)
            ->filterTable('buyers_price', ['from' => '124', 'to' => '200'])
            ->assertCanSeeTableRecords([$mid])->assertCanNotSeeTableRecords([$dear, $cheap]);
        Livewire::test(ListOffers::class)
            ->sortTable('buyers_price')
            ->assertCanSeeTableRecords([$cheap, $mid, $dear], inOrder: true);
        Livewire::test(ListOffers::class)
            ->sortTable('buyers_price', 'desc')
            ->assertCanSeeTableRecords([$dear, $mid, $cheap], inOrder: true);
    }

    public function test_a_seller_has_no_seller_filter(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(ListOffers::class)->assertTableFilterHidden('owner_id');
    }

    public function test_bulk_actions_that_would_be_refused_are_hidden_per_tab(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $visible = fn (string $tab, string $action): bool => Livewire::test(ListOffers::class)->set('activeTab', $tab)
            ->instance()->getTable()->getBulkAction($action)?->isVisible() ?? false;
        // BulkActionGroup children are visible by their own closures; the group is checked through them.
        $this->assertTrue($visible('all', 'withdrawSelected'));
        $this->assertTrue($visible('active', 'withdrawSelected'));
        $this->assertFalse($visible('sold', 'withdrawSelected'));
        $this->assertFalse($visible('drafts', 'approveSelected'));
        $this->assertTrue($visible('pending', 'approveSelected'));
        $this->assertFalse($visible('reserved', 'changePriceSelected'));
        $this->assertFalse($visible('reserved', 'deleteSelected'));
        $this->assertTrue($visible('sold', 'changeGlassModelSelected'));
        foreach (['withdrawSelected', 'approveSelected', 'changePriceSelected', 'deleteSelected', 'changeGlassModelSelected'] as $action) {
            $this->assertFalse($visible('trash', $action), $action);
        }
    }

    public function test_a_seller_sees_the_stat_cards_with_own_counts_and_an_admin_does_not(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Active)->count(2)->create();
        OfferModelFactory::new()->ownedBy($seller->id)->pending()->create();
        $sold = OfferModelFactory::new()->ownedBy($seller->id)->status(OfferStatus::Sold)->create();
        SaleModelFactory::new()->forOffer($sold->id)->create();
        SaleModelFactory::new()->forOffer($sold->id)->returned($seller->id)->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->count(5)->create();

        Livewire::test(ListOffers::class)->assertSeeLivewire(OfferStatCards::class);
        Livewire::test(OfferStatCards::class)
            ->assertSeeInOrder(['2', 'Aktywne', '1', 'Oczekujące', '0', 'Zarezerwowane', '1', 'Sprzedane w tym miesiącu']);

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(ListOffers::class)->assertDontSeeLivewire(OfferStatCards::class);
    }
}
