<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Events;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\AcceptInquiry\AcceptInquiryCommand;
use App\Modules\Offers\Application\Commands\ApproveOffer\ApproveOfferCommand;
use App\Modules\Offers\Application\Commands\ApproveOffers\ApproveOffersCommand;
use App\Modules\Offers\Application\Commands\ChangeGlassModelOfOffers\ChangeGlassModelOfOffersCommand;
use App\Modules\Offers\Application\Commands\ChangePriceOfOffers\ChangePriceOfOffersCommand;
use App\Modules\Offers\Application\Commands\DeleteOffer\DeleteOfferCommand;
use App\Modules\Offers\Application\Commands\DeleteOffers\DeleteOffersCommand;
use App\Modules\Offers\Application\Commands\RejectOffer\RejectOfferCommand;
use App\Modules\Offers\Application\Commands\ReleaseReservation\ReleaseReservationCommand;
use App\Modules\Offers\Application\Commands\ReserveOffer\ReserveOfferCommand;
use App\Modules\Offers\Application\Commands\RestoreOffer\RestoreOfferCommand;
use App\Modules\Offers\Application\Commands\ResumeOffer\ResumeOfferCommand;
use App\Modules\Offers\Application\Commands\ReturnSale\ReturnSaleCommand;
use App\Modules\Offers\Application\Commands\SellOffer\SellOfferCommand;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffers\WithdrawOffersCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffersOfDeactivatedSeller\WithdrawOffersOfDeactivatedSellerCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Events\OfferBecameCandidate;
use App\Modules\Offers\Domain\Events\OfferChangedWhileCandidate;
use App\Modules\Offers\Domain\Events\OfferReserved;
use App\Modules\Offers\Domain\Events\OfferSold;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingCandidate;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\ReservationModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * §10, RULE-12: every route that moves an Offer in or out of the candidate state (Active or Reserved, outside the
 * Trash), or changes it while there, raises the one matching public event with the Offer id and nothing else.
 */
class OfferAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    private const array EVENTS = [
        OfferBecameCandidate::class,
        OfferChangedWhileCandidate::class,
        OfferReserved::class,
        OfferSold::class,
        OfferStoppedBeingCandidate::class,
    ];

    /**
     * @param  callable(UserModel, UserModel): OfferModel  $offer  owner, admin
     * @param  callable(UserModel, UserModel, OfferModel): object  $command
     */
    private function assertRaises(string $expected, callable $offer, callable $command): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $model = $offer($owner, $admin);
        Event::fake(self::EVENTS);

        app(CommandBus::class)->send($command($owner, $admin, $model));

        foreach (self::EVENTS as $event) {
            if ($event === $expected) {
                Event::assertDispatchedTimes($event, 1);
                Event::assertDispatched($event, fn (object $e): bool => $e->offerId === $model->id);
            } else {
                Event::assertNotDispatched($event);
            }
        }
    }

    private function offer(OfferStatus $status, int $ownerId, bool $trashed = false, bool $approvedBefore = false): OfferModel
    {
        $factory = OfferModelFactory::new()->sendable()->status($status)->ownedBy($ownerId)
            ->withGlassModel(GlassModelModelFactory::new()->create()->id);

        if ($trashed) {
            $factory = $factory->inTrash();
        }

        return ($approvedBefore ? $factory->approvedBefore() : $factory)->create();
    }

    /** @return array<string, array{0: string, 1: callable, 2: callable}> */
    public static function becameCandidate(): array
    {
        return [
            'approved' => [OfferBecameCandidate::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Pending, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ApproveOfferCommand(Actor::user($a->id), $m->id)],
            'approved in bulk' => [OfferBecameCandidate::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Pending, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ApproveOffersCommand(Actor::user($a->id), [$m->id])],
            'resumed' => [OfferBecameCandidate::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Withdrawn, $o->id, approvedBefore: true),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ResumeOfferCommand(Actor::user($o->id), $m->id)],
            'returned from a sale' => [OfferBecameCandidate::class,
                function (self $t, UserModel $o) {
                    $offer = $t->offer(OfferStatus::Sold, $o->id);
                    SaleModelFactory::new()->forOffer($offer->id)->create();

                    return $offer;
                },
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ReturnSaleCommand(Actor::user($a->id), $m->id, 'Klient zrezygnował')],
            'released from a reservation' => [OfferBecameCandidate::class,
                function (self $t, UserModel $o) {
                    $offer = $t->offer(OfferStatus::Reserved, $o->id);
                    ReservationModelFactory::new()->forOffer($offer->id)->madeBy($o->id, false)->create();

                    return $offer;
                },
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ReleaseReservationCommand(Actor::user($o->id), $m->id)],
            'restored into Active' => [OfferBecameCandidate::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id, trashed: true),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new RestoreOfferCommand(Actor::user($a->id), $m->id)],
            'restored into Reserved' => [OfferBecameCandidate::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Reserved, $o->id, trashed: true),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new RestoreOfferCommand(Actor::user($a->id), $m->id)],
        ];
    }

    #[DataProvider('becameCandidate')]
    public function test_becoming_a_candidate(string $expected, callable $offer, callable $command): void
    {
        $this->assertRaises($expected, fn ($o, $a) => $offer($this, $o), $command);
    }

    /** @return array<string, array{0: string, 1: callable, 2: callable}> */
    public static function reservedAndSold(): array
    {
        return [
            'reserved' => [OfferReserved::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ReserveOfferCommand(Actor::user($o->id), $m->id, 'Pan Kowalski')],
            'reserved by accepting an inquiry' => [OfferReserved::class,
                function (self $t, UserModel $o) {
                    $offer = $t->offer(OfferStatus::Active, $o->id);
                    InquiryModelFactory::new()->forOffer($offer->id)->sentBy(UserModel::factory()->admin()->create()->id)->create();

                    return $offer;
                },
                fn (UserModel $o, UserModel $a, OfferModel $m) => new AcceptInquiryCommand(Actor::user($o->id), InquiryModel::query()->sole()->id)],
            'sold from Active' => [OfferSold::class,
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new SellOfferCommand(Actor::user($o->id), $m->id, 'panel', 15000)],
            'sold from Reserved' => [OfferSold::class,
                function (self $t, UserModel $o) {
                    $offer = $t->offer(OfferStatus::Reserved, $o->id);
                    ReservationModelFactory::new()->forOffer($offer->id)->madeBy($o->id, false)->create();

                    return $offer;
                },
                fn (UserModel $o, UserModel $a, OfferModel $m) => new SellOfferCommand(Actor::user($o->id), $m->id, 'panel', 15000)],
        ];
    }

    #[DataProvider('reservedAndSold')]
    public function test_reserved_and_sold(string $expected, callable $offer, callable $command): void
    {
        $this->assertRaises($expected, fn ($o, $a) => $offer($this, $o), $command);
    }

    /** @return array<string, array{0: string, 1: callable, 2: callable}> */
    public static function stoppedBeingCandidate(): array
    {
        $active = fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id);

        return [
            'rejected from Active' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new RejectOfferCommand(Actor::user($a->id), $m->id, 'Powód')],
            'withdrawn by the owner' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new WithdrawOfferCommand(Actor::user($o->id), $m->id)],
            'withdrawn by an admin' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new WithdrawOfferCommand(Actor::user($a->id), $m->id)],
            'withdrawn in bulk' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new WithdrawOffersCommand(Actor::user($o->id), [$m->id])],
            'withdrawn by the System, seller deactivated' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new WithdrawOffersOfDeactivatedSellerCommand(Actor::system('seller deactivated'), $o->id)],
            'moved to the Trash' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new DeleteOfferCommand(Actor::user($a->id), $m->id)],
            'moved to the Trash in bulk' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => new DeleteOffersCommand(Actor::user($a->id), [$m->id])],
            'seller edit back to Pending' => [OfferStoppedBeingCandidate::class, $active,
                fn (UserModel $o, UserModel $a, OfferModel $m) => self::update($o, $m, description: 'Zmieniony opis')],
        ];
    }

    #[DataProvider('stoppedBeingCandidate')]
    public function test_stopping_being_a_candidate(string $expected, callable $offer, callable $command): void
    {
        $this->assertRaises($expected, fn ($o, $a) => $offer($this, $o), $command);
    }

    /**
     * @param  list<string>|null  $publicPhotos
     */
    private static function update(UserModel $by, OfferModel $offer, ?string $description = null, ?int $priceGrosze = null, ?array $publicPhotos = null, ?string $privateNotes = null, ?int $producerId = null): UpdateOfferCommand
    {
        return new UpdateOfferCommand(
            actor: Actor::user($by->id),
            offerId: $offer->id,
            glassModelId: $offer->glass_model_id,
            condition: 'used',
            rating: 3,
            producerId: $producerId,
            description: $description ?? $offer->description,
            privateNotes: $privateNotes,
            priceGrosze: $priceGrosze ?? $offer->price_grosze,
            publicPhotos: $publicPhotos ?? $offer->public_photos,
            privatePhotos: [],
        );
    }

    /** @return array<string, array{0: callable, 1: callable}> */
    public static function changedWhileCandidate(): array
    {
        return [
            'an admin edit of the content' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => self::update($a, $m, description: 'Nowy opis')],
            'a seller price change' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => self::update($o, $m, priceGrosze: 20000)],
            'an admin edit of the public photos' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => self::update($a, $m, publicPhotos: ['offers/public/1/a.jpg', 'offers/public/'.$a->id.'/b.jpg'])],
            'a change of a Reserved Offer' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Reserved, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => self::update($a, $m, description: 'Nowy opis')],
            'a single price change' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ChangePriceOfOffersCommand(Actor::user($o->id), [$m->id], 'fixed', 99000)],
            'a bulk price change' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ChangePriceOfOffersCommand(Actor::user($a->id), [$m->id], 'percent', 1000)],
            'a Glass model change' => [
                fn (self $t, UserModel $o) => $t->offer(OfferStatus::Active, $o->id),
                fn (UserModel $o, UserModel $a, OfferModel $m) => new ChangeGlassModelOfOffersCommand(Actor::user($a->id), [$m->id], GlassModelModelFactory::new()->create()->id)],
        ];
    }

    #[DataProvider('changedWhileCandidate')]
    public function test_changing_while_a_candidate(callable $offer, callable $command): void
    {
        $this->assertRaises(OfferChangedWhileCandidate::class, fn ($o, $a) => $offer($this, $o), $command);
    }

    /** @return array<string, array{0: OfferStatus, 1: bool}> */
    public static function nonCandidates(): array
    {
        return [
            'Draft' => [OfferStatus::Draft, false],
            'Pending' => [OfferStatus::Pending, false],
            'Withdrawn' => [OfferStatus::Withdrawn, false],
            'Rejected' => [OfferStatus::Rejected, false],
            'trashed Active' => [OfferStatus::Active, true],
        ];
    }

    #[DataProvider('nonCandidates')]
    public function test_a_change_of_a_non_candidate_announces_nothing(OfferStatus $status, bool $trashed): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = $this->offer($status, $owner->id, $trashed);
        Event::fake(self::EVENTS);

        $glassModelId = GlassModelModelFactory::new()->create()->id;
        app(CommandBus::class)->send(new ChangeGlassModelOfOffersCommand(Actor::user($admin->id), [$offer->id], $glassModelId));
        app(CommandBus::class)->send(new ChangePriceOfOffersCommand(Actor::user($admin->id), [$offer->id], 'fixed', 77000));

        foreach (self::EVENTS as $event) {
            Event::assertNotDispatched($event);
        }
    }

    public function test_private_notes_and_a_pure_no_op_edit_announce_nothing(): void
    {
        $owner = UserModel::factory()->create();
        $offer = $this->offer(OfferStatus::Active, $owner->id);
        Event::fake(self::EVENTS);

        app(CommandBus::class)->send(self::update($owner, $offer, privateNotes: 'Tylko dla mnie'));
        app(CommandBus::class)->send(self::update($owner, $offer->fresh()));

        foreach (self::EVENTS as $event) {
            Event::assertNotDispatched($event);
        }
    }

    public function test_active_to_reserved_is_not_stopping_being_a_candidate(): void
    {
        $owner = UserModel::factory()->create();
        $offer = $this->offer(OfferStatus::Active, $owner->id);
        Event::fake(self::EVENTS);

        app(CommandBus::class)->send(new ReserveOfferCommand(Actor::user($owner->id), $offer->id, 'Pan Kowalski'));

        Event::assertNotDispatched(OfferStoppedBeingCandidate::class);
    }
}
