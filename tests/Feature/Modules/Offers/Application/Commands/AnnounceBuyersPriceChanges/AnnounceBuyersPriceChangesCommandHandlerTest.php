<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\AnnounceBuyersPriceChanges;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\AnnounceBuyersPriceChanges\AnnounceBuyersPriceChangesCommand;
use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-28. */
class AnnounceBuyersPriceChangesCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_announces_a_moved_price_and_nothing_for_an_unknown_account_type(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->ownedBy($owner->id)->create(['price_grosze' => 10000]);
        Event::fake([OfferBuyersPriceChanged::class]);

        app(CommandBus::class)->send(new AnnounceBuyersPriceChangesCommand(Actor::system('test'), $owner->id, 'bogus', 'company'));
        Event::assertNotDispatched(OfferBuyersPriceChanged::class);

        app(CommandBus::class)->send(new AnnounceBuyersPriceChangesCommand(Actor::system('test'), $owner->id, 'private', 'company'));
        Event::assertDispatched(OfferBuyersPriceChanged::class, fn (OfferBuyersPriceChanged $event): bool => $event->offerId === $offer->id);
    }

    public function test_a_zero_price_never_moves(): void
    {
        $owner = UserModel::factory()->create();
        OfferModelFactory::new()->ownedBy($owner->id)->create(['price_grosze' => 0]);
        Event::fake([OfferBuyersPriceChanged::class]);

        app(CommandBus::class)->send(new AnnounceBuyersPriceChangesCommand(Actor::system('test'), $owner->id, 'private', 'company'));

        Event::assertNotDispatched(OfferBuyersPriceChanged::class);
    }
}
