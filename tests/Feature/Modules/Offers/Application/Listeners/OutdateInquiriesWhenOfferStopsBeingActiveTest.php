<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Modules\Offers\Application\Commands\DeleteOffer\DeleteOfferCommand;
use App\Modules\Offers\Application\Commands\RejectOffer\RejectOfferCommand;
use App\Modules\Offers\Application\Commands\ReserveOffer\ReserveOfferCommand;
use App\Modules\Offers\Application\Commands\SellOffer\SellOfferCommand;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\Application\Commands\WithdrawOffer\WithdrawOfferCommand;
use App\Modules\Offers\Domain\Enums\InquiryState;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Events\OfferStoppedBeingActive;
use App\Modules\Offers\Domain\Repositories\OfferRepository;
use App\Modules\Offers\Infrastructure\Factories\InquiryModelFactory;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\InquiryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** FR-23, D-41, D-66: whichever route takes an Offer out of Active, its open Inquiries close as Outdated. */
class OutdateInquiriesWhenOfferStopsBeingActiveTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{0: callable(self, UserModel, UserModel, int): object}> */
    public static function exitsFromActive(): array
    {
        return [
            'rejected' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new RejectOfferCommand(Actor::user($admin->id), $id, 'Powód')],
            'withdrawn' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new WithdrawOfferCommand(Actor::user($owner->id), $id)],
            'withdrawn by an admin' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new WithdrawOfferCommand(Actor::user($admin->id), $id)],
            'reserved' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new ReserveOfferCommand(Actor::user($owner->id), $id, 'Pan Kowalski')],
            'sold' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new SellOfferCommand(Actor::user($owner->id), $id, 'panel', 15000)],
            'deleted' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new DeleteOfferCommand(Actor::user($admin->id), $id)],
            'sent back to Pending' => [fn (self $t, UserModel $owner, UserModel $admin, int $id) => new UpdateOfferCommand(
                actor: Actor::user($owner->id),
                offerId: $id,
                identificationHint: 'VW Golf 2015',
                condition: 'used',
                rating: 3,
                description: 'Zmieniony opis',
                priceGrosze: 15000,
                publicPhotos: ['offers/public/1/a.jpg'],
                privatePhotos: [],
            )],
        ];
    }

    /** @param callable(self, UserModel, UserModel, int): object $command */
    #[DataProvider('exitsFromActive')]
    public function test_every_exit_from_active_outdates_the_open_inquiries_and_tells_their_senders(callable $command): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $asker = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $sent = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($asker->id)->create();
        $read = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($asker->id)->inState(InquiryState::Read)->create();
        $answered = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($asker->id)->inState(InquiryState::Rejected)->create();
        $foreign = InquiryModelFactory::new()->forOffer(OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create()->id)->create();

        app(CommandBus::class)->send($command($this, $owner, $admin, $offer->id));

        $this->assertSame(InquiryState::Outdated, $sent->fresh()->state);
        $this->assertSame(InquiryState::Outdated, $read->fresh()->state);
        $this->assertSame(InquiryState::Rejected, $answered->fresh()->state);
        $this->assertSame(InquiryState::Sent, $foreign->fresh()->state);
        $this->assertSame(2, JournalEntryModel::query()->where('user_id', $asker->id)->count());
    }

    public function test_the_sender_is_not_told_about_their_own_action(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->sentBy($admin->id)->create();

        app(CommandBus::class)->send(new RejectOfferCommand(Actor::user($admin->id), $offer->id, 'Powód'));

        $this->assertSame(InquiryState::Outdated, $inquiry->fresh()->state);
        $this->assertSame(0, JournalEntryModel::query()->where('user_id', $admin->id)->count());
    }

    public function test_moves_that_keep_the_offer_active_or_never_were_active_leave_inquiries_alone(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $inquiry = InquiryModelFactory::new()->forOffer($offer->id)->create();

        app(CommandBus::class)->send(new UpdateOfferCommand(
            actor: Actor::user($owner->id),
            offerId: $offer->id,
            identificationHint: 'VW Golf 2015',
            condition: 'used',
            rating: 3,
            description: null,
            priceGrosze: 20000,
            publicPhotos: ['offers/public/1/a.jpg'],
            privatePhotos: [],
        ));

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $this->assertSame(InquiryState::Sent, $inquiry->fresh()->state);
        $this->assertSame(1, InquiryModel::query()->count());
    }

    public function test_the_repository_announces_the_fact_once_with_the_actor_of_the_command(): void
    {
        Event::fake([OfferStoppedBeingActive::class]);
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();
        $actor = Actor::user($owner->id);

        app(ActorStack::class)->run($actor, function () use ($offer): void {
            $entity = app(OfferRepository::class)->get($offer->id);
            $entity->withdraw();
            app(OfferRepository::class)->save($entity);
            app(OfferRepository::class)->save($entity);
        });

        Event::assertDispatchedTimes(OfferStoppedBeingActive::class, 1);
        Event::assertDispatched(OfferStoppedBeingActive::class, fn (OfferStoppedBeingActive $event): bool => $event->offerId === $offer->id && $event->actor == $actor);
    }
}
