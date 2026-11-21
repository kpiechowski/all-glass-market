<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence\Queries;

use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Queries\InquiryQueries;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_seller_gets_the_inquiries_on_their_own_offers_outside_the_trash_only(): void
    {
        $seller = UserModel::factory()->create();
        $own = OfferModelFactory::new()->ownedBy($seller->id)->create();
        $trashed = OfferModelFactory::new()->ownedBy($seller->id)->inTrash()->create();
        $mine = InquiryModelFactory::new()->forOffer($own->id)->create();
        InquiryModelFactory::new()->forOffer($trashed->id)->create();
        InquiryModelFactory::new()->create();

        $this->assertSame([$mine->id], (new InquiryQueries)->visibleTo($seller->id, managesAll: false)->pluck('id')->all());
    }

    public function test_whoever_manages_all_offers_gets_every_inquiry(): void
    {
        $one = InquiryModelFactory::new()->create();
        $two = InquiryModelFactory::new()->inState(InquiryState::Outdated)->create();

        $this->assertEqualsCanonicalizing([$one->id, $two->id], (new InquiryQueries)->visibleTo(1, managesAll: true)->pluck('id')->all());
    }

    public function test_the_open_inquiries_of_an_offer_come_with_their_senders_oldest_first(): void
    {
        $offer = OfferModelFactory::new()->create();
        $first = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $second = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Read)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Rejected)->create();

        $open = (new InquiryQueries)->openOfOffer($offer->id)->get();

        $this->assertSame([$first->id, $second->id], $open->pluck('id')->all());
        $this->assertTrue($open->first()->relationLoaded('sender'));
    }
}
