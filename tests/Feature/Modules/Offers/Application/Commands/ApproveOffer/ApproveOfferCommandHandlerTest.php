<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ApproveOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\ApproveOffer\ApproveOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-09, FR-10, RULE-03, FR-34 (Journal), §7 Pending/Rejected to Active. */
class ApproveOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function approve(Actor $actor, int $offerId, ?int $glassModelId = null): void
    {
        app(CommandBus::class)->send(new ApproveOfferCommand($actor, $offerId, $glassModelId));
    }

    public function test_an_admin_approves_a_pending_offer_with_a_glass_model_and_the_owner_gets_a_journal_entry(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->ownedBy($owner->id)->create();

        $this->approve(Actor::user($admin->id), $offer->id);

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Active, $fresh->status);
        $this->assertNotNull($fresh->first_approved_at);
        $this->assertEquals($fresh->first_approved_at, $fresh->last_approved_at);

        $entry = JournalEntryModel::query()->where('user_id', $owner->id)->sole();
        $this->assertSame('offer', $entry->kind);
        $this->assertSame($offer->id, $entry->subject_id);
        $this->assertStringContainsString($offer->number, $entry->title);
    }

    public function test_a_rejected_offer_can_be_approved_and_keeps_its_first_approval_date(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $first = now()->subYear()->startOfSecond();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->withGlassModel($glassModel->id)
            ->create(['first_approved_at' => $first, 'last_approved_at' => $first]);

        $this->approve(Actor::user($admin->id), $offer->id);

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Active, $fresh->status);
        $this->assertEquals($first, $fresh->first_approved_at);
        $this->assertTrue($fresh->last_approved_at->greaterThan($first));
    }

    public function test_an_offer_without_a_glass_model_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->pending()->create();

        try {
            $this->approve(Actor::user($admin->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.glass_model_required', $refusal->translationKey);
        }

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_the_admin_selects_a_glass_model_while_approving_and_the_hint_goes(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->create();

        $this->approve(Actor::user($admin->id), $offer->id, $glassModel->id);

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Active, $fresh->status);
        $this->assertSame($glassModel->id, $fresh->glass_model_id);
        $this->assertNull($fresh->identification_hint);
    }

    public function test_a_withdrawn_or_unknown_glass_model_cannot_be_selected(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();
        $offer = OfferModelFactory::new()->pending()->create();

        foreach ([$withdrawn->id, 999999] as $id) {
            try {
                $this->approve(Actor::user($admin->id), $offer->id, $id);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.glass_model_cannot_be_picked', $refusal->translationKey);
            }
        }

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_a_draft_or_active_offer_cannot_be_approved(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Active] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->withGlassModel($glassModel->id)->create();

            try {
                $this->approve(Actor::user($admin->id), $offer->id);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.cannot_be_approved', $refusal->translationKey);
            }
        }
    }

    public function test_a_seller_cannot_approve_even_their_own_offer(): void
    {
        $owner = UserModel::factory()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->ownedBy($owner->id)->create();

        $this->expectException(AuthorizationException::class);

        $this->approve(Actor::user($owner->id), $offer->id);
    }

    public function test_an_admin_approving_their_own_offer_gets_no_journal_entry(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->ownedBy($admin->id)->create();

        $this->approve(Actor::user($admin->id), $offer->id);

        $this->assertSame(0, JournalEntryModel::query()->count());
    }
}
