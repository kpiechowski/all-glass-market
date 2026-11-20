<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\CreateOffer;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Offers\Application\Commands\CreateOffer\CreateOfferCommand;
use App\Modules\Offers\Domain\Enums\OfferCondition;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\OfferModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-01 to FR-06 (Draft saving), RULE-02, RULE-07, RULE-13. */
class CreateOfferCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function seller(): Actor
    {
        return Actor::user(UserModel::factory()->create()->id);
    }

    private function send(Actor $actor, ?int $glassModelId = null, ?string $hint = null, ?int $producerId = null, ?string $condition = null, ?int $rating = null, ?int $price = null): int
    {
        return app(CommandBus::class)->send(new CreateOfferCommand(
            actor: $actor,
            glassModelId: $glassModelId,
            identificationHint: $hint,
            producerId: $producerId,
            year: 2018,
            condition: $condition,
            rating: $rating,
            description: 'Opis',
            privateNotes: 'Notatka',
            priceGrosze: $price,
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

    public function test_a_seller_creates_a_draft_they_own_with_a_glass_model(): void
    {
        $seller = $this->seller();
        $glassModel = GlassModelModelFactory::new()->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        $id = $this->send($seller, glassModelId: $glassModel->id, producerId: $producer->id, condition: 'used', rating: 4, price: 25000);

        $offer = OfferModel::findOrFail($id);
        $this->assertSame($seller->id, $offer->owner_id);
        $this->assertSame(OfferStatus::Draft, $offer->status);
        $this->assertSame($glassModel->id, $offer->glass_model_id);
        $this->assertNull($offer->identification_hint);
        $this->assertSame($producer->id, $offer->producer_id);
        $this->assertSame(OfferCondition::Used, $offer->condition);
        $this->assertSame(4, $offer->rating);
        $this->assertSame(25000, $offer->price_grosze);
        $this->assertSame('Opis', $offer->description);
        $this->assertSame('Notatka', $offer->private_notes);
    }

    public function test_an_admin_creates_a_draft_with_a_hint_and_owns_it(): void
    {
        $admin = Actor::user(UserModel::factory()->admin()->create()->id);

        $id = $this->send($admin, hint: 'VIN WVW123');

        $offer = OfferModel::findOrFail($id);
        $this->assertSame($admin->id, $offer->owner_id);
        $this->assertSame('VIN WVW123', $offer->identification_hint);
        $this->assertNull($offer->glass_model_id);
    }

    public function test_a_draft_may_be_empty(): void
    {
        $this->assertIsInt($this->send($this->seller()));
    }

    public function test_the_number_is_ag_and_eight_digits_and_each_offer_has_its_own(): void
    {
        $seller = $this->seller();

        $first = OfferModel::findOrFail($this->send($seller))->number;
        $second = OfferModel::findOrFail($this->send($seller))->number;

        $this->assertMatchesRegularExpression('/^AG\d{8}$/', $first);
        $this->assertNotSame($first, $second);
    }

    public function test_the_number_never_collides_with_an_existing_one(): void
    {
        $taken = OfferModelFactory::new()->create()->number;

        $number = OfferModel::findOrFail($this->send($this->seller()))->number;

        $this->assertNotSame($taken, $number);
    }

    public function test_a_glass_model_and_a_hint_together_are_refused(): void
    {
        $glassModel = GlassModelModelFactory::new()->create();

        $this->assertRefused('glass_model_and_hint_together', fn () => $this->send($this->seller(), glassModelId: $glassModel->id, hint: 'OEM'));
        $this->assertSame(0, OfferModel::count());
    }

    public function test_a_rating_on_a_new_piece_is_refused(): void
    {
        $this->assertRefused('rating_not_for_new', fn () => $this->send($this->seller(), condition: 'new', rating: 5));
    }

    public function test_an_unknown_condition_is_refused(): void
    {
        $this->assertRefused('condition_is_unknown', fn () => $this->send($this->seller(), condition: 'broken'));
    }

    public function test_a_glass_model_that_does_not_exist_is_refused(): void
    {
        $this->assertRefused('glass_model_cannot_be_picked', fn () => $this->send($this->seller(), glassModelId: 99999));
    }

    public function test_a_withdrawn_glass_model_is_refused(): void
    {
        $withdrawn = GlassModelModelFactory::new()->withdrawn()->create();

        $this->assertRefused('glass_model_cannot_be_picked', fn () => $this->send($this->seller(), glassModelId: $withdrawn->id));
    }

    public function test_a_withdrawn_or_foreign_producer_is_refused(): void
    {
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->withdrawn()->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->assertRefused('producer_cannot_be_picked', fn () => $this->send($this->seller(), producerId: $withdrawn->id));
        $this->assertRefused('producer_cannot_be_picked', fn () => $this->send($this->seller(), producerId: $colour->id));
    }

    public function test_a_user_without_the_permission_is_refused(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->send(Actor::system('test'));
    }
}
