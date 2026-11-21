<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ReleaseReservation;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\ReleaseReservation\ReleaseReservationCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Enums\ReservationEnd;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-17, FR-34, RULE-05, RULE-10: the seller-vs-admin lock. */
class ReleaseReservationCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function release(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new ReleaseReservationCommand($actor, $offerId));
    }

    private function refusalKey(callable $action): string
    {
        try {
            $action();
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_the_owner_releases_a_reservation_made_by_a_seller(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($owner->id, false)->create();

        $this->release(Actor::user($owner->id), $offer->id);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $reservation = ReservationModel::query()->sole();
        $this->assertSame(ReservationEnd::Released, $reservation->ended_as);
        $this->assertNotNull($reservation->ended_at);
        $this->assertNull($reservation->is_current);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_an_admin_releases_any_reservation_and_the_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create();

        $this->release(Actor::user($admin->id), $offer->id);

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame($owner->id, JournalEntryModel::query()->where('subject_id', $offer->id)->sole()->user_id);
    }

    public function test_the_owner_cannot_release_an_admins_reservation(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->madeBy($admin->id, true)->create();

        $this->assertSame('offers::exceptions.reservation_is_locked', $this->refusalKey(fn () => $this->release(Actor::user($owner->id), $offer->id)));

        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $this->assertTrue(ReservationModel::query()->sole()->is_current);
    }

    public function test_an_offer_that_is_not_reserved_cannot_be_released(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_released', $this->refusalKey(fn () => $this->release(Actor::user($owner->id), $offer->id)));
    }

    public function test_another_seller_cannot_release_it(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->create();
        $stranger = UserModel::factory()->create();

        $this->assertSame('offers::exceptions.not_yours', $this->refusalKey(fn () => $this->release(Actor::user($stranger->id), $offer->id)));
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();

        $this->expectException(AuthorizationException::class);

        $this->release(Actor::system('test'), $offer->id);
    }
}
