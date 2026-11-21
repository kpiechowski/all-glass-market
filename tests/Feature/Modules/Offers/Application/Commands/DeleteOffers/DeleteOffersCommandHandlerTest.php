<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\DeleteOffers;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\DeleteOffers\DeleteOffersCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-25, RULE-06, RULE-14: bulk delete moves each fit Offer to the Trash with its status, skips the rest and counts. */
class DeleteOffersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_trashes_the_fit_offers_and_skips_reserved_and_trashed_ones(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();
        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create();

        $result = app(CommandBus::class)->send(new DeleteOffersCommand(Actor::user($admin->id), [$active->id, $sold->id, $reserved->id, $trashed->id, 999999]));

        $this->assertSame(['done' => 2, 'skipped' => 3], $result);
        $this->assertNotNull($active->fresh()->trashed_at);
        $this->assertSame(OfferStatus::Active, $active->fresh()->status);
        $this->assertNotNull($sold->fresh()->trashed_at);
        $this->assertNull($reserved->fresh()->trashed_at);
        $this->assertSame(2, JournalEntryModel::query()->count());
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($seller->id)->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new DeleteOffersCommand(Actor::user($seller->id), [$offer->id]));
    }
}
