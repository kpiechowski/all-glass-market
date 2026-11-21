<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ChangePriceOfOffers;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\ChangePriceOfOffers\ChangePriceOfOffersCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-29, RULE-14, D-31, D-53: bulk price change with done and skipped counts and no re-approval. */
class ChangePriceOfOffersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @param  list<int>  $ids */
    private function change(UserModel $user, array $ids, string $kind, int $value): array
    {
        return app(CommandBus::class)->send(new ChangePriceOfOffersCommand(Actor::user($user->id), $ids, $kind, $value));
    }

    public function test_a_seller_changes_the_price_of_own_offers_without_re_approval_and_skips_the_rest(): void
    {
        $seller = UserModel::factory()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->approvedBefore()->ownedBy($seller->id)->priced(10000)->create();
        $withdrawn = OfferModelFactory::new()->sendable()->status(OfferStatus::Withdrawn)->ownedBy($seller->id)->priced(20000)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($seller->id)->priced(10000)->create();
        $sold = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($seller->id)->priced(10000)->create();
        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->priced(10000)->inTrash()->create();
        $unpriced = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $foreign = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->priced(10000)->create();

        $result = $this->change($seller, [$active->id, $withdrawn->id, $reserved->id, $sold->id, $trashed->id, $unpriced->id, $foreign->id], 'amount', 500);

        $this->assertSame(['done' => 2, 'skipped' => 5], $result);
        $this->assertSame(10500, $active->fresh()->price_grosze);
        $this->assertSame(OfferStatus::Active, $active->fresh()->status);
        $this->assertSame(20500, $withdrawn->fresh()->price_grosze);
        $this->assertSame(OfferStatus::Withdrawn, $withdrawn->fresh()->status);
        $this->assertFalse((bool) $active->fresh()->changed_since_approval);
        foreach ([$reserved, $sold, $trashed, $foreign] as $untouched) {
            $this->assertSame(10000, $untouched->fresh()->price_grosze);
        }
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_price_that_would_not_stay_above_zero_is_skipped(): void
    {
        $seller = UserModel::factory()->create();
        $cheap = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->priced(100)->create();
        $dear = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->priced(20000)->create();

        $half = $this->change($seller, [$cheap->id, $dear->id], 'percent', -5000);
        $this->assertSame(['done' => 2, 'skipped' => 0], $half);
        $this->assertSame(50, $cheap->fresh()->price_grosze);
        $this->assertSame(10000, $dear->fresh()->price_grosze);

        $all = $this->change($seller, [$cheap->id, $dear->id], 'amount', -10000);
        $this->assertSame(['done' => 0, 'skipped' => 2], $all);
        $this->assertSame(50, $cheap->fresh()->price_grosze);
        $this->assertSame(10000, $dear->fresh()->price_grosze);
    }

    public function test_a_fixed_price_replaces_the_price_and_an_amount_below_one_zloty_is_refused(): void
    {
        $seller = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($seller->id)->priced(10000)->create();

        $this->assertSame(['done' => 1, 'skipped' => 0], $this->change($seller, [$offer->id], 'fixed', 29900));
        $this->assertSame(29900, $offer->fresh()->price_grosze);

        $this->expectException(OfferRefused::class);

        $this->change($seller, [$offer->id], 'fixed', 50);
    }

    public function test_an_admin_changes_any_offer_and_a_foreign_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $foreign = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->priced(10000)->create();
        $own = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($admin->id)->priced(10000)->create();

        $result = $this->change($admin, [$foreign->id, $own->id], 'percent', 1000);

        $this->assertSame(['done' => 2, 'skipped' => 0], $result);
        $this->assertSame(11000, $foreign->fresh()->price_grosze);
        $this->assertSame(OfferStatus::Active, $foreign->fresh()->status);
        $this->assertSame(1, JournalEntryModel::query()->count());
        $this->assertSame($foreign->owner_id, JournalEntryModel::query()->sole()->user_id);
    }
}
