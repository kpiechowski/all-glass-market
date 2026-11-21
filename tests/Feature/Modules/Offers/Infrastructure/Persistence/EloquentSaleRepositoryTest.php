<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Repositories\SaleRepository;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentSaleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): SaleRepository
    {
        return app(SaleRepository::class);
    }

    public function test_it_finds_the_latest_sale_that_was_not_returned_and_skips_returned_and_other_offers_sales(): void
    {
        $offer = OfferModelFactory::new()->create();
        $other = OfferModelFactory::new()->create();
        $admin = UserModel::factory()->admin()->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();
        $current = SaleModelFactory::new()->forOffer($offer->id)->create();
        SaleModelFactory::new()->forOffer($other->id)->create();

        $this->assertSame($current->id, $this->repository()->findCurrentForOffer($offer->id)?->id());
    }

    public function test_it_finds_nothing_when_every_sale_was_returned(): void
    {
        $offer = OfferModelFactory::new()->create();
        $admin = UserModel::factory()->admin()->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();

        $this->assertNull($this->repository()->findCurrentForOffer($offer->id));
    }

    public function test_it_removes_every_sale_of_the_offer_only(): void
    {
        $offer = OfferModelFactory::new()->create();
        $other = OfferModelFactory::new()->create();
        $admin = UserModel::factory()->admin()->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();
        $kept = SaleModelFactory::new()->forOffer($other->id)->create();

        app(ActorStack::class)->run(Actor::user(1), fn () => $this->repository()->removeAllOfOffer($offer->id));

        $this->assertSame([$kept->id], SaleModel::query()->pluck('id')->all());
    }

    public function test_the_database_refuses_a_second_unreturned_sale_of_one_offer(): void
    {
        $offer = OfferModelFactory::new()->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->expectException(QueryException::class);

        SaleModelFactory::new()->forOffer($offer->id)->create();
    }

    public function test_the_database_allows_a_new_sale_after_the_earlier_one_was_returned(): void
    {
        $offer = OfferModelFactory::new()->create();
        $admin = UserModel::factory()->admin()->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->assertSame(3, SaleModel::query()->where('offer_id', $offer->id)->count());
    }
}
