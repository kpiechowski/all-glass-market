<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\RejectInquiry;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\RejectInquiry\RejectInquiryCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-22, FR-34. */
class RejectInquiryCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function reject(Actor $actor, int $inquiryId): void
    {
        app(CommandBus::class)->send(new RejectInquiryCommand($actor, $inquiryId));
    }

    public function test_the_owner_rejects_and_only_the_sending_admin_is_told(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($admin->id)->create();

        $this->reject(Actor::user($owner->id), $inquiry->id);

        $fresh = $inquiry->fresh();
        $this->assertSame(InquiryState::Rejected, $fresh->state);
        $this->assertNotNull($fresh->answered_at);
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame($admin->id, JournalEntryModel::query()->sole()->user_id);
    }

    public function test_a_closed_inquiry_cannot_be_rejected(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Outdated)->create();

        try {
            $this->reject(Actor::user($owner->id), $inquiry->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.inquiry_is_closed', $refusal->translationKey);
        }
    }

    public function test_someone_who_is_not_the_owner_is_refused(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $stranger = UserModel::factory()->create();

        $this->expectException(OfferRefused::class);

        $this->reject(Actor::user($stranger->id), $inquiry->id);
    }
}
