<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\AcceptInquiry;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\AcceptInquiry\AcceptInquiryCommand;
use App\Modules\Offers\Application\Commands\ReleaseReservation\ReleaseReservationCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-22, FR-23, WF-05, RULE-05, RULE-10. */
class AcceptInquiryCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function accept(Actor $actor, int $inquiryId): void
    {
        app(CommandBus::class)->send(new AcceptInquiryCommand($actor, $inquiryId));
    }

    public function test_accepting_reserves_the_offer_in_the_admins_name_locked_and_linked_to_the_inquiry(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($admin->id)->create(['note' => 'Pan Kowalski, tel. 500']);

        $this->accept(Actor::user($owner->id), $inquiry->id);

        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $reservation = ReservationModel::query()->sole();
        $this->assertSame($admin->id, $reservation->author_id);
        $this->assertTrue($reservation->made_by_admin);
        $this->assertSame('Pan Kowalski, tel. 500', $reservation->note);
        $this->assertSame($inquiry->id, $reservation->inquiry_id);
        $this->assertTrue($reservation->is_current);
        $fresh = $inquiry->fresh();
        $this->assertSame(InquiryState::Accepted, $fresh->state);
        $this->assertNotNull($fresh->answered_at);
        $this->assertSame($admin->id, JournalEntryModel::query()->sole()->user_id);
    }

    public function test_the_other_open_inquiries_become_outdated_and_their_senders_are_told(): void
    {
        $owner = UserModel::factory()->create();
        $first = UserModel::factory()->admin()->create();
        $second = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $firstInquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($first->id)->create();
        $secondInquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($second->id)->inState(InquiryState::Read)->create();

        $this->accept(Actor::user($owner->id), $secondInquiry->id);

        $this->assertSame(InquiryState::Outdated, $firstInquiry->fresh()->state);
        $this->assertSame(InquiryState::Accepted, $secondInquiry->fresh()->state);
        $this->assertSame([$first->id, $second->id], JournalEntryModel::query()->orderBy('user_id')->pluck('user_id')->sort()->values()->all());
    }

    public function test_the_seller_cannot_release_the_reservation_but_an_admin_can(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($admin->id)->create();
        $this->accept(Actor::user($owner->id), $inquiry->id);

        try {
            app(CommandBus::class)->send(new ReleaseReservationCommand(Actor::user($owner->id), $offer->id));
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.reservation_is_locked', $refusal->translationKey);
        }

        app(CommandBus::class)->send(new ReleaseReservationCommand(Actor::user($admin->id), $offer->id));
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_accepting_after_the_offer_stopped_being_active_is_refused_and_the_inquiry_is_outdated(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();
        app(CommandBus::class)->send(new WithdrawOfferCommand(Actor::user($owner->id), $offer->id));

        try {
            $this->accept(Actor::user($owner->id), $inquiry->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.inquiry_is_closed', $refusal->translationKey);
        }

        $this->assertSame(InquiryState::Outdated, $inquiry->fresh()->state);
        $this->assertSame(0, ReservationModel::query()->count());
    }

    public function test_an_open_inquiry_on_an_offer_that_is_not_active_is_refused_and_changes_nothing(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();

        try {
            $this->accept(Actor::user($owner->id), $inquiry->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.cannot_be_reserved', $refusal->translationKey);
        }

        $this->assertSame(InquiryState::Sent, $inquiry->fresh()->state);
        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
    }

    public function test_someone_who_is_not_the_owner_cannot_accept_not_even_an_admin(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();

        $this->expectException(OfferRefused::class);

        $this->accept(Actor::user($admin->id), $inquiry->id);
    }
}
