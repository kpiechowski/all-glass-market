<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\UserInterface\Http;

use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Pages\EditProfile\EditProfile;
use App\Modules\Users\UserInterface\Filament\Resources\JournalEntries\JournalEntryResource;
use App\Modules\Users\UserInterface\Filament\Resources\Users\UserResource;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-37, WF-07: every panel page leads to "Ustaw nowe hasło" while the marker is set. */
class RequirePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
    }

    private function formUrl(): string
    {
        return route('filament.admin.set-new-password');
    }

    public function test_a_flagged_user_is_sent_to_the_new_password_screen_from_any_panel_page(): void
    {
        $this->actingAs(UserModel::factory()->admin()->passwordChangeRequired()->create());

        foreach (['/admin', UserResource::getUrl(), JournalEntryResource::getUrl(), EditProfile::getUrl()] as $path) {
            $this->get($path)->assertRedirect($this->formUrl());
        }
    }

    public function test_the_screen_itself_stays_open_for_a_flagged_user(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->passwordChangeRequired()->create());

        $this->get($this->formUrl())->assertOk()->assertSee(__('users::pages.set_new_password.title'));
    }

    public function test_a_flagged_user_can_sign_out(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->passwordChangeRequired()->create());

        $this->post(Filament::getLogoutUrl())->assertRedirect();

        $this->assertGuest();
    }

    public function test_an_unflagged_user_reaches_the_dashboard(): void
    {
        $this->actingAs(UserModel::factory()->withRole('user')->create());

        $this->get('/admin')->assertOk();
    }

    public function test_a_super_admin_is_subject_to_the_marker_too(): void
    {
        $this->actingAs(UserModel::factory()->superAdmin()->passwordChangeRequired()->create());

        $this->get('/admin')->assertRedirect($this->formUrl());
    }

    public function test_an_impersonator_is_not_redirected(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $target = UserModel::factory()->withRole('user')->passwordChangeRequired()->create();

        // WF-06: the panel session is the target's, with the impersonator remembered.
        $this->actingAs($target)->withSession(['users.impersonator_id' => $admin->id]);

        $this->get('/admin')->assertOk();
    }
}
