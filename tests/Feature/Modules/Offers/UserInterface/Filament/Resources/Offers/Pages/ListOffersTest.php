<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_sees_only_their_own_offers(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $mine = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $theirs = OfferModelFactory::new()->create();

        Livewire::test(ListOffers::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_an_admin_sees_every_offer(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offers = OfferModelFactory::new()->count(2)->create();

        Livewire::test(ListOffers::class)->assertCanSeeTableRecords($offers);
    }

    public function test_the_page_is_titled_by_who_is_looking(): void
    {
        $this->actingAs(UserModel::factory()->create());
        Livewire::test(ListOffers::class)->assertSee('Moje oferty');

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(ListOffers::class)->assertSee('Wszystkie oferty');
    }

    public function test_the_list_shows_the_buyers_price_by_the_owners_account_type(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $private = OfferModelFactory::new()->ownedBy(UserModel::factory()->create(['account_type' => 'private'])->id)->create(['price_grosze' => 10000]);
        $company = OfferModelFactory::new()->ownedBy(UserModel::factory()->create(['account_type' => 'company'])->id)->create(['price_grosze' => 20000]);

        Livewire::test(ListOffers::class)
            ->assertTableColumnStateSet('buyers_price', 12300, $private)
            ->assertTableColumnStateSet('buyers_price', 20000, $company);
    }
}
