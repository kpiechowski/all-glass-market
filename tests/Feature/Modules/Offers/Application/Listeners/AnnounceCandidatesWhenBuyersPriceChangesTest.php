<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** §10, FR-28: a buyer's price change reaches adverts only for candidates. */
class AnnounceCandidatesWhenBuyersPriceChangesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: OfferStatus, 1: bool, 2: bool}> */
    public static function offers(): array
    {
        return [
            'Active' => [OfferStatus::Active, false, true],
            'Reserved' => [OfferStatus::Reserved, false, true],
            'Draft' => [OfferStatus::Draft, false, false],
            'Pending' => [OfferStatus::Pending, false, false],
            'Withdrawn' => [OfferStatus::Withdrawn, false, false],
            'Sold' => [OfferStatus::Sold, false, false],
            'trashed Active' => [OfferStatus::Active, true, false],
        ];
    }

    #[DataProvider('offers')]
    public function test_only_candidates_are_announced(OfferStatus $status, bool $trashed, bool $announced): void
    {
        $factory = OfferModelFactory::new()->status($status)->priced(10000);
        $offer = ($trashed ? $factory->inTrash() : $factory)->create();
        Event::fake([OfferChangedWhileCandidate::class]);

        event(new OfferBuyersPriceChanged($offer->id, 12300, 10000, CarbonImmutable::now()));

        if ($announced) {
            Event::assertDispatchedTimes(OfferChangedWhileCandidate::class, 1);
            Event::assertDispatched(OfferChangedWhileCandidate::class, fn (OfferChangedWhileCandidate $e): bool => $e->offerId === $offer->id);
        } else {
            Event::assertNotDispatched(OfferChangedWhileCandidate::class);
        }
    }
}
