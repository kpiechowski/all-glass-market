<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Events\GlassModelRestored;
use App\Modules\Catalogue\Domain\Events\GlassModelWithdrawn;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** §9, §10: a Glass model change in Catalogue reaches adverts of its candidate Offers. */
class AnnounceCandidatesWhenGlassModelChangesTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: class-string}> */
    public static function glassModelEvents(): array
    {
        return [
            'changed' => [GlassModelChanged::class],
            'withdrawn' => [GlassModelWithdrawn::class],
            'restored' => [GlassModelRestored::class],
        ];
    }

    /** @param class-string $event */
    #[DataProvider('glassModelEvents')]
    public function test_a_glass_model_event_announces_its_candidates_only(string $event): void
    {
        $model = GlassModelModelFactory::new()->create();
        $other = GlassModelModelFactory::new()->create();
        $active = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($model->id)->create();
        $reserved = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->withGlassModel($model->id)->create();
        foreach ([OfferStatus::Draft, OfferStatus::Pending, OfferStatus::Withdrawn, OfferStatus::Sold, OfferStatus::Rejected] as $status) {
            OfferModelFactory::new()->sendable()->status($status)->withGlassModel($model->id)->create();
        }
        OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($model->id)->inTrash()->create();
        OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($other->id)->create();
        Event::fake([OfferChangedWhileCandidate::class]);

        event(new $event($model->id, CarbonImmutable::now()));

        Event::assertDispatchedTimes(OfferChangedWhileCandidate::class, 2);
        Event::assertDispatched(OfferChangedWhileCandidate::class, fn (OfferChangedWhileCandidate $e): bool => $e->offerId === $active->id);
        Event::assertDispatched(OfferChangedWhileCandidate::class, fn (OfferChangedWhileCandidate $e): bool => $e->offerId === $reserved->id);
    }
}
