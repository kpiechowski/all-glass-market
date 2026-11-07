<?php

declare(strict_types=1);

namespace Tests\Feature\Core\QuickAccess;

use App\Core\Domain\Preferences\PreferenceDefinitions;
use App\Core\Domain\QuickAccess\QuickAccessItem;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Issue 13: each module registers its own pages; the item reads them from the Filament page. */
class RegisteredQuickAccessItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_and_audit_register_their_pages(): void
    {
        $keys = array_map(fn (QuickAccessItem $item): string => $item->key, app(QuickAccessItems::class)->all());

        $this->assertEqualsCanonicalizing(['users.list', 'users.journal', 'users.profile', 'audit.log', 'audit.technical_log'], $keys);
    }

    public function test_an_item_shows_what_the_sidebar_shows_and_follows_can_access(): void
    {
        Filament::setCurrentPanel('admin');
        $item = app(QuickAccessItems::class)->find('users.list');

        $this->assertSame('Użytkownicy', $item?->label());
        $this->assertSame(Heroicon::OutlinedUsers, $item->icon());
        $this->assertSame(UserResource::getUrl(), $item->url());
        $this->assertSame('users', $item->group);

        $this->actingAs(UserModel::factory()->create());
        $this->assertFalse($item->isVisible());

        $this->actingAs(UserModel::factory()->admin()->create());
        $this->assertTrue($item->isVisible());
    }

    public function test_users_and_audit_register_their_groups_with_an_icon(): void
    {
        $users = app(QuickAccessItems::class)->group('users');
        $audit = app(QuickAccessItems::class)->group('audit');

        $this->assertSame('Użytkownicy', $users?->label());
        $this->assertSame(Heroicon::OutlinedUsers, $users->icon);
        $this->assertSame('Historia i logi', $audit?->label());
        $this->assertSame(Heroicon::OutlinedClock, $audit->icon);
    }

    public function test_preferences_registers_the_quick_access_definition(): void
    {
        $this->assertNotNull(app(PreferenceDefinitions::class)->find('quick_access'));
    }
}
