<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ApproveOffers;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\ApproveOffers\ApproveOffersCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-11, RULE-14: bulk approve acts as the single action and reports approved and skipped counts. */
class ApproveOffersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_pending_and_rejected_offers_with_a_glass_model_and_skips_the_rest(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $pending = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->create();
        $rejected = OfferModelFactory::new()->sendable()->status(OfferStatus::Rejected)->withGlassModel($glassModel->id)->create();
        $withoutModel = OfferModelFactory::new()->pending()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($glassModel->id)->create();
        $draft = OfferModelFactory::new()->sendable()->withGlassModel($glassModel->id)->create();

        $result = app(CommandBus::class)->send(new ApproveOffersCommand(
            Actor::user($admin->id),
            [$pending->id, $rejected->id, $withoutModel->id, $active->id, $draft->id],
        ));

        $this->assertSame(['approved' => 2, 'skipped' => 3], $result);
        $this->assertSame(OfferStatus::Active, $pending->fresh()->status);
        $this->assertSame(OfferStatus::Active, $rejected->fresh()->status);
        $this->assertSame(OfferStatus::Pending, $withoutModel->fresh()->status);
        $this->assertSame(OfferStatus::Draft, $draft->fresh()->status);
        $this->assertSame(2, JournalEntryModel::query()->count());
    }

    public function test_an_offer_in_the_trash_is_skipped(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $trashed = OfferModelFactory::new()->pending()->withGlassModel($glassModel->id)->inTrash()->create();

        $result = app(CommandBus::class)->send(new ApproveOffersCommand(Actor::user($admin->id), [$trashed->id]));

        $this->assertSame(['approved' => 0, 'skipped' => 1], $result);
        $this->assertSame(OfferStatus::Pending, $trashed->fresh()->status);
    }

    public function test_an_unknown_offer_counts_as_skipped(): void
    {
        $admin = UserModel::factory()->admin()->create();

        $result = app(CommandBus::class)->send(new ApproveOffersCommand(Actor::user($admin->id), [999999]));

        $this->assertSame(['approved' => 0, 'skipped' => 1], $result);
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(CommandBus::class)->send(new ApproveOffersCommand(Actor::user($seller->id), []));
    }
}
