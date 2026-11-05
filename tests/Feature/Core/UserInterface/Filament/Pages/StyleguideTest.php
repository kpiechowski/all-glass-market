<?php

declare(strict_types=1);

namespace Tests\Feature\Core\UserInterface\Filament\Pages;

use App\Core\UserInterface\Filament\Pages\Styleguide;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** D-33: the Styleguide exists only in the local environment. */
class StyleguideTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A test whose name ends in `_locally` boots the application as `local`,
     * so the panel provider registers the page as it does on a developer machine.
     */
    public function createApplication(): Application
    {
        if (! str_ends_with($this->name(), '_locally')) {
            return parent::createApplication();
        }

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        /** @var Application $app */
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
            $app['env'] = 'local';
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_it_is_not_found_outside_local(): void
    {
        $this->assertFalse(app()->isLocal());
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->get('/admin/styleguide')->assertNotFound();
    }

    public function test_it_renders_every_block_locally(): void
    {
        $this->assertTrue(app()->isLocal());
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->get('/admin/styleguide')
            ->assertOk()
            ->assertSeeInOrder(['Formularz', 'Zakładki', 'Karta rekordu', 'Lista', 'Akcje i powiadomienia'])
            ->assertSee('Krój pisma')
            ->assertSee('Wpisz 10 cyfr NIP bez kresek i spacji.')
            ->assertSee('Zaproszeni')
            ->assertSee('Anna Kowalska');
    }

    public function test_the_empty_state_switch_empties_the_table_locally(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(Styleguide::class)
            ->assertCanSeeTableRecords(['1'])
            ->callTableAction('toggleEmptyState')
            ->assertCountTableRecords(0)
            ->assertSee('Nie ma jeszcze użytkowników');
    }

    public function test_a_list_tab_narrows_the_rows_locally(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(Styleguide::class)
            ->call('setListTab', 'invited')
            ->assertCanSeeTableRecords(['5', '10'])
            ->assertCanNotSeeTableRecords(['1'])
            ->callTableAction('toggleFiltersLayout')
            ->assertSet('showsFilterPanel', true);
    }

    public function test_saving_the_demo_form_validates_and_stores_nothing_locally(): void
    {
        Filament::setCurrentPanel('admin');
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        Livewire::test(Styleguide::class)
            ->call('save')
            ->assertHasErrors(['data.company_nip' => 'regex'])
            ->set('data.company_nip', '7792345678')
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified('Zapisano zmiany');
    }
}
