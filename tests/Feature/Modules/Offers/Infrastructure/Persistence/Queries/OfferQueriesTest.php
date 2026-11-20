<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Queries;

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
}
