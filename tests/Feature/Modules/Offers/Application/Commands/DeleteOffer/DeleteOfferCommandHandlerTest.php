<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\DeleteOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\DeleteOffer\DeleteOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Domain\Enums\JournalSeverity;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-25, FR-34, D-43: delete moves the Offer to the Trash and keeps its status. */
class DeleteOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function deleteOffer(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new DeleteOfferCommand($actor, $offerId));
    }

    public function test_an_admin_deletes_an_offer_of_any_status_but_reserved_into_the_trash_keeping_the_status(): void
    {
        $admin = UserModel::factory()->admin()->create();

        foreach (array_filter(OfferStatus::cases(), fn (OfferStatus $status): bool => $status !== OfferStatus::Reserved) as $status) {
            $offer = OfferModelFactory::new()->sendable()->status($status)->create();

            $this->deleteOffer(Actor::user($admin->id), $offer->id);

            $fresh = $offer->fresh();
            $this->assertNotNull($fresh->trashed_at, $status->value);
            $this->assertSame($status, $fresh->status);
        }
    }

    public function test_the_owner_is_told_in_the_journal(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->ownedBy($owner->id)->create();

        $this->deleteOffer(Actor::user($admin->id), $offer->id);

        $entry = JournalEntryModel::query()->where('subject_id', $offer->id)->sole();
        $this->assertSame($owner->id, $entry->user_id);
        $this->assertSame(JournalSeverity::Info, $entry->severity);
    }

    public function test_an_admin_deleting_their_own_offer_gets_no_journal_entry(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($admin->id)->create();

        $this->deleteOffer(Actor::user($admin->id), $offer->id);

        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_seller_cannot_delete_even_their_own_offer(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->create();

        try {
            $this->deleteOffer(Actor::user($owner->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (AuthorizationException) {
            $this->assertNull($offer->fresh()->trashed_at);
        }
    }

    public function test_an_offer_already_in_the_trash_is_refused(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->inTrash()->create();

        try {
            $this->deleteOffer(Actor::user($admin->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.cannot_be_trashed', $refusal->translationKey);
        }

        $this->assertSame(1, OfferModel::query()->count());
    }

    public function test_a_reserved_offer_cannot_be_deleted(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Reserved)->create();

        try {
            $this->deleteOffer(Actor::user($admin->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.reserved_cannot_be_trashed', $refusal->translationKey);
        }

        $this->assertNull($offer->fresh()->trashed_at);
    }
}
