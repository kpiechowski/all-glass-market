<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller\WithdrawOffersOfDeactivatedSellerCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-35, WF-09, FR-34: a deactivated seller's Offers are withdrawn by the System, with one Journal entry. */
class WithdrawOffersOfDeactivatedSellerCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(int $ownerId, ?Actor $actor = null): void
    {
        app(CommandBus::class)->send(new WithdrawOffersOfDeactivatedSellerCommand($actor ?? Actor::system('seller deactivated'), $ownerId));
    }

    public function test_active_pending_and_draft_offers_are_withdrawn_and_the_rest_stay(): void
    {
        $seller = UserModel::factory()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $pending = OfferModelFactory::new()->sendable()->status(OfferStatus::Pending)->ownedBy($seller->id)->create();
        $draft = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($seller->id)->create();
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($seller->id)->create();
        $rejected = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->ownedBy($seller->id)->create();
        $withdrawn = OfferModelFactory::new()->sendable()->status(OfferStatus::Withdrawn)->ownedBy($seller->id)->create();
        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->inTrash()->create();
        $foreign = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->send($seller->id);

        foreach ([$active, $pending, $draft] as $offer) {
            $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
        }
        $this->assertSame(OfferStatus::Reserved, $reserved->fresh()->status);
        $this->assertSame(OfferStatus::Sold, $sold->fresh()->status);
        $this->assertSame(OfferStatus::Rejected, $rejected->fresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $withdrawn->fresh()->status);
        $this->assertSame(OfferStatus::Active, $trashed->fresh()->status);
        $this->assertSame(OfferStatus::Active, $foreign->fresh()->status);
    }

    public function test_the_owner_gets_one_entry_for_all_offers_and_none_when_nothing_was_withdrawn(): void
    {
        $seller = UserModel::factory()->create();
        OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        OfferModelFactory::new()->sendable()->status(OfferStatus::Pending)->ownedBy($seller->id)->create();
        OfferModelFactory::new()->ownedBy($seller->id)->create();

        $this->send($seller->id);

        $entries = JournalEntryModel::query()->where('user_id', $seller->id)->get();
        $this->assertCount(1, $entries);
        $this->assertSame('Oferty zostały wycofane', $entries->first()->title);
        $this->assertStringContainsString('3', $entries->first()->content);

        $this->send($seller->id);
        $this->assertSame(1, JournalEntryModel::query()->count());

        $quiet = UserModel::factory()->create();
        OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($quiet->id)->create();
        $this->send($quiet->id);
        $this->assertSame(0, JournalEntryModel::query()->where('user_id', $quiet->id)->count());
    }

    public function test_open_inquiries_of_a_withdrawn_active_offer_become_outdated(): void
    {
        $seller = UserModel::factory()->create();
        $asker = UserModel::factory()->admin()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($seller->id)->create();
        $onActive = InquiryModelFactory::new()->forOffer($active->id)->sentBy($asker->id)->create();
        $onReserved = InquiryModelFactory::new()->forOffer($reserved->id)->sentBy($asker->id)->create();

        $this->send($seller->id);

        $this->assertSame(InquiryState::Outdated, $onActive->fresh()->state);
        $this->assertSame(InquiryState::Sent, $onReserved->fresh()->state);
    }

    public function test_a_person_cannot_send_it(): void
    {
        $seller = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->ownedBy($seller->id)->create();

        $this->expectException(AuthorizationException::class);

        try {
            $this->send($seller->id, Actor::user($admin->id));
        } finally {
            $this->assertSame(OfferStatus::Draft, $offer->fresh()->status);
        }
    }
}
