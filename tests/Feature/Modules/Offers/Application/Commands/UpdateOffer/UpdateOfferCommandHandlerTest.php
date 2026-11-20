<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\UpdateOffer;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\UpdateOffer\UpdateOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-06 (a Draft keeps its status when saved), RULE-02, RULE-07, RULE-11, RULE-13. */
class UpdateOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, int $offerId, ?int $glassModelId = null, ?string $hint = null, ?int $producerId = null, ?string $condition = null, ?int $rating = null, ?string $description = 'Nowy opis'): void
    {
        app(CommandBus::class)->send(new UpdateOfferCommand(
            actor: $actor,
            offerId: $offerId,
            glassModelId: $glassModelId,
            identificationHint: $hint,
            producerId: $producerId,
            year: 2020,
            condition: $condition,
            rating: $rating,
            description: $description,
            privateNotes: null,
            priceGrosze: 9900,
        ));
    }

    private function assertRefused(string $key, callable $action): void
    {
        try {
            $action();
        } catch (OfferRefused $exception) {
            $this->assertSame('offers::exceptions.'.$key, $exception->translationKey);

            return;
        }

        $this->fail('Expected a refusal: '.$key);
    }

    public function test_the_owner_edits_their_draft_and_it_stays_a_draft_with_its_number_and_owner(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create(['number' => 'AG00000005']);
        $glassModel = GlassModelModelFactory::new()->create();

        $this->send(Actor::user($owner->id), $offer->id, glassModelId: $glassModel->id, condition: 'outlet', rating: 2);

        $fresh = $offer->fresh();
        $this->assertSame($glassModel->id, $fresh->glass_model_id);
        $this->assertSame('Nowy opis', $fresh->description);
        $this->assertSame(9900, $fresh->price_grosze);
        $this->assertSame(OfferStatus::Draft, $fresh->status);
        $this->assertSame('AG00000005', $fresh->number);
        $this->assertSame($owner->id, $fresh->owner_id);
    }

    public function test_an_admin_edits_another_sellers_offer_without_taking_it_over(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        $this->send(Actor::user($admin->id), $offer->id, hint: 'OEM 5');

        $fresh = $offer->fresh();
        $this->assertSame('OEM 5', $fresh->identification_hint);
        $this->assertSame($owner->id, $fresh->owner_id);
    }

    public function test_another_sellers_offer_is_refused_and_left_alone(): void
    {
        $offer = OfferModelFactory::new()->withHint('stare')->create();
        $stranger = UserModel::factory()->create();

        $this->assertRefused('not_yours', fn () => $this->send(Actor::user($stranger->id), $offer->id, hint: 'nowe'));
        $this->assertSame('stare', $offer->fresh()->identification_hint);
    }

    public function test_switching_from_a_hint_to_a_glass_model_clears_the_hint(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withHint('stare')->create();
        $glassModel = GlassModelModelFactory::new()->create();

        $this->send(Actor::user($owner->id), $offer->id, glassModelId: $glassModel->id);

        $this->assertNull($offer->fresh()->identification_hint);
    }

    public function test_a_glass_model_and_a_hint_together_are_refused(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();
        $glassModel = GlassModelModelFactory::new()->create();

        $this->assertRefused('glass_model_and_hint_together', fn () => $this->send(Actor::user($owner->id), $offer->id, glassModelId: $glassModel->id, hint: 'OEM'));
    }

    public function test_a_rating_on_a_new_piece_is_refused(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        $this->assertRefused('rating_not_for_new', fn () => $this->send(Actor::user($owner->id), $offer->id, condition: 'new', rating: 3));
    }

    public function test_a_withdrawn_glass_model_cannot_be_newly_chosen_but_the_one_already_on_the_offer_stays(): void
    {
        $owner = UserModel::factory()->create();
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withGlassModel($withdrawn->id)->create();

        $this->send(Actor::user($owner->id), $offer->id, glassModelId: $withdrawn->id);
        $this->assertSame($withdrawn->id, $offer->fresh()->glass_model_id);

        $other = GlassModelModelFactory::new()->withdrawn()->create();
        $this->assertRefused('glass_model_cannot_be_picked', fn () => $this->send(Actor::user($owner->id), $offer->id, glassModelId: $other->id));
    }

    public function test_a_withdrawn_producer_the_offer_already_has_stays_but_cannot_be_newly_chosen(): void
    {
        $owner = UserModel::factory()->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->withdrawn()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create(['producer_id' => $withdrawn->id]);

        $this->send(Actor::user($owner->id), $offer->id, producerId: $withdrawn->id);
        $this->assertSame($withdrawn->id, $offer->fresh()->producer_id);

        $other = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->withdrawn()->create();
        $this->assertRefused('producer_cannot_be_picked', fn () => $this->send(Actor::user($owner->id), $offer->id, producerId: $other->id));
    }

    /**
     * @param  list<string>|null  $public
     * @param  list<string>|null  $private
     */
    private function sendPhotos(Actor $actor, int $offerId, ?array $public, ?array $private): void
    {
        app(CommandBus::class)->send(new UpdateOfferCommand(actor: $actor, offerId: $offerId, publicPhotos: $public, privatePhotos: $private));
    }

    public function test_the_owner_reorders_and_replaces_photos(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)
            ->withPublicPhotos(["offers/public/{$owner->id}/a.jpg", "offers/public/{$owner->id}/b.jpg"])
            ->withPrivatePhotos(["offers/private/{$owner->id}/x.jpg"])
            ->create();

        $this->sendPhotos(Actor::user($owner->id), $offer->id, ["offers/public/{$owner->id}/b.jpg", "offers/public/{$owner->id}/a.jpg", "offers/public/{$owner->id}/c.jpg"], []);

        $fresh = $offer->fresh();
        $this->assertSame(["offers/public/{$owner->id}/b.jpg", "offers/public/{$owner->id}/a.jpg", "offers/public/{$owner->id}/c.jpg"], $fresh->public_photos);
        $this->assertSame([], $fresh->private_photos);
    }

    public function test_an_update_without_photo_lists_keeps_the_photos(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withPublicPhotos(["offers/public/{$owner->id}/a.jpg"])->create();

        $this->send(Actor::user($owner->id), $offer->id, hint: 'OEM');

        $this->assertSame(["offers/public/{$owner->id}/a.jpg"], $offer->fresh()->public_photos);
    }

    public function test_an_admin_keeps_the_sellers_photos_and_adds_their_own(): void
    {
        $owner = UserModel::factory()->create();
        $admin = UserModel::factory()->admin()->create();
        $existing = "offers/public/{$owner->id}/a.jpg";
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withPublicPhotos([$existing])->create();

        $this->sendPhotos(Actor::user($admin->id), $offer->id, [$existing, "offers/public/{$admin->id}/n.jpg"], null);

        $this->assertSame([$existing, "offers/public/{$admin->id}/n.jpg"], $offer->fresh()->public_photos);
    }

    public function test_an_eleventh_photo_is_refused_and_the_photos_stay(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->withPrivatePhotos(["offers/private/{$owner->id}/x.jpg"])->create();
        $eleven = array_map(fn (int $n): string => "offers/private/{$owner->id}/{$n}.jpg", range(1, 11));

        $this->assertRefused('too_many_private_photos', fn () => $this->sendPhotos(Actor::user($owner->id), $offer->id, null, $eleven));
        $this->assertSame(["offers/private/{$owner->id}/x.jpg"], $offer->fresh()->private_photos);
    }

    public function test_another_sellers_private_photo_cannot_be_added_to_ones_own_offer(): void
    {
        $owner = UserModel::factory()->create();
        $victim = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create();

        $this->assertRefused('photo_is_invalid', fn () => $this->sendPhotos(Actor::user($owner->id), $offer->id, null, ["offers/private/{$victim->id}/secret.jpg"]));
        $this->assertSame([], $offer->fresh()->private_photos);
    }
}
