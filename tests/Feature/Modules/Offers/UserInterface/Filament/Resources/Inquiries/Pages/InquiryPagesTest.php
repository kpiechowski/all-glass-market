<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages;

use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Modules\Offers\UserInterface\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-21a, FR-22, FR-32: "Zapytania" lists, opening marks read, the owner accepts or rejects. */
class InquiryPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_a_seller_sees_the_inquiries_on_their_own_offers_and_an_admin_sees_all(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $mine = InquiryModelFactory::new()->forOffer(OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create()->id)->sentBy($admin->id)->create();
        $foreign = InquiryModelFactory::new()->create();

        $this->actingAs($owner);
        Livewire::test(ListInquiries::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$foreign])
            ->assertSee($admin->name);

        $this->actingAs($admin);
        Livewire::test(ListInquiries::class)->assertCanSeeTableRecords([$mine, $foreign]);
    }

    public function test_opening_an_inquiry_as_the_owner_marks_it_read_but_not_as_an_admin(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])->assertOk();
        $this->assertSame(InquiryState::Sent, $inquiry->fresh()->state);

        $this->actingAs($owner);
        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])->assertOk();
        $this->assertSame(InquiryState::Read, $inquiry->fresh()->state);
    }

    public function test_the_owner_accepts_and_the_offer_is_reserved_in_the_admins_name(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($admin->id)->create();
        $this->actingAs($owner);

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->callAction('accept')
            ->assertNotified(__('offers::resources.inquiry.actions.accept.done'));

        $this->assertSame(InquiryState::Accepted, $inquiry->fresh()->state);
        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $this->assertSame($admin->id, ReservationModel::query()->sole()->author_id);
    }

    public function test_the_owner_rejects(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $this->actingAs($owner);

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->callAction('reject')
            ->assertNotified(__('offers::resources.inquiry.actions.reject.done'));

        $this->assertSame(InquiryState::Rejected, $inquiry->fresh()->state);
    }

    public function test_the_answers_are_hidden_from_an_admin_who_is_not_the_owner_and_for_a_closed_inquiry(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $open = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $closed = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Outdated)->create();

        $this->actingAs(UserModel::factory()->admin()->create());
        Livewire::test(ViewInquiry::class, ['record' => $open->getRouteKey()])
            ->assertActionHidden('accept')
            ->assertActionHidden('reject');

        $this->actingAs($owner);
        Livewire::test(ViewInquiry::class, ['record' => $closed->getRouteKey()])
            ->assertActionHidden('accept')
            ->assertActionHidden('reject');
    }

    public function test_an_inquiry_of_an_offer_that_is_no_longer_active_cannot_be_accepted_and_shows_as_outdated(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $this->actingAs($owner);
        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])->callAction('withdraw');

        Livewire::test(ViewInquiry::class, ['record' => $inquiry->getRouteKey()])
            ->assertActionHidden('accept')
            ->assertSee(InquiryState::Outdated->getLabel());
    }
}
