<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Domain\Entities\Inquiry;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Repositories\InquiryRepository;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentInquiryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private function inWrite(callable $callback): mixed
    {
        return app(ActorStack::class)->run(Actor::user(UserModel::factory()->create()->id), $callback);
    }

    public function test_an_inquiry_is_added_loaded_and_saved(): void
    {
        $offer = OfferModelFactory::new()->create();
        $admin = UserModel::factory()->admin()->create();
        $repository = app(InquiryRepository::class);

        $id = $this->inWrite(fn () => $repository->add(Inquiry::send($offer->id, $admin->id, null, 'Notatka', CarbonImmutable::now(), true)));
        $inquiry = $repository->get($id);
        $inquiry->reject(CarbonImmutable::now());
        $this->inWrite(fn () => $repository->save($inquiry));

        $this->assertSame(InquiryState::Rejected, InquiryModel::query()->findOrFail($id)->state);
    }

    public function test_the_open_inquiries_of_an_offer_are_the_sent_and_read_ones_only(): void
    {
        $offer = OfferModelFactory::new()->create();
        $other = OfferModelFactory::new()->create();
        $sent = InquiryModelFactory::new()->forOffer($offer->id)->create();
        $read = InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Read)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Accepted)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Outdated)->create();
        InquiryModelFactory::new()->forOffer($other->id)->create();

        $open = app(InquiryRepository::class)->openOfOffer($offer->id);

        $this->assertSame([$sent->id, $read->id], array_map(fn (Inquiry $inquiry): ?int => $inquiry->id(), $open));
    }

    public function test_every_inquiry_of_an_offer_can_be_removed_and_others_stay(): void
    {
        $offer = OfferModelFactory::new()->create();
        $kept = InquiryModelFactory::new()->create();
        InquiryModelFactory::new()->forOffer($offer->id)->create();
        InquiryModelFactory::new()->forOffer($offer->id)->inState(InquiryState::Accepted)->create();

        $this->inWrite(fn () => app(InquiryRepository::class)->removeAllOfOffer($offer->id));

        $this->assertSame([$kept->id], InquiryModel::query()->pluck('id')->all());
    }
}
