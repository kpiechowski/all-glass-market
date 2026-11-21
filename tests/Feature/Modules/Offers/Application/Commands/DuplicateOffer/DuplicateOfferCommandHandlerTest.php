<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\DuplicateOffer;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\DuplicateOffer\DuplicateOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-27, RULE-07, D-32: the copy is a Draft of whoever duplicates, an admin included. */
class DuplicateOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function duplicate(Actor $actor, int $offerId): int
    {
        return app(CommandBus::class)->send(new DuplicateOfferCommand($actor, $offerId));
    }

    public function test_an_admin_duplicating_a_sellers_offer_owns_the_copy(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $seller = UserModel::factory()->create();
        $glassModel = GlassModelModelFactory::new()->create();
        $source = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->withGlassModel($glassModel->id)
            ->ownedBy($seller->id)->state(['description' => 'Opis', 'producer_id' => null, 'year' => 2019, 'private_notes' => 'Tajne', 'private_photos' => ['offers/private/'.$seller->id.'/x.jpg']])->create();

        $copyId = $this->duplicate(Actor::user($admin->id), $source->id);

        $copy = OfferModel::query()->findOrFail($copyId);
        $this->assertSame($admin->id, $copy->owner_id);
        $this->assertSame(OfferStatus::Draft, $copy->status);
        $this->assertNotSame($source->number, $copy->number);
        $this->assertSame($glassModel->id, $copy->glass_model_id);
        $this->assertSame('Opis', $copy->description);
        $this->assertSame(15000, $copy->price_grosze);
        $this->assertNull($copy->year);
        $this->assertNull($copy->condition);
        $this->assertNull($copy->rating);
        $this->assertNull($copy->private_notes);
        $this->assertSame([], $copy->public_photos);
        $this->assertSame([], $copy->private_photos);
        $this->assertSame($seller->id, $source->fresh()->owner_id);
        $this->assertSame(OfferStatus::Active, $source->fresh()->status);
        $this->assertSame(0, JournalEntryModel::query()->count());
    }

    public function test_a_seller_duplicating_their_own_offer_owns_the_copy_and_keeps_the_hint(): void
    {
        $seller = UserModel::factory()->create();
        $source = OfferModelFactory::new()->pending()->ownedBy($seller->id)->create();

        $copy = OfferModel::query()->findOrFail($this->duplicate(Actor::user($seller->id), $source->id));

        $this->assertSame($seller->id, $copy->owner_id);
        $this->assertSame('VW Golf 2015', $copy->identification_hint);
        $this->assertSame(OfferStatus::Draft, $copy->status);
    }

    public function test_a_seller_cannot_duplicate_another_sellers_offer(): void
    {
        $source = OfferModelFactory::new()->pending()->create();
        $stranger = UserModel::factory()->create();

        try {
            $this->duplicate(Actor::user($stranger->id), $source->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.not_yours', $refusal->translationKey);
        }

        $this->assertSame(1, OfferModel::query()->count());
    }

    public function test_an_offer_in_the_trash_is_not_duplicated(): void
    {
        $seller = UserModel::factory()->create();
        $source = OfferModelFactory::new()->pending()->ownedBy($seller->id)->inTrash()->create();

        $this->expectException(OfferRefused::class);

        $this->duplicate(Actor::user($seller->id), $source->id);
    }
}
