<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\SendOfferForApproval;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\SendOfferForApproval\SendOfferForApprovalCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-06, FR-13, WF-01, §7 Draft/Rejected to Pending or Active. */
class SendOfferForApprovalCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new SendOfferForApprovalCommand($actor, $offerId));
    }

    public function test_a_seller_sends_their_complete_draft_to_pending(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();

        $this->send(Actor::user($owner->id), $offer->id);

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Pending, $fresh->status);
        $this->assertNull($fresh->first_approved_at);
    }

    public function test_a_seller_resends_a_rejected_offer(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->ownedBy($owner->id)->create();

        $this->send(Actor::user($owner->id), $offer->id);

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_an_incomplete_draft_is_refused_and_the_message_names_the_missing_fields(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        try {
            $this->send(Actor::user($owner->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $message = __($refusal->translationKey, $refusal->parameters);
            $this->assertStringContainsString('Cena', $message);
            $this->assertStringContainsString('Stan', $message);
            $this->assertStringContainsString('Zdjęcia publiczne', $message);
            $this->assertStringContainsString('Szyba', $message);
        }

        $this->assertSame(OfferStatus::Draft, $offer->fresh()->status);
    }

    public function test_an_admins_own_draft_with_a_glass_model_goes_active_and_records_the_approval(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->sendable()->withGlassModel($glassModel->id)->ownedBy($admin->id)->create();

        $this->send(Actor::user($admin->id), $offer->id);

        $fresh = $offer->fresh();
        $this->assertSame(OfferStatus::Active, $fresh->status);
        $this->assertNotNull($fresh->first_approved_at);
        $this->assertNotNull($fresh->last_approved_at);
    }

    public function test_an_admins_own_draft_with_only_a_hint_goes_pending(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($admin->id)->create();

        $this->send(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Pending, $offer->fresh()->status);
    }

    public function test_only_the_owner_sends_an_offer(): void
    {
        $offer = OfferModelFactory::new()->sendable()->create();
        $stranger = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();

        foreach ([$stranger, $admin] as $user) {
            try {
                $this->send(Actor::user($user->id), $offer->id);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.not_yours', $refusal->translationKey);
            }
        }

        $this->assertSame(OfferStatus::Draft, $offer->fresh()->status);
    }

    public function test_a_pending_offer_cannot_be_sent_again(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->pending()->ownedBy($owner->id)->create();

        try {
            $this->send(Actor::user($owner->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.cannot_be_sent', $refusal->translationKey);
        }
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->sendable()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::system('test'), $offer->id);
    }
}
