<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\UserInterface\Filament\Resources\Offers\Pages\ViewOffer;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-21: an admin sends an Inquiry about an Active Offer, after seeing who already asked. */
class ViewOfferSendInquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_sends_an_inquiry_through_its_command(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $this->actingAs($admin);

        Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->callAction('sendInquiry', ['channel' => 'olx', 'note' => 'Pan Kowalski'])
            ->assertNotified(__('offers::resources.offer.actions.send_inquiry.done'));

        $inquiry = InquiryModel::query()->sole();
        $this->assertSame($admin->id, $inquiry->sender_id);
        $this->assertSame('Pan Kowalski', $inquiry->note);
    }

    public function test_the_modal_says_how_many_inquiries_are_open_and_from_whom(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $asker = UserModel::factory()->admin()->create(['name' => 'Anna Pytająca']);
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->sentBy($asker->id)->create();
        $this->actingAs($admin);

        $page = Livewire::test(ViewOffer::class, ['record' => $offer->getRouteKey()])
            ->mountAction('sendInquiry')
            ->assertActionMounted('sendInquiry');

        $summary = (fn () => $this->getMountedActionSchema()->getComponents()[0]->getContent())->call($page->instance());
        $this->assertStringContainsString('Anna Pytająca', $summary);
        $this->assertStringContainsString('1', $summary);
    }

    public function test_a_seller_and_an_offer_that_is_not_active_do_not_get_the_action(): void
    {
        $seller = UserModel::factory()->create();
        $mine = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $this->actingAs($seller);
        Livewire::test(ViewOffer::class, ['record' => $mine->getRouteKey()])->assertActionHidden('sendInquiry');

        $this->actingAs(UserModel::factory()->admin()->create());
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->create();
        Livewire::test(ViewOffer::class, ['record' => $sold->getRouteKey()])->assertActionHidden('sendInquiry');
    }
}
