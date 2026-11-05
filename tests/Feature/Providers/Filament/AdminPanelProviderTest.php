<?php

declare(strict_types=1);

namespace Tests\Feature\Providers\Filament;

use App\Core\UserInterface\Filament\Pages\Styleguide;
use App\Modules\Audit\UserInterface\Filament\Pages\AuditLog\AuditLog;
use App\Modules\Audit\UserInterface\Filament\Pages\TechnicalLog\TechnicalLog;
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

/** D-25: the sidebar is built explicitly, a top block of work screens and a bottom block with the profile. */
class AdminPanelProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_the_super_admin_sees_every_screen_on_top_and_the_profile_at_the_bottom(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->create());

        [$top, $bottom] = $this->blocks();

        $this->assertSame([
            Dashboard::getNavigationLabel(),
            UserResource::getNavigationLabel(),
            JournalEntryResource::getNavigationLabel(),
            AuditLog::getNavigationLabel(),
            TechnicalLog::getNavigationLabel(),
        ], $this->labels($top));
        $this->assertSame([EditProfile::getNavigationLabel()], $this->labels($bottom));
        $this->assertStringContainsString('sidebar-group--bottom', $bottom->getExtraSidebarAttributeBag()->get('class'));
    }

    public function test_a_plain_user_does_not_see_the_permission_gated_screens(): void
    {
        $this->actingAs(UserModel::factory()->create());

        [$top, $bottom] = $this->blocks();

        $this->assertSame([
            Dashboard::getNavigationLabel(),
            JournalEntryResource::getNavigationLabel(),
        ], $this->labels($top));
        $this->assertSame([EditProfile::getNavigationLabel()], $this->labels($bottom));
    }

    public function test_items_keep_their_badges(): void
    {
        $this->actingAs(UserModel::factory()->create());

        [, $bottom] = $this->blocks();

        $this->assertSame('i', $bottom->getItems()[0]->getBadge());
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
            ->assertSee('sidebar-group--bottom')
            ->assertSeeInOrder([AuditLog::getNavigationLabel(), EditProfile::getNavigationLabel()]);
    }

    /** D-40: the brand logo replaces the brand name on the login page and in the panel. */
    public function test_the_brand_logo_shows_on_the_login_page_and_in_the_panel(): void
    {
        $this->get(Filament::getLoginUrl())
            ->assertOk()
            ->assertSee('images/brand/allglass-logotype.svg');

        $this->actingAs(UserModel::factory()->superAdmin()->create());

        $this->get(Dashboard::getUrl())
            ->assertOk()
            ->assertSee('images/brand/allglass-logotype.svg');
    }

    /** @return array{0: NavigationGroup, 1: NavigationGroup} */
    private function blocks(): array
    {
        $groups = Filament::getNavigation();
        $this->assertCount(2, $groups);

        return [$groups[0], $groups[1]];
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
