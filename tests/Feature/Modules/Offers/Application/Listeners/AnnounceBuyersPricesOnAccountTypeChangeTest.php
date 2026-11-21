<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Listeners;

use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Domain\Events\AccountTypeChanged;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-28: a Private seller becoming Company drops the buyer's prices by the VAT, and back. */
class AnnounceBuyersPricesOnAccountTypeChangeTest extends TestCase
{
    use RefreshDatabase;

    private function changed(int $userId, string $to, string $from): void
    {
        event(new AccountTypeChanged($userId, $to, $from, CarbonImmutable::now()));
    }

    public function test_becoming_a_company_announces_each_priced_offer_of_that_owner_only(): void
    {
        $owner = UserModel::factory()->create(['account_type' => 'company']);
        $other = UserModel::factory()->create(['account_type' => 'private']);
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create(['price_grosze' => 10000]);
        OfferModelFactory::new()->ownedBy($owner->id)->create(['price_grosze' => null]);
        OfferModelFactory::new()->ownedBy($other->id)->create(['price_grosze' => 10000]);
        Event::fake([OfferBuyersPriceChanged::class]);

        $this->changed($owner->id, 'company', 'private');

        Event::assertDispatchedTimes(OfferBuyersPriceChanged::class, 1);
        Event::assertDispatched(
            OfferBuyersPriceChanged::class,
            fn (OfferBuyersPriceChanged $event): bool => $event->offerId === $offer->id
                && $event->previousBuyersPriceGrosze === 12300
                && $event->buyersPriceGrosze === 10000,
        );
    }

    public function test_becoming_private_again_raises_the_price_by_the_vat(): void
    {
        $owner = UserModel::factory()->create(['account_type' => 'private']);
        OfferModelFactory::new()->ownedBy($owner->id)->create(['price_grosze' => 10000]);
        Event::fake([OfferBuyersPriceChanged::class]);

        $this->changed($owner->id, 'private', 'company');

        Event::assertDispatched(
            OfferBuyersPriceChanged::class,
            fn (OfferBuyersPriceChanged $event): bool => $event->previousBuyersPriceGrosze === 10000 && $event->buyersPriceGrosze === 12300,
        );
    }
}
