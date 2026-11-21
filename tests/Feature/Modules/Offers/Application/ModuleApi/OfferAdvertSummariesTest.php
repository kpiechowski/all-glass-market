<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\ModuleApi;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\ModuleApi\OfferAdvertSummaries;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** §10, RULE-12: the offer summary for adverts, read through the module interface; candidates only. */
class OfferAdvertSummariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_candidate_is_summarised_with_everything_an_advert_shows(): void
    {
        $owner = UserModel::factory()->create(['account_type' => 'private']);
        $model = GlassModelModelFactory::new()->withMainEurocode('2345AGSV')->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();
        $offer = OfferModelFactory::new()->status(OfferStatus::Active)->ownedBy($owner->id)->withGlassModel($model->id)
            ->used(4)->priced(10000)->withPublicPhotos(['offers/public/1/b.jpg', 'offers/public/1/a.jpg'])
            ->create(['producer_id' => $producer->id, 'year' => 2018, 'description' => 'Bez rys', 'private_notes' => 'Tajne']);

        $summary = app(OfferAdvertSummaries::class)->of($offer->id);

        $this->assertNotNull($summary);
        $this->assertSame($offer->id, $summary->offerId);
        $this->assertSame($offer->number, $summary->number);
        $this->assertSame($model->id, $summary->glassModel?->id);
        $this->assertSame('2345AGSV', $summary->glassModel->mainEurocode);
        $this->assertSame($producer->id, $summary->producerId);
        $this->assertSame('Pilkington', $summary->producer);
        $this->assertSame(2018, $summary->year);
        $this->assertSame('used', $summary->condition);
        $this->assertSame(4, $summary->rating);
        $this->assertFalse($summary->showsFiveStars);
        $this->assertSame('Bez rys', $summary->description);
        $this->assertSame(['offers/public/1/b.jpg', 'offers/public/1/a.jpg'], $summary->publicPhotos);
        $this->assertSame(12300, $summary->buyersPriceGrosze);
        $this->assertFalse($summary->reserved);
    }

    public function test_a_reserved_offer_is_flagged_and_a_new_piece_shows_five_stars(): void
    {
        $owner = UserModel::factory()->create(['account_type' => 'company']);
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)
            ->withGlassModel(GlassModelModelFactory::new()->create()->id)
            ->create(['condition' => OfferCondition::New, 'rating' => null]);

        $summary = app(OfferAdvertSummaries::class)->of($offer->id);

        $this->assertTrue($summary->reserved);
        $this->assertTrue($summary->showsFiveStars);
        $this->assertNull($summary->rating);
        $this->assertSame(15000, $summary->buyersPriceGrosze);
    }

    public function test_the_summary_leaves_out_what_is_not_given(): void
    {
        $offer = OfferModelFactory::new()->status(OfferStatus::Active)->create();

        $summary = app(OfferAdvertSummaries::class)->of($offer->id);

        $this->assertNull($summary->glassModel);
        $this->assertNull($summary->producer);
        $this->assertNull($summary->buyersPriceGrosze);
        $this->assertSame([], $summary->publicPhotos);
    }

    /** @return array<string, array{0: OfferStatus, 1: bool}> */
    public static function nonCandidates(): array
    {
        return [
            'Draft' => [OfferStatus::Draft, false],
            'Pending' => [OfferStatus::Pending, false],
            'Rejected' => [OfferStatus::Rejected, false],
            'Sold' => [OfferStatus::Sold, false],
            'Withdrawn' => [OfferStatus::Withdrawn, false],
            'trashed Active' => [OfferStatus::Active, true],
            'trashed Reserved' => [OfferStatus::Reserved, true],
        ];
    }

    #[DataProvider('nonCandidates')]
    public function test_it_answers_nothing_for_a_non_candidate(OfferStatus $status, bool $trashed): void
    {
        $factory = OfferModelFactory::new()->sendable()->status($status);
        $offer = ($trashed ? $factory->inTrash() : $factory)->create();

        $this->assertNull(app(OfferAdvertSummaries::class)->of($offer->id));
    }

    public function test_it_answers_nothing_for_an_unknown_offer(): void
    {
        $this->assertNull(app(OfferAdvertSummaries::class)->of(999999));
    }
}
