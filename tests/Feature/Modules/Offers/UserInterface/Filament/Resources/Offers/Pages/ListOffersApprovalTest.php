<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ListOffers;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-11: the Pending tab with its counter, row actions and bulk approve. */
class ListOffersApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_pending_tab_holds_the_queue_and_counts_it(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $pending = OfferModelFactory::new()->pending()->count(2)->create();
        $draft = OfferModelFactory::new()->create();

        $component = Livewire::test(ListOffers::class)->set('activeTab', 'pending');

        $component->assertCanSeeTableRecords($pending)->assertCanNotSeeTableRecords([$draft]);
        $this->assertEquals(2, $component->instance()->getTabs()['pending']->getBadge());
    }

    public function test_a_seller_has_no_queue(): void
    {
        $this->actingAs(UserModel::factory()->create());

        $this->assertSame([], Livewire::test(ListOffers::class)->instance()->getTabs());
    }

    public function test_an_admin_approves_and_rejects_from_the_row(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $toApprove = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->create();
        $toReject = OfferModelFactory::new()->pending()->create();

        Livewire::test(ListOffers::class)
            ->callAction(TestAction::make('approve')->table($toApprove))
            ->callAction(TestAction::make('reject')->table($toReject), ['reason' => 'Brak zdjęć']);

        $this->assertSame(OfferStatus::Active, $toApprove->fresh()->status);
        $this->assertSame(OfferStatus::Rejected, $toReject->fresh()->status);
    }

    public function test_bulk_approve_skips_offers_without_a_glass_model_and_reports_the_counts(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $withModel = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->create();
        $withoutModel = OfferModelFactory::new()->pending()->create();

        Livewire::test(ListOffers::class)
            ->selectTableRecords([$withModel->id, $withoutModel->id])
            ->callAction(TestAction::make('approveSelected')->table()->bulk())
            ->assertNotified('Zatwierdzono: 1, pominięto: 1');

        $this->assertSame(OfferStatus::Active, $withModel->fresh()->status);
        $this->assertSame(OfferStatus::Pending, $withoutModel->fresh()->status);
    }

    public function test_a_seller_has_no_approval_actions_in_the_list(): void
    {
        $seller = UserModel::factory()->create();
        $this->actingAs($seller);
        $offer = OfferModelFactory::new()->pending()->ownedBy($seller->id)->create();

        Livewire::test(ListOffers::class)
            ->assertActionHidden(TestAction::make('approve')->table($offer))
            ->assertActionHidden(TestAction::make('reject')->table($offer))
            ->assertActionHidden(TestAction::make('approveSelected')->table()->bulk());
    }
}
