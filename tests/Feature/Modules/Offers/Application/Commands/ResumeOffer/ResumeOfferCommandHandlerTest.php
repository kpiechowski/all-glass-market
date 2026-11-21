<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ResumeOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\RejectOffer\RejectOfferCommand;
use App\Modules\Offers\Application\Commands\ResumeOffer\ResumeOfferCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24, FR-13: "Wznów sprzedaż". */
class ResumeOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function resume(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new ResumeOfferCommand($actor, $offerId));
    }

    private function refusalKey(Actor $actor, int $offerId): string
    {
        try {
            $this->resume($actor, $offerId);
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_the_owner_resumes_an_approved_unchanged_withdrawn_offer_to_active(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Withdrawn)->ownedBy($owner->id)->create();
        $approvedAt = $offer->last_approved_at;

        $this->resume(Actor::user($owner->id), $offer->id);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertEquals($approvedAt, $offer->fresh()->last_approved_at);
    }

    public function test_an_admin_resumes_a_sellers_offer_under_the_same_rule(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Withdrawn)->create();

        $this->resume(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_an_offer_changed_since_its_approval_is_refused(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->changedSinceApproval()->status(OfferStatus::Withdrawn)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(Actor::user($owner->id), $offer->id));
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
    }

    public function test_an_offer_an_admin_rejected_cannot_be_resumed_after_the_seller_withdraws_it(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        app(CommandBus::class)->send(new RejectOfferCommand(Actor::user($admin->id), $offer->id, 'Zdjęcia'));
        app(CommandBus::class)->send(new WithdrawOfferCommand(Actor::user($owner->id), $offer->id));

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(Actor::user($owner->id), $offer->id));
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
    }

    public function test_an_offer_that_was_never_approved_is_refused(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Withdrawn)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(Actor::user($owner->id), $offer->id));
    }

    public function test_an_admins_own_withdrawn_offer_resumes_with_a_glass_model_even_when_changed(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->withGlassModel(5)->changedSinceApproval()->status(OfferStatus::Withdrawn)->ownedBy($admin->id)->create();

        $this->resume(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertNotNull($offer->fresh()->last_approved_at);
    }

    public function test_an_admins_own_withdrawn_offer_with_only_a_hint_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Withdrawn)->ownedBy($admin->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(Actor::user($admin->id), $offer->id));
    }

    public function test_an_offer_that_is_not_withdrawn_is_refused(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_resumed', $this->refusalKey(Actor::user($owner->id), $offer->id));
    }

    public function test_a_seller_cannot_resume_another_sellers_offer(): void
    {
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Withdrawn)->create();
        $stranger = UserModel::factory()->create();

        $this->assertSame('offers::exceptions.not_yours', $this->refusalKey(Actor::user($stranger->id), $offer->id));
        $this->assertSame(OfferStatus::Withdrawn, $offer->fresh()->status);
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->sendable()->approvedBefore()->status(OfferStatus::Withdrawn)->create();

        $this->expectException(AuthorizationException::class);

        $this->resume(Actor::system('test'), $offer->id);
    }
}
