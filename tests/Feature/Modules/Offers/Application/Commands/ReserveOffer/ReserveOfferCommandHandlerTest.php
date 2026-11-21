<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ReserveOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\ReserveOffer\ReserveOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\ReservationModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-16, FR-34, RULE-04, RULE-05, RULE-10. */
class ReserveOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function reserve(Actor $actor, int $offerId, string $note = 'Pan Kowalski'): void
    {
        app(CommandBus::class)->send(new ReserveOfferCommand($actor, $offerId, $note));
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

    public function test_the_owner_reserves_an_active_offer_and_the_reservation_shows_author_note_and_time(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->reserve(Actor::user($owner->id), $offer->id, ' Pan Kowalski, tel. 500 ');

        $this->assertSame(OfferStatus::Reserved, $offer->fresh()->status);
        $reservation = ReservationModel::query()->sole();
        $this->assertSame($offer->id, $reservation->offer_id);
        $this->assertSame($owner->id, $reservation->author_id);
        $this->assertFalse($reservation->made_by_admin);
        $this->assertSame('Pan Kowalski, tel. 500', $reservation->note);
        $this->assertNotNull($reservation->made_at);
        $this->assertTrue($reservation->is_current);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_an_admins_reservation_is_marked_as_an_admins_and_the_owner_is_told(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->reserve(Actor::user($admin->id), $offer->id);

        $this->assertTrue(ReservationModel::query()->sole()->made_by_admin);
        $this->assertSame($owner->id, JournalEntryModel::query()->where('subject_id', $offer->id)->sole()->user_id);
    }

    public function test_an_offer_that_is_not_active_is_refused_and_nothing_is_recorded(): void
    {
        $owner = UserModel::factory()->create();

        foreach ([OfferStatus::Draft, OfferStatus::Pending, OfferStatus::Reserved, OfferStatus::Sold, OfferStatus::Withdrawn] as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->ownedBy($owner->id)->create();

            $this->assertSame('offers::exceptions.cannot_be_reserved', $this->refusalKey(fn () => $this->reserve(Actor::user($owner->id), $offer->id)), $status->value);
        }

        $this->assertSame(0, ReservationModel::query()->count());
    }

    public function test_an_offer_has_at_most_one_current_reservation(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        ReservationModelFactory::new()->forOffer($offer->id)->create();

        $this->assertSame('offers::exceptions.cannot_be_reserved', $this->refusalKey(fn () => $this->reserve(Actor::user($owner->id), $offer->id)));
        $this->assertSame(1, ReservationModel::query()->count());
    }

    public function test_the_note_is_required_and_the_status_stays_when_it_is_missing(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->assertSame('offers::exceptions.reservation_note_required', $this->refusalKey(fn () => $this->reserve(Actor::user($owner->id), $offer->id, '  ')));
        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
    }

    public function test_a_seller_cannot_reserve_another_sellers_offer(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();
        $stranger = UserModel::factory()->create();

        $this->assertSame('offers::exceptions.not_yours', $this->refusalKey(fn () => $this->reserve(Actor::user($stranger->id), $offer->id)));
    }

    public function test_an_offer_in_the_trash_cannot_be_reserved(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->inTrash()->create();

        $this->assertSame('offers::exceptions.is_in_trash', $this->refusalKey(fn () => $this->reserve(Actor::user($admin->id), $offer->id)));
    }

    public function test_a_system_actor_without_a_person_is_rejected(): void
    {
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->expectException(AuthorizationException::class);

        $this->reserve(Actor::system('test'), $offer->id);
    }
}
