<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\Application\ModuleApi;

use App\Core\Domain\Actor;
use App\Modules\Offers\Application\Commands\ChangeVatRate\ChangeVatRateCommand;
use App\Modules\Offers\Application\ModuleApi\BuyersPrices;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** FR-28, RULE-08: derived by the owner's current Account type and the current rate. */
class BuyersPricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_owner_pays_the_entered_price_and_a_private_owner_adds_vat(): void
    {
        $company = UserModel::factory()->create(['account_type' => 'company']);
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $prices = app(BuyersPrices::class);

        $this->assertSame(10000, $prices->forOffer($company->id, 10000));
        $this->assertSame(12300, $prices->forOffer($private->id, 10000));
    }

    public function test_there_is_no_buyers_price_without_an_entered_price(): void
    {
        $private = UserModel::factory()->create(['account_type' => 'private']);

        $this->assertNull(app(BuyersPrices::class)->forOffer($private->id, null));
    }

    public function test_it_follows_a_rate_change(): void
    {
        $private = UserModel::factory()->create(['account_type' => 'private']);
        $superAdmin = UserModel::factory()->superAdmin()->create();
        app(CommandBus::class)->send(new ChangeVatRateCommand(Actor::user($superAdmin->id), 800));

        $this->assertSame(10800, app(BuyersPrices::class)->forOffer($private->id, 10000));
    }

    public function test_a_primed_list_asks_users_once_for_all_rows(): void
    {
        $owners = UserModel::factory()->count(3)->create(['account_type' => 'private']);
        $prices = app(BuyersPrices::class);
        $prices->prime($owners->pluck('id')->all());

        DB::enableQueryLog();
        foreach ($owners as $owner) {
            $this->assertSame(12300, $prices->forOffer($owner->id, 10000));
        }

        $this->assertCount(0, DB::getQueryLog());
    }
}
