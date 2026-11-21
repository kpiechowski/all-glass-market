<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-14, FR-27, FR-29, RULE-14: duplicate, change Glass model and the bulk actions, each landing through its command. */
class OfferDuplicateAndBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_duplicates_a_sellers_offer_and_owns_the_copy(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])->callAction('duplicate');

        $copy = OfferModel::query()->where('id', '!=', $offer->id)->sole();
        $this->assertSame($admin->id, $copy->owner_id);
        $this->assertSame(OfferStatus::Draft, $copy->status);
    }

    public function test_an_admin_changes_the_glass_model_of_one_offer_in_any_status(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->callAction('changeGlassModel', ['glass_model_id' => $glassModel->id])
            ->assertNotified(__('offers::resources.offer.actions.change_glass_model.done'));

        $this->assertSame($glassModel->id, $offer->fresh()->glass_model_id);
        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
    }

    public function test_a_seller_is_not_offered_the_glass_model_change_but_may_duplicate_own_offer(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $offer = OfferModelFactory::new()->sendable()->ownedBy($seller->id)->create();

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->assertActionHidden('changeGlassModel')
            ->assertActionVisible('duplicate');
    }

    public function test_a_seller_changes_the_price_of_own_offers_and_the_message_counts_the_skipped(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $first = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->priced(10000)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($seller->id)->priced(10000)->create();

        Livewire::test(ListOffers::class)
            ->selectTableRecords([$first->getKey(), $reserved->getKey()])
            ->callAction(TestAction::make('changePriceSelected')->table()->bulk(), ['kind' => 'percent', 'value' => '-10.5'])
            ->assertNotified(__('offers::resources.offer.actions.change_price_selected.done', ['done' => 1, 'skipped' => 1]));

        $this->assertSame(8950, $first->fresh()->price_grosze);
        $this->assertSame(OfferStatus::Active, $first->fresh()->status);
        $this->assertSame(10000, $reserved->fresh()->price_grosze);
    }

    public function test_a_seller_withdraws_in_bulk_but_has_no_delete_or_glass_model_bulk_action(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();

        Livewire::test(ListOffers::class)
            ->assertActionHidden(TestAction::make('deleteSelected')->table()->bulk())
            ->assertActionHidden(TestAction::make('changeGlassModelSelected')->table()->bulk())
            ->selectTableRecords([$offer->getKey()])
            ->callAction(TestAction::make('withdrawSelected')->table()->bulk())
            ->assertNotified(__('offers::resources.offer.actions.withdraw_selected.done', ['done' => 1, 'skipped' => 0]));

        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
    }

    public function test_an_admin_deletes_and_changes_the_glass_model_in_bulk(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $offers = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->count(2)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();

        Livewire::test(ListOffers::class)
            ->selectTableRecords($offers->pluck('id')->all())
            ->callAction(TestAction::make('changeGlassModelSelected')->table()->bulk(), ['glass_model_id' => $glassModel->id])
            ->assertNotified(__('offers::resources.offer.actions.change_glass_model_selected.done', ['done' => 2, 'skipped' => 0]));

        foreach ($offers as $offer) {
            $this->assertSame($glassModel->id, $offer->fresh()->glass_model_id);
        }

        Livewire::test(ListOffers::class)
            ->selectTableRecords([$offers[0]->id, $reserved->id])
            ->callAction(TestAction::make('deleteSelected')->table()->bulk())
            ->assertNotified(__('offers::resources.offer.actions.delete_selected.done', ['done' => 1, 'skipped' => 1]));

        $this->assertNotNull($offers[0]->fresh()->trashed_at);
        $this->assertNull($reserved->fresh()->trashed_at);
    }
}
