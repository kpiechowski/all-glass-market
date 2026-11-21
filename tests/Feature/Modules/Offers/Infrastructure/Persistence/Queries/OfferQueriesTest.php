<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Queries\OfferQueries;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seller_gets_only_their_own_offers(): void
    {
        $seller = UserModel::factory()->create();
        $mine = OfferModelFactory::new()->ownedBy($seller->id)->create();
        OfferModelFactory::new()->ownedBy(UserModel::factory()->create()->id)->create();

        $ids = (new OfferQueries)->visibleTo($seller->id, managesAll: false)->pluck('id')->all();

        $this->assertSame([$mine->id], $ids);
    }

    public function test_whoever_manages_all_offers_gets_every_offer(): void
    {
        $admin = UserModel::factory()->admin()->create();
        OfferModelFactory::new()->count(2)->create();

        $this->assertCount(2, (new OfferQueries)->visibleTo($admin->id, managesAll: true)->get());
    }

    public function test_the_approval_queue_holds_pending_offers_only(): void
    {
        $pending = OfferModelFactory::new()->pending()->create();
        OfferModelFactory::new()->create();
        OfferModelFactory::new()->status(OfferStatus::Rejected)->create();
        OfferModelFactory::new()->status(OfferStatus::Active)->create();

        $this->assertSame([$pending->id], (new OfferQueries)->pendingApproval()->pluck('id')->all());
    }

    public function test_a_seller_never_gets_an_offer_in_the_trash_but_an_admin_can_open_it(): void
    {
        $seller = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $live = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $trashed = OfferModelFactory::new()->ownedBy($seller->id)->inTrash()->create();

        $this->assertSame([$live->id], (new OfferQueries)->visibleTo($seller->id, managesAll: false)->pluck('id')->all());
        $this->assertEqualsCanonicalizing([$live->id, $trashed->id], (new OfferQueries)->visibleTo($admin->id, managesAll: true)->pluck('id')->all());
    }

    public function test_outside_the_trash_and_only_the_trash_split_the_offers(): void
    {
        $live = OfferModelFactory::new()->create();
        $trashed = OfferModelFactory::new()->inTrash()->create();
        $queries = new OfferQueries;

        $this->assertSame([$live->id], $queries->outsideTrash(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all());
        $this->assertSame([$trashed->id], $queries->onlyTrash(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all());
    }

    public function test_a_pending_offer_in_the_trash_is_not_in_the_approval_queue(): void
    {
        $pending = OfferModelFactory::new()->pending()->create();
        OfferModelFactory::new()->pending()->inTrash()->create();

        $this->assertSame([$pending->id], (new OfferQueries)->pendingApproval()->pluck('id')->all());
        $this->assertSame(
            [$pending->id],
            (new OfferQueries)->onlyPending(OfferModelFactory::new()->newModel()->newQuery())->pluck('id')->all(),
        );
    }
}
