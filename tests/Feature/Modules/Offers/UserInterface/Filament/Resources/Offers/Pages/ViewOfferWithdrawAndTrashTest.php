<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
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

/** FR-24 to FR-26: withdraw, resume, delete, restore and delete permanently, each landing through its command. */
class ViewOfferWithdrawAndTrashTest extends TestCase
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

    public function test_a_seller_withdraws_and_resumes_their_own_offer(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->openOffer($offer)->callAction('withdraw')->assertNotified(__('offers::resources.offer.actions.withdraw.done'));
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);

        $this->openOffer($offer)->callAction('resume')->assertNotified(__('offers::resources.offer.actions.resume.done'));
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_approval_actions_are_hidden_on_a_pending_offer_in_the_trash(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->pending()->withGlassModel(5)->inTrash()->create();
        $withoutModel = OfferModelFactory::new()->pending()->inTrash()->create();

        $this->openOffer($offer)->assertActionHidden('approve')->assertActionHidden('reject')->assertActionHidden('sendForApproval');
        $this->openOffer($withoutModel)->assertActionHidden('approveWithGlassModel');
    }

    public function test_approval_actions_are_hidden_on_a_trashed_pending_offer_in_the_trash_tab(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->pending()->withGlassModel(5)->inTrash()->create();
        $withoutModel = OfferModelFactory::new()->pending()->inTrash()->create();

        Livewire::test(ListOffers::class)->set('activeTab', 'trash')
            ->assertActionHidden(TestAction::make('approve')->table($offer))
            ->assertActionHidden(TestAction::make('reject')->table($offer))
            ->assertActionHidden(TestAction::make('approveWithGlassModel')->table($withoutModel));
    }

    public function test_resuming_is_not_offered_for_an_offer_changed_since_its_approval_and_a_seller_never_deletes(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $changed = OfferModelFactory::new()->sendable()->approvedBefore()->changedSinceApproval()->status(OfferStatus::Withdrawn)->ownedBy($owner->id)->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->openOffer($changed)->assertActionHidden('resume')->assertActionHidden('withdraw');
        $this->openOffer($active)->assertActionVisible('withdraw')->assertActionHidden('delete')->assertActionHidden('restore')->assertActionHidden('deletePermanently');
    }

    public function test_an_admin_deletes_restores_and_deletes_permanently(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->openOffer($offer)->assertActionHidden('restore')->assertActionHidden('deletePermanently')->callAction('delete')->assertNotified(__('offers::resources.offer.actions.delete.done'));
        $this->assertNotNull($offer->fresh()->trashed_at);

        $this->openOffer($offer)->assertActionHidden('delete')->assertActionHidden('edit')->callAction('restore')->assertNotified(__('offers::resources.offer.actions.restore.done'));
        $this->assertNull($offer->fresh()->trashed_at);
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);

        $this->openOffer($offer)->callAction('delete');
        $this->openOffer($offer)->callAction('deletePermanently')->assertRedirect();
        $this->assertNull(OfferModel::query()->find($offer->id));
    }

    public function test_the_trash_tab_holds_deleted_offers_and_every_other_tab_leaves_them_out(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $live = OfferModelFactory::new()->pending()->create();
        $trashed = OfferModelFactory::new()->pending()->inTrash()->create();

        Livewire::test(ListOffers::class)->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$trashed]);
        Livewire::test(ListOffers::class)->set('activeTab', 'pending')->assertCanSeeTableRecords([$live])->assertCanNotSeeTableRecords([$trashed]);
        Livewire::test(ListOffers::class)->set('activeTab', 'trash')->assertCanSeeTableRecords([$trashed])->assertCanNotSeeTableRecords([$live]);
    }

    public function test_the_row_actions_delete_and_restore(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->sendable()->create();

        Livewire::test(ListOffers::class)->callAction(TestAction::make('delete')->table($offer));
        $this->assertNotNull($offer->fresh()->trashed_at);

        Livewire::test(ListOffers::class)->set('activeTab', 'trash')->callAction(TestAction::make('restore')->table($offer));
        $this->assertNull($offer->fresh()->trashed_at);
    }

    public function test_a_seller_does_not_see_their_deleted_offer_and_cannot_edit_one_in_the_trash(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $trashed = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->inTrash()->create();

        Livewire::test(ListOffers::class)->assertCanNotSeeTableRecords([$trashed]);

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(EditOffer::class, ['record' => $trashed->getRouteKey()])->assertForbidden();
    }
}
