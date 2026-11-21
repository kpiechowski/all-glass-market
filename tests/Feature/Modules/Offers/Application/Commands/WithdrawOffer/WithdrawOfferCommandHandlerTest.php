<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\WithdrawOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24, FR-34, RULE-06 (Reserved comes with Reservations), §7 to Withdrawn. */
class WithdrawOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function withdraw(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new WithdrawOfferCommand($actor, $offerId));
    }

    public function test_the_owner_withdraws_a_pending_rejected_or_active_offer_and_gets_no_journal_entry(): void
    {
        $owner = UserModel::factory()->create();

        foreach ([OfferStatus::Pending, OfferStatus::Rejected, OfferStatus::Active] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->ownedBy($owner->id)->create();

            $this->withdraw(Actor::user($owner->id), $offer->id);

            $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
        }

        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_an_admin_withdraws_a_sellers_offer_and_the_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->withdraw(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
        $entry = JournalEntryModel::query()->where('subject_id', $offer->id)->sole();
        $this->assertSame($owner->id, $entry->user_id);
        $this->assertSame(JournalSeverity::Info, $entry->severity);
    }

    public function test_a_draft_or_withdrawn_offer_cannot_be_withdrawn(): void
    {
        $owner = UserModel::factory()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Withdrawn] as $status) {
            $offer = OfferModelFactory::new()->status($status)->ownedBy($owner->id)->create();

            try {
                $this->withdraw(Actor::user($owner->id), $offer->id);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.cannot_be_withdrawn', $refusal->translationKey);
            }
        }
    }

    public function test_a_seller_cannot_withdraw_another_sellers_offer(): void
    {
        $offer = OfferModelFactory::new()->pending()->create();
        $stranger = UserModel::factory()->create();

        try {
            $this->withdraw(Actor::user($stranger->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.not_yours', $refusal->translationKey);
        }

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_an_offer_in_the_trash_cannot_be_withdrawn(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->pending()->inTrash()->create();

        $this->expectException(OfferRefused::class);

        $this->withdraw(Actor::user($admin->id), $offer->id);
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->pending()->create();

        $this->expectException(AuthorizationException::class);

        $this->withdraw(Actor::system('test'), $offer->id);
    }
}
