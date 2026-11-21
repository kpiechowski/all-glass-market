<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\Commands\ReturnSale;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Offers\Application\Commands\ReturnSale\ReturnSaleCommand;
use App\Modules\Offers\Domain\Enums\OfferStatus;
use App\Modules\Offers\Domain\Exceptions\OfferRefused;
use App\Modules\Offers\Infrastructure\Factories\OfferModelFactory;
use App\Modules\Offers\Infrastructure\Factories\SaleModelFactory;
use App\Modules\Offers\Infrastructure\Persistence\Models\SaleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\JournalEntryModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-19, FR-34, RULE-09, RULE-10. */
class ReturnSaleCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function returnSale(Actor $actor, int $offerId, string $reason = 'Klient zrezygnował'): void
    {
        app(CommandBus::class)->send(new ReturnSaleCommand($actor, $offerId, $reason));
    }

    private function refusalKey(callable $action): string
    {
        try {
            $action();
        } catch (OfferRefused $refusal) {
            return $refusal->translationKey;
        }

        $this->fail('Expected a refusal.');
    }

    public function test_an_admin_returns_a_sale_the_offer_is_active_and_the_sale_stays_marked_returned(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->returnSale(Actor::user($admin->id), $offer->id, ' Klient zrezygnował ');

        $this->assertSame(OfferStatus::Active, $offer->fresh()->status);
        $sale = SaleModel::query()->sole();
        $this->assertNotNull($sale->returned_at);
        $this->assertSame('Klient zrezygnował', $sale->return_reason);
        $this->assertSame($admin->id, $sale->returned_by_id);
        $this->assertSame(18450, $sale->sale_price_grosze);
        $this->assertSame($owner->id, JournalEntryModel::query()->where('subject_id', $offer->id)->sole()->user_id);
    }

    public function test_the_reason_is_required(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->assertSame('offers::exceptions.return_reason_required', $this->refusalKey(fn () => $this->returnSale(Actor::user($admin->id), $offer->id, ' ')));

        $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        $this->assertNull(SaleModel::query()->sole()->returned_at);
    }

    public function test_only_a_sold_offer_has_a_sale_to_return(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Active)->create();

        $this->assertSame('offers::exceptions.sale_cannot_be_returned', $this->refusalKey(fn () => $this->returnSale(Actor::user($admin->id), $offer->id)));
    }

    public function test_a_second_sale_after_a_return_can_be_returned_again_and_both_stay(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->create();
        SaleModelFactory::new()->forOffer($offer->id)->returned($admin->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        $this->returnSale(Actor::user($admin->id), $offer->id);

        $this->assertSame(2, SaleModel::query()->whereNotNull('returned_at')->count());
    }

    public function test_the_owner_cannot_return_a_sale(): void
    {
        $owner = UserModel::factory()->create();
        $offer = OfferModelFactory::new()->sendable()->status(OfferStatus::Sold)->ownedBy($owner->id)->create();
        SaleModelFactory::new()->forOffer($offer->id)->create();

        try {
            $this->returnSale(Actor::user($owner->id), $offer->id);
            $this->fail('Expected a refusal.');
        } catch (AuthorizationException) {
            $this->assertSame(OfferStatus::Sold, $offer->fresh()->status);
        }
    }
}
