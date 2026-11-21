<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\OutdateInquiriesOfOffer;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\OutdateInquiriesOfOffer\OutdateInquiriesOfOfferCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-23, FR-34: the open Inquiries of the Offer become Outdated and their senders are told, but not the actor. */
class OutdateInquiriesOfOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_inquiries_become_outdated_and_each_sender_but_the_actor_is_told(): void
    {
        $actor = UserModel::factory()->admin()->create();
        $other = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->create();
        $own = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($actor->id)->create();
        $read = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($other->id)->inState(InquiryState::Read)->create();
        $accepted = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Accepted)->create();

        app(CommandBus::class)->send(new OutdateInquiriesOfOfferCommand(Actor::user($actor->id), $offer->id));

        $this->assertSame(InquiryState::Outdated, $own->fresh()->state);
        $this->assertSame(InquiryState::Outdated, $read->fresh()->state);
        $this->assertSame(InquiryState::Accepted, $accepted->fresh()->state);
        $this->assertSame([$other->id], JournalEntryModel::query()->pluck('user_id')->all());
    }

    public function test_an_offer_without_open_inquiries_changes_nothing(): void
    {
        $offer = OfferModelFactory::new()->create();

        app(CommandBus::class)->send(new OutdateInquiriesOfOfferCommand(Actor::system('test'), $offer->id));

        $this->assertSame(0, JournalEntryModel::query()->count());
    }
}
