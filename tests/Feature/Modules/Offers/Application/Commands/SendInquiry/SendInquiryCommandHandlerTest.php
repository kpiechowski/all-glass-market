<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\SendInquiry;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\SendInquiry\SendInquiryCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\SaleChannel;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-21, FR-34. */
class SendInquiryCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, int $offerId, ?string $channel = null, string $note = 'Pan Kowalski'): void
    {
        app(CommandBus::class)->send(new SendInquiryCommand($actor, $offerId, $channel, $note));
    }

    private function refusalKey(callable $action): string
    {
        try {
            $action();
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_an_admin_sends_an_inquiry_on_an_active_offer_recording_themselves_and_the_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->send(Actor::user($admin->id), $offer->id, 'olx', ' Pan Kowalski, tel. 500 ');

        $inquiry = InquiryModel::query()->sole();
        $this->assertSame($offer->id, $inquiry->offer_id);
        $this->assertSame($admin->id, $inquiry->sender_id);
        $this->assertSame(SaleChannel::Olx, $inquiry->channel);
        $this->assertSame('Pan Kowalski, tel. 500', $inquiry->note);
        $this->assertSame(InquiryState::Sent, $inquiry->state);
        $this->assertNotNull($inquiry->sent_at);
        $entry = JournalEntryModel::query()->sole();
        $this->assertSame($owner->id, $entry->user_id);
        $this->assertSame($offer->id, $entry->subject_id);
        $this->assertSame('offer', $entry->kind);
    }

    public function test_the_channel_is_optional(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->send(Actor::user($admin->id), $offer->id, null);

        $this->assertNull(InquiryModel::query()->sole()->channel);
    }

    public function test_any_number_of_inquiries_may_be_open_on_one_offer(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $other = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->sentBy($other->id)->create();

        $this->send(Actor::user($admin->id), $offer->id);
        $this->send(Actor::user($admin->id), $offer->id);

        $this->assertSame(3, InquiryModel::query()->count());
    }

    public function test_an_offer_that_is_not_active_or_is_in_the_trash_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Pending, OfferStatus::Reserved, OfferStatus::Sold, OfferStatus::Withdrawn, OfferStatus::Rejected] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->create();

            $this->assertSame('offers::exceptions.offer_cannot_receive_inquiry', $this->refusalKey(fn () => $this->send(Actor::user($admin->id), $offer->id)), $status->value);
        }

        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create();
        $this->assertSame('offers::exceptions.offer_cannot_receive_inquiry', $this->refusalKey(fn () => $this->send(Actor::user($admin->id), $trashed->id)));
        $this->assertSame(0, InquiryModel::query()->count());
    }

    public function test_a_seller_cannot_send_an_inquiry_even_on_their_own_offer(): void
    {
        $seller = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user($seller->id), $offer->id);
    }

    public function test_a_blank_note_and_an_unknown_channel_are_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->assertSame('offers::exceptions.inquiry_note_required', $this->refusalKey(fn () => $this->send(Actor::user($admin->id), $offer->id, null, '  ')));
        $this->assertSame('offers::exceptions.inquiry_channel_is_unknown', $this->refusalKey(fn () => $this->send(Actor::user($admin->id), $offer->id, 'fax')));
        $this->assertSame(0, InquiryModel::query()->count());
    }

    public function test_an_admin_owner_asking_about_their_own_offer_gets_no_journal_entry(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($admin->id)->create();

        $this->send(Actor::user($admin->id), $offer->id);

        $this->assertSame(0, JournalEntryModel::query()->count());
    }
}
