<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\RejectOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\RejectOffer\RejectOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-09, FR-34, §7 Pending/Active to Rejected. */
class RejectOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function reject(Actor $actor, int $offerId, string $reason = 'Nieczytelne zdjęcia'): void
    {
        app(CommandBus::class)->send(new RejectOfferCommand($actor, $offerId, $reason));
    }

    public function test_an_admin_rejects_a_pending_or_active_offer_and_the_owner_gets_the_reason_in_the_journal(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();

        foreach ([OfferStatus::Pending, OfferStatus::Active] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->ownedBy($owner->id)->create();

            $this->reject(Actor::user($admin->id), $offer->id);

            $this->assertSame(OfferStatus::Rejected, $offer->fresh()->status);
            $entry = JournalEntryModel::query()->where('subject_id', $offer->id)->sole();
            $this->assertSame($owner->id, $entry->user_id);
            $this->assertSame(JournalSeverity::Rejection, $entry->severity);
            $this->assertStringContainsString('Nieczytelne zdjęcia', $entry->content);
        }
    }

    public function test_a_reason_is_required(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->pending()->create();

        try {
            $this->reject(Actor::user($admin->id), $offer->id, '   ');
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.rejection_reason_required', $refusal->translationKey);
        }

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_draft_or_rejected_offer_cannot_be_rejected(): void
    {
        $admin = UserModel::factory()->admin()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Rejected] as $status) {
            $offer = OfferModelFactory::new()->status($status)->create();

            try {
                $this->reject(Actor::user($admin->id), $offer->id);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.cannot_be_rejected', $refusal->translationKey);
            }
        }
    }

    public function test_a_seller_cannot_reject(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->pending()->ownedBy($owner->id)->create();

        $this->expectException(AuthorizationException::class);

        $this->reject(Actor::user($owner->id), $offer->id);
    }

    public function test_an_admin_rejecting_their_own_offer_gets_no_journal_entry(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->pending()->ownedBy($admin->id)->create();

        $this->reject(Actor::user($admin->id), $offer->id);

        $this->assertSame(0, JournalEntryModel::query()->count());
    }
}
