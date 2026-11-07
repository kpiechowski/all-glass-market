<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Preferences\UserInterface\Filament\Widgets;

use App\Core\Domain\QuickAccess\QuickAccessGroup;
use App\Core\Domain\QuickAccess\QuickAccessItem;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use App\Modules\Audit\UserInterface\Filament\Pages\AuditLog\AuditLog;
use App\Modules\Preferences\Infrastructure\Persistence\Models\UserPreferenceModel;
use App\Modules\Preferences\UserInterface\Filament\Widgets\QuickAccessWidget;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Issue 13. */
class QuickAccessWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    /** @param list<string> $keys */
    private function pin(UserModel $user, array $keys): void
    {
        UserPreferenceModel::factory()->create(['user_id' => $user->id, 'key' => 'quick_access', 'value' => $keys]);
    }

    /**
     * Registered keys a plain user cannot open, to fill the stored list past what is drawn.
     *
     * @return list<string>
     */
    private function hiddenKeys(int $count): array
    {
        $items = app(QuickAccessItems::class);
        $items->registerGroup(new QuickAccessGroup('hidden', 'preferences::widgets.quick_access.heading', 'heroicon-o-lock-closed'));
        $keys = [];

        for ($i = 1; $i <= $count; $i++) {
            $items->register(new QuickAccessItem(
                key: 'hidden.item_'.$i,
                group: 'hidden',
                label: fn (): string => 'Hidden',
                icon: fn (): ?string => null,
                url: fn (): string => '/hidden',
                visible: fn (): bool => false,
            ));
            $keys[] = 'hidden.item_'.$i;
        }

        return $keys;
    }

    /** @return list<string>|null */
    private function storedKeys(UserModel $user): ?array
    {
        return UserPreferenceModel::query()->where('user_id', $user->id)->where('key', 'quick_access')->first()?->value;
    }

    public function test_the_dashboard_offers_the_widget(): void
    {
        $this->assertContains(QuickAccessWidget::class, Filament::getWidgets());
    }

    public function test_a_user_without_tiles_sees_the_empty_grid_with_add(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(QuickAccessWidget::class)
            ->assertSee('Szybki dostęp')
            ->assertSee('Brak kafelków')
            ->assertActionVisible('startAdding')
            ->assertActionHidden('toggleEdit');
    }

    public function test_add_on_the_empty_grid_opens_edit_mode_with_the_palette(): void
    {
        $this->actingAs(UserModel::factory()->create());

        Livewire::test(QuickAccessWidget::class)
            ->callAction('startAdding')
            ->assertSet('isEditing', true)
            ->assertDontSee('Brak kafelków')
            ->assertSee('Dostępne')
            ->assertSee('Dodaj „Mój profil” do szybkiego dostępu')
            ->assertActionVisible('toggleEdit');
    }

    public function test_tiles_show_in_the_users_order_and_link_to_their_pages(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->pin($admin, ['users.profile', 'users.list']);
        $this->actingAs($admin);

        Livewire::test(QuickAccessWidget::class)
            ->assertSeeInOrder(['Mój profil', 'Użytkownicy'])
            ->assertSee(EditProfile::getUrl(), false)
            ->assertSee(UserResource::getUrl(), false)
            ->assertDontSee('Brak kafelków')
            ->assertActionVisible('toggleEdit');
    }

    public function test_unknown_and_inaccessible_tiles_are_skipped(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['gone.page', 'users.list', 'audit.log', 'users.profile']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->assertSee(EditProfile::getUrl(), false)
            ->assertDontSee(UserResource::getUrl(), false)
            ->assertDontSee(AuditLog::getUrl(), false);
    }

    public function test_the_edit_action_switches_edit_mode_on_and_off(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->callAction('toggleEdit')
            ->assertSet('isEditing', true)
            ->assertSee('Przeciągnij kafelek, aby zmienić kolejność.')
            ->assertSee('x-sortable', false)
            ->callAction('toggleEdit')
            ->assertSet('isEditing', false);
    }

    public function test_the_palette_lists_the_places_left_grouped_with_their_group_label(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->set('isEditing', true)
            ->assertSee('Użytkownicy')
            ->assertSee('Dodaj „Dziennik” do szybkiego dostępu')
            ->assertDontSee('Dodaj „Mój profil” do szybkiego dostępu')
            ->assertDontSee('Dodaj „Użytkownicy” do szybkiego dostępu')
            ->assertDontSee('Historia i logi');
    }

    public function test_the_palette_says_when_nothing_is_left_to_add(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile', 'users.journal']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->set('isEditing', true)
            ->assertSee('Wszystkie dostępne miejsca są już w szybkim dostępie.');
    }

    public function test_add_saves_the_chosen_item_at_the_end(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->set('isEditing', true)
            ->call('add', 'users.journal')
            ->assertSet('justAdded', 'users.journal');

        $this->assertSame(['users.profile', 'users.journal'], $this->storedKeys($user));
    }

    public function test_add_creates_the_first_tile_from_the_empty_grid(): void
    {
        $user = UserModel::factory()->create();
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)->call('add', 'users.profile');

        $this->assertSame(['users.profile'], $this->storedKeys($user));
    }

    public function test_add_ignores_places_the_user_may_not_open_or_already_has(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->call('add', 'users.list')
            ->call('add', 'users.profile')
            ->call('add', 'gone.page');

        $this->assertSame(['users.profile'], $this->storedKeys($user));
    }

    public function test_at_the_limit_the_palette_explains_it_and_add_is_refused(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile', ...$this->hiddenKeys(9)]);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->set('isEditing', true)
            ->assertSee('10 z 10')
            ->assertSee('Osiągnięto limit 10 kafelków — usuń któryś, aby dodać nowy.')
            ->call('add', 'users.journal');

        $this->assertNotContains('users.journal', $this->storedKeys($user));
    }

    public function test_a_tile_the_user_cannot_open_now_is_kept_in_place_on_every_save(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile', 'users.list', 'gone.page', 'users.journal']);
        $this->actingAs($user);

        $widget = Livewire::test(QuickAccessWidget::class)
            ->assertDontSee(UserResource::getUrl(), false);

        $widget->call('reorder', ['users.journal', 'users.profile']);
        $this->assertSame(['users.journal', 'users.list', 'users.profile'], $this->storedKeys($user));

        $widget->call('remove', 'users.profile');
        $this->assertSame(['users.journal', 'users.list'], $this->storedKeys($user));

        $widget->call('add', 'users.profile');
        $this->assertSame(['users.journal', 'users.list', 'users.profile'], $this->storedKeys($user));

        $widget->call('remove', 'users.list');
        $this->assertSame(['users.journal', 'users.list', 'users.profile'], $this->storedKeys($user));
    }

    public function test_reorder_saves_the_new_order(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $this->pin($admin, ['users.list', 'users.profile', 'users.journal']);
        $this->actingAs($admin);

        Livewire::test(QuickAccessWidget::class)->call('reorder', ['users.journal', 'users.list', 'users.profile']);

        $this->assertSame(['users.journal', 'users.list', 'users.profile'], $this->storedKeys($admin));
    }

    public function test_reorder_ignores_anything_but_the_current_tiles(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile', 'users.journal']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)->call('reorder', ['users.journal', 'users.list']);

        $this->assertSame(['users.profile', 'users.journal'], $this->storedKeys($user));
    }

    public function test_remove_drops_the_tile(): void
    {
        $user = UserModel::factory()->create();
        $this->pin($user, ['users.profile', 'users.journal']);
        $this->actingAs($user);

        Livewire::test(QuickAccessWidget::class)
            ->set('isEditing', true)
            ->call('remove', 'users.profile')
            ->assertSet('justReturned', 'users.profile')
            ->assertSee('Dodaj „Mój profil” do szybkiego dostępu');

        $this->assertSame(['users.journal'], $this->storedKeys($user));
    }
}
