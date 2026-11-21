<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Domain\Events\UserDeactivated;
use App\Modules\Users\Domain\Events\UserReactivated;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-35, WF-09: deactivating a seller withdraws their Offers; reactivating restores nothing. */
class WithdrawOffersWhenSellerIsDeactivatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_user_deactivated_event_triggers_it_and_reactivation_restores_nothing(): void
    {
        $seller = UserModel::factory()->create();
        $draft = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->create();

        event(new UserDeactivated($seller->id, CarbonImmutable::now()));

        $this->assertSame(OfferStatus::Withdrawn, $draft->fresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $active->fresh()->status);
        $this->assertSame(1, JournalEntryModel::query()->where('user_id', $seller->id)->count());

        event(new UserReactivated($seller->id, CarbonImmutable::now()));

        $this->assertSame(OfferStatus::Withdrawn, $draft->fresh()->status);
        $this->assertSame(OfferStatus::Withdrawn, $active->fresh()->status);
        $this->assertSame(1, JournalEntryModel::query()->count());
    }
}
