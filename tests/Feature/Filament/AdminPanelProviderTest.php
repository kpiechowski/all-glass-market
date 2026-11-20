<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\Styleguide;
use App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs\HistoryAndLogs;
use App\Modules\Catalogue\UserInterface\Filament\Pages\CatalogueSettings\CatalogueSettings;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\GlassModelResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\VehicleResource;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** D-25, D-25a: the sidebar is built explicitly: a top block (dashboard, journal), the labelled groups Katalog and Administracja, and a bottom block with the profile. */
class AdminPanelProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_super_admin_sees_the_top_block_the_administracja_group_and_the_profile_at_the_bottom(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        [$top, $bottom] = $this->blocks();

        $this->assertSame([Dashboard::getNavigationLabel(), JournalEntryResource::getNavigationLabel()], $this->labels($top));
        $this->assertStringContainsString('sidebar-group--top', $top->getExtraSidebarAttributeBag()->get('class'));
        $this->assertSame([
            UserResource::getNavigationLabel(),
            HistoryAndLogs::getNavigationLabel(),
        ], $this->labels($this->group('Administracja')));
        $this->assertFalse($this->group('Administracja')->isCollapsible());
        $this->assertSame([EditProfile::getNavigationLabel()], $this->labels($bottom));
        $this->assertStringContainsString('sidebar-group--bottom', $bottom->getExtraSidebarAttributeBag()->get('class'));
    }

    public function test_the_katalog_group_comes_before_administracja(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $labels = collect(Filament::getNavigation())->map(fn (NavigationGroup $group): ?string => $group->getLabel())->values()->all();

        $this->assertSame([null, 'Oferty', 'Katalog', 'Administracja', null], $labels);
    }

    public function test_a_plain_user_does_not_see_the_permission_gated_screens(): void
    {
        $this->actingAs(UserModel::factory()->create());

        [$top, $bottom] = $this->blocks();

        $this->assertSame([Dashboard::getNavigationLabel(), JournalEntryResource::getNavigationLabel()], $this->labels($top));
        $this->assertNull($this->group('Administracja'));
        $this->assertSame([EditProfile::getNavigationLabel()], $this->labels($bottom));
    }

    /** Catalogue, D-42: the "Katalog" group shows for an Admin and is gone for a seller. */
    public function test_the_katalog_group_shows_dictionaries_to_an_admin_and_nothing_to_a_seller(): void
    {
        $this->actingAs(UserModel::factory()->admin()->create());

        $group = collect(Filament::getNavigation())->first(fn (NavigationGroup $group): bool => $group->getLabel() === 'Katalog');

        $this->assertNotNull($group);
        $this->assertSame([
            GlassModelResource::getNavigationLabel(),
            VehicleResource::getNavigationLabel(),
            DictionaryValueResource::getNavigationLabel(),
            CatalogueSettings::getNavigationLabel(),
        ], $this->labels($group));

        $this->actingAs(UserModel::factory()->create());

        $this->assertNull(collect(Filament::getNavigation())->first(fn (NavigationGroup $group): bool => $group->getLabel() === 'Katalog'));
    }

    public function test_items_keep_their_badges(): void
    {
        $this->actingAs(UserModel::factory()->create());

        [, $bottom] = $this->blocks();

        $this->assertSame('!', $bottom->getItems()[0]->getBadge());
    }

    public function test_the_styleguide_is_not_in_the_sidebar_outside_local(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $labels = collect(Filament::getNavigation())
            ->flatMap(fn (NavigationGroup $group): array => $this->labels($group));

        $this->assertNotContains(Styleguide::getNavigationLabel(), $labels);
    }

    public function test_the_sidebar_renders_for_the_super_admin(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('sidebar-group--top')
            ->assertSee('sidebar-group--bottom')
            ->assertSeeInOrder([Dashboard::getNavigationLabel(), HistoryAndLogs::getNavigationLabel(), EditProfile::getNavigationLabel()]);
    }

    /**
     * D-40: the brand logo replaces the brand name. Both versions are rendered; the theme shows the
     * dark one on white (login, topbar) and the client's white one in the navy sidebar.
     */
    public function test_the_brand_logo_shows_on_the_login_page_and_in_the_panel(): void
    {
        $this->get(Filament::getLoginUrl())
            ->assertOk()
            ->assertSee('class="brand-logo brand-logo--on-light"', escape: false)
            ->assertSee('images/brand/allglass-logotype.svg');

        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('images/brand/allglass-logotype.svg')
            ->assertSee('class="brand-logo brand-logo--on-dark"', escape: false)
            ->assertSee('images/brand/allglass-logotype-white.svg');
    }

    /**
     * The top and the bottom block; the labelled groups sit between them for those who may open them.
     *
     * @return array{0: NavigationGroup, 1: NavigationGroup}
     */
    private function blocks(): array
    {
        $groups = array_values(Filament::getNavigation());
        $this->assertGreaterThanOrEqual(2, count($groups));

        return [$groups[0], $groups[array_key_last($groups)]];
    }

    private function group(string $label): ?NavigationGroup
    {
        return collect(Filament::getNavigation())->first(fn (NavigationGroup $group): bool => $group->getLabel() === $label);
    }

    /** @return list<string> */
    private function labels(NavigationGroup $group): array
    {
        return collect($group->getItems())
            ->map(fn (NavigationItem $item): string => (string) $item->getLabel())
            ->values()
            ->all();
    }
}
