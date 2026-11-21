<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers\ChangeGlassModelOfOffersCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-14, FR-34, RULE-14, D-44: an admin changes the Glass model singly or in bulk; the status stays. */
class ChangeGlassModelOfOffersCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    /** @param  list<int>  $ids */
    private function change(UserModel $user, array $ids, int $glassModelId): array
    {
        return app(CommandBus::class)->send(new ChangeGlassModelOfOffersCommand(Actor::user($user->id), $ids, $glassModelId));
    }

    public function test_an_admin_changes_the_model_in_every_status_keeps_it_and_each_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $old = GlassModelModelFactory::new()->create();
        $new = GlassModelModelFactory::new()->create();
        $statuses = [OfferStatus::Pending, OfferStatus::Active, OfferStatus::Reserved, OfferStatus::Sold, OfferStatus::Withdrawn];
        $offers = array_map(fn (OfferStatus $status) => OfferModelFactory::new()->sendable()->status($status)->withGlassModel($old->id)->create(), $statuses);
        $hinted = OfferModelFactory::new()->pending()->create();
        $own = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($old->id)->ownedBy($admin->id)->create();

        $ids = array_map(fn ($offer) => $offer->id, [...$offers, $hinted, $own]);
        $result = $this->change($admin, $ids, $new->id);

        $this->assertSame(['done' => 7, 'skipped' => 0], $result);
        foreach ($offers as $index => $offer) {
            $this->assertSame($new->id, $offer->fresh()->glass_model_id);
            $this->assertSame($statuses[$index], $offer->fresh()->status);
        }
        $this->assertSame($new->id, $hinted->fresh()->glass_model_id);
        $this->assertNull($hinted->fresh()->identification_hint);
        $this->assertSame(OfferStatus::Pending, $hinted->fresh()->status);
        $this->assertSame(6, JournalEntryModel::query()->count());
        $this->assertFalse(JournalEntryModel::query()->where('user_id', $admin->id)->exists());
    }

    public function test_trashed_unknown_and_already_matching_offers_are_skipped(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $model = GlassModelModelFactory::new()->create();
        $trashed = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create();
        $same = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($model->id)->create();

        $result = $this->change($admin, [$trashed->id, $same->id, 999999], $model->id);

        $this->assertSame(['done' => 0, 'skipped' => 3], $result);
        $this->assertNull($trashed->fresh()->glass_model_id);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_withdrawn_or_unknown_glass_model_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->pending()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();

        foreach ([$withdrawn->id, 999999] as $glassModelId) {
            try {
                $this->change($admin, [$offer->id], $glassModelId);
                $this->fail('Expected a refusal.');
            } catch (OfferRefused $refusal) {
                $this->assertSame('offers::exceptions.glass_model_cannot_be_picked', $refusal->translationKey);
            }
        }

        $this->assertNull($offer->fresh()->glass_model_id);
    }

    public function test_a_seller_is_rejected(): void
    {
        $seller = UserModel::factory()->create();
        $model = GlassModelModelFactory::new()->create();
        $offer = OfferModelFactory::new()->pending()->ownedBy($seller->id)->create();

        $this->expectException(AuthorizationException::class);

        $this->change($seller, [$offer->id], $model->id);
    }
}
