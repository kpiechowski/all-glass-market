<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Filament\Widgets;

use App\Modules\Users\Domain\Enums\AccountType;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Widgets\AccountTile;
use Filament\Facades\Filament;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** FR-34, FR-13. */
class AccountTileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    public function test_the_tile_renders_for_a_private_user(): void
    {
        $user = UserModel::factory()->create(['name' => 'Jan Kowalski', 'account_type' => AccountType::Private]);
        $this->actingAs($user);

        Livewire::test(AccountTile::class)
            ->assertSee('Użytkownik')
            ->assertSee('Jan Kowalski')
            ->assertSee('Konto prywatne')
            ->assertDontSee('Konto firmowe')
            ->assertSee('Wyloguj')
            ->assertSee('Edytuj profil')
            ->assertSee(EditProfile::getUrl(), false);
    }

    public function test_the_tile_renders_for_a_company_user(): void
    {
        $user = UserModel::factory()->create(['name' => 'Firma Sp. z o.o.', 'account_type' => AccountType::Company]);
        $this->actingAs($user);

        Livewire::test(AccountTile::class)
            ->assertSee('Firma Sp. z o.o.')
            ->assertSee('Konto firmowe')
            ->assertDontSee('Konto prywatne');
    }

    public function test_the_dashboard_offers_the_account_tile_and_not_the_default_account_widget(): void
    {
        $widgets = Filament::getWidgets();

        $this->assertContains(AccountTile::class, $widgets);
        $this->assertNotContains(AccountWidget::class, $widgets);
    }
}
