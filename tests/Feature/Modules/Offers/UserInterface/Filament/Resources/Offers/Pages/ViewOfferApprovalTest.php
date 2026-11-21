<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-06, FR-09, FR-10, FR-13: the send, approve and reject actions on the offer view, each landing through its command. */
class ViewOfferApprovalTest extends TestCase
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

    public function test_a_seller_sends_a_complete_draft_and_it_goes_pending(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();

        $this->openOffer($offer)->callAction('sendForApproval')->assertNotified(__('offers::resources.offer.actions.send_for_approval.done_pending'));

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_sending_an_incomplete_draft_is_refused_with_the_missing_fields_named(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        $this->openOffer($offer)->callAction('sendForApproval')->assertNotified(__('offers::exceptions.missing_for_sending', ['fields' => implode(', ', [
            __('offers::exceptions.sending_fields.glass'),
            __('offers::exceptions.sending_fields.price'),
            __('offers::exceptions.sending_fields.condition'),
            __('offers::exceptions.sending_fields.public_photos'),
        ])]));

        $this->assertSame(OfferStatus::Draft, $offer->fresh()->status);
    }

    public function test_an_admins_own_draft_with_a_glass_model_goes_active_on_sending(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->actingAs($admin);
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->sendable()->withGlassModel($glassModel->id)->ownedBy($admin->id)->create();

        $this->openOffer($offer)->callAction('sendForApproval')->assertNotified(__('offers::resources.offer.actions.send_for_approval.done_active'));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_a_seller_is_offered_neither_approve_nor_reject_and_sending_only_from_draft_or_rejected(): void
    {
        $owner = UserModel::factory()->create();
        $this->actingAs($owner);
        $pending = OfferModelFactory::new()->pending()->ownedBy($owner->id)->create();
        $draft = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();

        $this->openOffer($pending)
            ->assertActionHidden('approve')
            ->assertActionHidden('approveWithGlassModel')
            ->assertActionHidden('reject')
            ->assertActionHidden('sendForApproval');
        $this->openOffer($draft)->assertActionVisible('sendForApproval');
    }

    public function test_an_admin_approves_a_pending_offer_that_has_a_glass_model(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->create();

        $this->openOffer($offer)
            ->assertActionVisible('approve')
            ->assertActionHidden('approveWithGlassModel')
            ->callAction('approve')
            ->assertNotified(__('offers::resources.offer.actions.approve.done'));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_an_offer_without_a_glass_model_is_approved_by_picking_one_in_the_slide_over(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->create();

        $this->openOffer($offer)
            ->assertActionHidden('approve')
            ->assertActionVisible('approveWithGlassModel')
            ->callAction('approveWithGlassModel', ['glass_model_id' => $glassModel->id])
            ->assertNotified(__('offers::resources.offer.actions.approve.done'));

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Active, $fresh->status);
        $this->assertSame($glassModel->id, $fresh->glass_model_id);
    }

    public function test_the_slide_over_needs_a_chosen_model(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->pending()->create();

        $this->openOffer($offer)
            ->callAction('approveWithGlassModel', ['glass_model_id' => null])
            ->assertHasActionErrors(['glass_model_id' => 'required']);

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_the_slide_over_opens_with_the_hint_as_its_search(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->pending()->withHint('VW Golf VII')->create();

        $this->openOffer($offer)
            ->mountAction('approveWithGlassModel')
            ->assertSeeHtml('VW Golf VII');
    }

    public function test_an_admin_rejects_with_a_reason_and_the_owner_reads_it(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->pending()->ownedBy($owner->id)->create();

        $this->openOffer($offer)
            ->callAction('reject', ['reason' => 'Zdjęcia są nieczytelne'])
            ->assertNotified(__('offers::resources.offer.actions.reject.done'));

        $this->assertSame(OfferStatus::Rejected, $offer->fresh()->status);
        $entry = JournalEntryModel::query()->where('user_id', $owner->id)->sole();
        $this->assertSame(JournalSeverity::Rejection, $entry->severity);
        $this->assertStringContainsString('Zdjęcia są nieczytelne', $entry->content);
    }

    public function test_a_rejection_without_a_reason_is_not_sent(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());
        $offer = OfferModelFactory::new()->pending()->create();

        $this->openOffer($offer)
            ->callAction('reject', ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_reject_is_offered_for_pending_and_active_only(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $this->openOffer(OfferModelFactory::new()->pending()->create())->assertActionVisible('reject');
        $this->openOffer(OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create())->assertActionVisible('reject');
        $this->openOffer(OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->create())->assertActionHidden('reject');
        $this->openOffer(OfferModelFactory::new()->sendable()->create())->assertActionHidden('reject');
    }

    public function test_an_admin_can_approve_a_rejected_offer_and_is_not_offered_to_send_it(): void
    {
        $this->actingAs($admin = UserModel::factory()->admin()->create());
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->withGlassModel($glassModel->id)->ownedBy($admin->id)->create();

        $this->openOffer($offer)
            ->assertActionHidden('sendForApproval')
            ->assertActionVisible('approve')
            ->callAction('approve');

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_the_approval_dates_are_shown_to_an_admin_only(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)
            ->create(['first_approved_at' => '2026-02-03 10:00:00', 'last_approved_at' => '2026-04-05 10:00:00']);

        $this->actingAs(UserModel::factory()->admin()->create());
        $this->openOffer($offer)->assertSee('03.02.2026')->assertSee('05.04.2026');

        $this->actingAs($owner);
        $this->openOffer($offer)->assertDontSee('03.02.2026');
    }
}
