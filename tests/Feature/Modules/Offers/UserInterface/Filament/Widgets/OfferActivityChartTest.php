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
use Livewire\Features\SupportTesting\Testable;
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

    /** @return array{labels: array<int, string>, datasets: array<int, array<string, mixed>>} */
    private function chartData(Testable $component): array
    {
        return (fn (): array => $this->getData())->call($component->instance());
    }

    /** @return array{0: int, 1: int} the current month's added and sold counts */
    private function thisMonth(Testable $component): array
    {
        $datasets = $this->chartData($component)['datasets'];

        return [$datasets[0]['data'][11], $datasets[1]['data'][11]];
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

        $component = Livewire::test(OfferActivityChart::class)->assertDontSee('Wszyscy sprzedający');

        $data = $this->chartData($component);
        $this->assertSame(['Dodane do sprzedaży', 'Sprzedane'], array_column($data['datasets'], 'label'));
        $this->assertCount(12, $data['labels']);
        $this->assertSame('lis', $data['labels'][11]);
        $this->assertSame([1, 2], $this->thisMonth($component));
    }

    public function test_a_seller_cannot_widen_the_chart_through_the_seller_filter(): void
    {
        $seller = UserModel::factory()->create();
        $other = UserModel::factory()->create();
        $this->actingAs($seller);
        $this->sellOffer($seller->id, 1);
        $this->sellOffer($other->id, 4);

        $component = Livewire::test(OfferActivityChart::class)->set('filter', (string) $other->id);

        $this->assertSame([1, 1], $this->thisMonth($component));
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
        $this->assertSame([2, 4], $this->thisMonth($component));

        $component->set('filter', (string) $bob->id);
        $this->assertSame([1, 3], $this->thisMonth($component));

        $component->set('filter', 'all');
        $this->assertSame([2, 4], $this->thisMonth($component));
    }

    public function test_a_chart_without_history_draws_twelve_months_of_zeros(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $data = $this->chartData(Livewire::test(OfferActivityChart::class));

        $this->assertCount(12, $data['labels']);
        $this->assertSame(0, array_sum($data['datasets'][0]['data']) + array_sum($data['datasets'][1]['data']));
    }

    public function test_the_two_lines_have_different_colours(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $datasets = $this->chartData(Livewire::test(OfferActivityChart::class))['datasets'];

        $this->assertNotNull($datasets[0]['borderColor']);
        $this->assertNotSame($datasets[0]['borderColor'], $datasets[1]['borderColor']);
    }
}
