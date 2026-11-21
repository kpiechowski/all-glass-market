<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\WithdrawOffers;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\WithdrawOffers\WithdrawOffersCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24, RULE-14: bulk withdraw under the single action's rules, with done and skipped counts. */
class WithdrawOffersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @param  list<int>  $ids */
    private function withdraw(UserModel $user, array $ids): array
    {
        return app(CommandBus::class)->send(new WithdrawOffersCommand(Actor::user($user->id), $ids));
    }

    public function test_a_seller_withdraws_own_offers_and_skips_foreign_and_unfit_ones(): void
    {
        $seller = UserModel::factory()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $pending = OfferModelFactory::new()->sendable()->status(OfferStatus::Pending)->ownedBy($seller->id)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($seller->id)->create();
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($seller->id)->create();
        $draft = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->inTrash()->create();
        $foreign = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $result = $this->withdraw($seller, [$active->id, $pending->id, $reserved->id, $sold->id, $draft->id, $trashed->id, $foreign->id, 999999]);

        $this->assertSame(['done' => 2, 'skipped' => 6], $result);
        $this->assertSame(OfferStatus::Withdrawn, $active->fresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $pending->fresh()->status);
        $this->assertSame(OfferStatus::Reserved, $reserved->fresh()->status);
        $this->assertSame(OfferStatus::Sold, $sold->fresh()->status);
        $this->assertSame(OfferStatus::Active, $trashed->fresh()->status);
        $this->assertSame(OfferStatus::Active, $foreign->fresh()->status);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_an_admin_withdraws_any_offer_and_each_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $first = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $second = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->create();
        $own = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($admin->id)->create();

        $result = $this->withdraw($admin, [$first->id, $second->id, $own->id, $first->id]);

        $this->assertSame(['done' => 3, 'skipped' => 0], $result);
        $this->assertSame(2, JournalEntryModel::query()->count());
        $this->assertTrue(JournalEntryModel::query()->where('user_id', $first->owner_id)->where('subject_id', $first->id)->exists());
    }

    public function test_every_offer_that_leaves_active_outdates_its_inquiries(): void
    {
        $seller = UserModel::factory()->create();
        $asker = UserModel::factory()->admin()->create();
        $first = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $second = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();
        $firstInquiry = InquiryModelFactory::new()->forOffer($first->id)->sentBy($asker->id)->create();
        $secondInquiry = InquiryModelFactory::new()->forOffer($second->id)->sentBy($asker->id)->create();

        $this->withdraw($seller, [$first->id, $second->id]);

        $this->assertSame(InquiryState::Outdated, $firstInquiry->fresh()->state);
        $this->assertSame(InquiryState::Outdated, $secondInquiry->fresh()->state);
    }
}
