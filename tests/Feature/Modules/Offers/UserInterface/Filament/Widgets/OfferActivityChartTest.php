<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Widgets;

use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Offers\UserInterface\Filament\Widgets\OfferActivityChart;
use App\Modules\Offers\UserInterface\Filament\Widgets\OfferStatCards;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-36, FR-37: the activity chart on the Offers list, a seller's own numbers and an admin's seller filter. */
class OfferActivityChartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->travelTo(now()->setDate(2026, 11, 21)->setTime(12, 0));
    }

    /** @return array<string, mixed> */
    private function viewData(OfferActivityChart $chart): array
    {
        return (fn (): array => $this->getViewData())->call($chart);
    }

    private function sellOffer(int $ownerId, int $sales): void
    {
        $offer = OfferModelFactory::new()->ownedBy($ownerId)->create(['first_approved_at' => now()->subDay()]);
        SaleModelFactory::new()->forOffer($offer->id)->count($sales)->create(['sold_at' => now()->subDay()]);
    }

    public function test_a_seller_sees_the_chart_under_the_stat_cards_with_own_numbers_only(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $this->sellOffer($seller->id, 2);
        $this->sellOffer(UserModel::factory()->create()->id, 5);

        Livewire::test(ListOffers::class)
            ->assertSeeLivewire(OfferStatCards::class)
            ->assertSeeLivewire(OfferActivityChart::class);

        $component = Livewire::test(OfferActivityChart::class);
        $component->assertSee('Dodane do sprzedaży')->assertSee('Sprzedane')->assertSee('lis')->assertDontSee('Wszyscy sprzedający');

        $data = $this->viewData($component->instance());
        $this->assertSame([1, 2], [$data['months'][11]['added'], $data['months'][11]['sold']]);
    }

    public function test_a_seller_cannot_widen_the_chart_through_the_seller_filter(): void
    {
        $seller = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $this->actingAs($seller);
        $this->sellOffer($seller->id, 1);
        $this->sellOffer($other->id, 4);

        $component = Livewire::test(OfferActivityChart::class)->set('sellerId', $other->id);

        $data = $this->viewData($component->instance());
        $this->assertSame([1, 1], [$data['months'][11]['added'], $data['months'][11]['sold']]);
        $this->assertSame([], $data['sellers']);
    }

    public function test_an_admin_sees_the_chart_alone_for_all_offers_and_can_filter_by_seller(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $ann = UserModel::factory()->create(['name' => 'Ann Seller']);
        $bob = UserModel::factory()->create(['name' => 'Bob Seller']);
        $this->sellOffer($ann->id, 1);
        $this->sellOffer($bob->id, 3);

        Livewire::test(ListOffers::class)
            ->assertSeeLivewire(OfferActivityChart::class)
            ->assertDontSeeLivewire(OfferStatCards::class);

        $component = Livewire::test(OfferActivityChart::class)
            ->assertSee('Wszyscy sprzedający')->assertSee('Ann Seller')->assertSee('Bob Seller');
        $this->assertSame([2, 4], [$this->viewData($component->instance())['months'][11]['added'], $this->viewData($component->instance())['months'][11]['sold']]);

        $component->set('sellerId', $bob->id);
        $this->assertSame([1, 3], [$this->viewData($component->instance())['months'][11]['added'], $this->viewData($component->instance())['months'][11]['sold']]);

        $component->set('sellerId', null);
        $this->assertSame(2, $this->viewData($component->instance())['months'][11]['added']);
    }

    public function test_a_chart_without_history_draws_twelve_months_of_zeros(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $data = $this->viewData(Livewire::test(OfferActivityChart::class)->instance());

        $this->assertCount(12, $data['months']);
        $this->assertSame(0, array_sum(array_column($data['months'], 'added')) + array_sum(array_column($data['months'], 'sold')));
        $this->assertSame([0, 1, 2, 3, 4], array_column($data['ticks'], 'value'));
    }
}
