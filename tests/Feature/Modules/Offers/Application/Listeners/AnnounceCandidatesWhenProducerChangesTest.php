<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Events\ProducerChanged;
use App\Modules\Catalogue\Domain\Events\ProducerWithdrawn;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** §9, §10: a producer change in Catalogue reaches adverts of its candidate Offers. */
class AnnounceCandidatesWhenProducerChangesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: class-string}> */
    public static function producerEvents(): array
    {
        return [
            'changed' => [ProducerChanged::class],
            'withdrawn' => [ProducerWithdrawn::class],
        ];
    }

    /** @param class-string $event */
    #[DataProvider('producerEvents')]
    public function test_a_producer_event_announces_its_candidates_only(string $event): void
    {
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        $other = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create(['producer_id' => $producer->id]);
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->sendable()->status(OfferStatus::Pending)->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create(['producer_id' => $producer->id]);
        OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create(['producer_id' => $other->id]);
        Event::fake([OfferChangedWhileCandidate::class]);

        event(new $event($producer->id, CarbonImmutable::now()));

        Event::assertDispatchedTimes(OfferChangedWhileCandidate::class, 2);
        Event::assertDispatched(OfferChangedWhileCandidate::class, fn (OfferChangedWhileCandidate $e): bool => $e->offerId === $active->id);
        Event::assertDispatched(OfferChangedWhileCandidate::class, fn (OfferChangedWhileCandidate $e): bool => $e->offerId === $reserved->id);
    }
}
