<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ChangeVatRate;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\ChangeVatRate\ChangeVatRateCommand;
use App\Modules\Offers\Domain\Events\OfferBuyersPriceChanged;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-28, FR-30, D-54. */
class ChangeVatRateCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function change(Actor $actor, int $basisPoints): void
    {
        app(CommandBus::class)->send(new ChangeVatRateCommand($actor, $basisPoints));
    }

    public function test_the_rate_starts_at_twenty_three_percent(): void
    {
        $this->assertSame(2300, app(VatRateSetting::class)->current()->basisPoints);
    }

    public function test_a_super_admin_changes_the_rate(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();

        $this->change(Actor::user($superAdmin->id), 800);
        $this->assertSame(800, app(VatRateSetting::class)->current()->basisPoints);

        $this->change(Actor::user($superAdmin->id), 500);
        $this->assertSame(500, app(VatRateSetting::class)->current()->basisPoints);
    }

    public function test_an_admin_and_a_seller_cannot_change_the_rate(): void
    {
        foreach ([UserModel::factory()->admin()->create(), UserModel::factory()->create()] as $user) {
            try {
                $this->change(Actor::user($user->id), 800);
                $this->fail('Expected a refusal.');
            } catch (AuthorizationException) {
            }
        }

        $this->assertSame(2300, app(VatRateSetting::class)->current()->basisPoints);
    }

    public function test_a_rate_out_of_range_is_refused(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();

        try {
            $this->change(Actor::user($superAdmin->id), 10001);
            $this->fail('Expected a refusal.');
        } catch (OfferRefused $refusal) {
            $this->assertSame('offers::exceptions.vat_rate_is_invalid', $refusal->translationKey);
        }
    }

    public function test_every_private_owners_priced_offer_is_announced_and_company_owners_are_not(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $priced = OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => 10000]);
        OfferModelFactory::new()->ownedBy($private->id)->create(['price_grosze' => null]);
        OfferModelFactory::new()->ownedBy($company->id)->create(['price_grosze' => 10000]);
        Event::fake([OfferBuyersPriceChanged::class]);

        $this->change(Actor::user($superAdmin->id), 800);

        Event::assertDispatchedTimes(OfferBuyersPriceChanged::class, 1);
        Event::assertDispatched(
            OfferBuyersPriceChanged::class,
            fn (OfferBuyersPriceChanged $event): bool => $event->offerId === $priced->id
                && $event->previousBuyersPriceGrosze === 12300
                && $event->buyersPriceGrosze === 10800,
        );
    }

    public function test_saving_the_same_rate_announces_nothing(): void
    {
        $superAdmin = UserModel::factory()->superAdmin()->create();
        OfferModelFactory::new()->create(['price_grosze' => 10000]);
        Event::fake([OfferBuyersPriceChanged::class]);

        $this->change(Actor::user($superAdmin->id), 2300);

        Event::assertNotDispatched(OfferBuyersPriceChanged::class);
    }
}
