<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\DeleteOfferPermanently;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\DeleteOfferPermanently\DeleteOfferPermanentlyCommand;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** FR-26, D-43: from the Trash only; the Offer and the files of both photo lists go. */
class DeleteOfferPermanentlyCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function deletePermanently(Actor $actor, int $offerId): void
    {
        app(CommandBus::class)->send(new DeleteOfferPermanentlyCommand($actor, $offerId));
    }

    public function test_an_admin_removes_an_offer_in_the_trash_with_both_photo_lists(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('offers/public/4/a.jpg', 'x');
        Storage::disk('public')->put('offers/public/4/other.jpg', 'x');
        Storage::disk('local')->put('offers/private/4/p.jpg', 'x');

        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->inTrash()
            ->withPublicPhotos(['offers/public/4/a.jpg'])
            ->withPrivatePhotos(['offers/private/4/p.jpg'])
            ->create();

        $this->deletePermanently(Actor::user($admin->id), $offer->id);

        $this->assertNull(OfferModel::query()->find($offer->id));
        Storage::disk('public')->assertMissing('offers/public/4/a.jpg');
        Storage::disk('local')->assertMissing('offers/private/4/p.jpg');
        Storage::disk('public')->assertExists('offers/public/4/other.jpg');
    }

    public function test_an_offer_outside_the_trash_is_refused_and_keeps_its_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('offers/public/4/a.jpg', 'x');

        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->withPublicPhotos(['offers/public/4/a.jpg'])->create();

        try {
            $this->deletePermanently(Actor::user($admin->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.cannot_be_deleted_permanently', $refusal->translationKey);
        }

        $this->assertNotNull(OfferModel::query()->find($offer->id));
        Storage::disk('public')->assertExists('offers/public/4/a.jpg');
    }

    public function test_a_seller_cannot_delete_permanently(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->ownedBy($owner->id)->inTrash()->create();

        try {
            $this->deletePermanently(Actor::user($owner->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (AuthorizationException) {
            $this->assertNotNull(OfferModel::query()->find($offer->id));
        }
    }
}
