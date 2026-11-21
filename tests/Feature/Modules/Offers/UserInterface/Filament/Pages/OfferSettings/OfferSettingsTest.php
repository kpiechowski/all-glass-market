<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Offers\UserInterface\Filament\Pages\OfferSettings;

use App\Modules\Offers\Domain\Repositories\VatRateSetting;
use App\Modules\Offers\UserInterface\Filament\Pages\OfferSettings\OfferSettings;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-30, D-54: only a Super Admin opens the page and saves the rate, through the command. */
class OfferSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_an_admin_and_a_seller_cannot_open_the_page(): void
    {
        foreach ([UserModel::factory()->admin()->create(), UserModel::factory()->create()] as $user) {
            $this->actingAs($user);

            Livewire::test(OfferSettings::class)->assertForbidden();
        }
    }

    public function test_a_super_admin_sees_the_current_rate_and_saves_a_new_one(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(OfferSettings::class)
            ->assertFormSet(['vat_rate_percent' => 23])
            ->fillForm(['vat_rate_percent' => 8.5])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(850, app(VatRateSetting::class)->current()->basisPoints);
    }
}
