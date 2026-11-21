<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\RestoreOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\RestoreOffer\RestoreOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-25: restoring returns the Offer with the status it had. */
class RestoreOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function restore(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new RestoreOfferCommand($actor, $offerId));
    }

    public function test_an_admin_restores_an_offer_from_the_trash_with_its_status(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create();

        $this->restore(Actor::user($admin->id), $offer->id);

        $this->assertNull($offer->fresh()->trashed_at);
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_an_offer_outside_the_trash_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->create();

        try {
            $this->restore(Actor::user($admin->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.cannot_be_restored', $refusal->translationKey);
        }
    }

    public function test_a_seller_cannot_restore(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->inTrash()->create();

        $this->expectException(AuthorizationException::class);

        $this->restore(Actor::user($owner->id), $offer->id);
    }
}
