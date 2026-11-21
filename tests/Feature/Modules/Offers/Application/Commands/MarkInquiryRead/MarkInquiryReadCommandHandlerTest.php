<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\MarkInquiryRead;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\MarkInquiryRead\MarkInquiryReadCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-22: opening an Inquiry as its owner marks it read. */
class MarkInquiryReadCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_owner_opening_a_sent_inquiry_marks_it_read(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();

        app(CommandBus::class)->send(new MarkInquiryReadCommand(Actor::user($owner->id), $inquiry->id));

        $fresh = $inquiry->fresh();
        $this->assertSame(InquiryState::Read, $fresh->state);
        $this->assertNotNull($fresh->read_at);
    }

    public function test_a_closed_inquiry_stays_as_it_is(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Outdated)->create();

        app(CommandBus::class)->send(new MarkInquiryReadCommand(Actor::user($owner->id), $inquiry->id));

        $this->assertSame(InquiryState::Outdated, $inquiry->fresh()->state);
    }

    public function test_someone_else_is_refused_and_the_inquiry_stays_sent(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $stranger = UserModel::factory()->create();

        try {
            app(CommandBus::class)->send(new MarkInquiryReadCommand(Actor::user($stranger->id), $inquiry->id));
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.not_yours', $refusal->translationKey);
        }

        $this->assertSame(InquiryState::Sent, $inquiry->fresh()->state);
    }
}
