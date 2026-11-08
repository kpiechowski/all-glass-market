<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Audit;

use App\Core\Domain\Actor;
use App\Core\Domain\Events\ChangeKind;
use App\Modules\Audit\Application\ModuleApi\AuditableRecords;
use App\Modules\Audit\Application\Queries\PresentChange\ChangePresenter;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Application\Commands\ChangeUserPassword\ChangeUserPasswordCommand;
use App\Modules\Users\Application\Commands\CreateUser\CreateUserCommand;
use App\Modules\Users\Application\Commands\DeactivateUser\DeactivateUserCommand;
use App\Modules\Users\Application\Commands\ReactivateUser\ReactivateUserCommand;
use App\Modules\Users\Application\Commands\SendPasswordLink\SendPasswordLinkCommand;
use App\Modules\Users\Application\Commands\SetPasswordChangeRequirement\SetPasswordChangeRequirementCommand;
use App\Modules\Users\Application\Commands\SetPasswordFromLink\SetPasswordFromLinkCommand;
use App\Modules\Users\Application\Commands\UpdateUser\UpdateUserCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** Users registers the User with Audit and every write path records its Actor (D-17, D-21, D-23, D-33). */
class UserAuditTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    private UserModel $super;

    protected function setUp(): void
    {
        parent::setUp();

        $this->super = UserModel::factory()->superAdmin()->create();
        DB::table('audit_changes')->delete();
    }

    private function send(object $command): mixed
    {
        return app(CommandBus::class)->send($command);
    }

    private function update(UserModel $actor, UserModel $target, array $arguments = []): void
    {
        $this->send(new UpdateUserCommand(Actor::user($actor->id), $target->id, ...[
            'name' => $target->name, 'email' => $target->email, ...$arguments,
        ]));
    }

    private function entry(): ChangeModel
    {
        return ChangeModel::query()->sole();
    }

    public function test_the_user_is_registered_with_name_title_link_and_polish_labels(): void
    {
        $type = app(AuditableRecords::class)->findByKey('users.user');

        $this->assertNotNull($type);
        $this->assertSame('Użytkownik', $type->typeName());
        $this->assertSame('Imię i nazwisko', $type->labelOf('name'));
        $this->assertSame('Jan Kowalski', $type->titleOf(UserModel::factory()->make(['name' => 'Jan Kowalski'])));
        $this->assertSame([], $type->restorableFields);
    }

    public function test_creating_a_user_records_created_with_the_actor_and_no_secrets(): void
    {
        $this->send(new CreateUserCommand(Actor::user($this->super->id), 'user', 'Jan', 'jan@example.com', 'Sekret-haslo-1', extraPermissions: ['users.impersonate']));

        $entry = $this->entry();
        $this->assertSame(ChangeKind::Created, $entry->kind);
        $this->assertSame('users.user', $entry->record_type);
        $this->assertSame($this->super->id, $entry->actor_id);
        $this->assertSame(['old' => null, 'new' => 'Jan'], $entry->changes['name']);
        $this->assertSame(['old' => null, 'new' => 1], $entry->changes['password_change_required']);
        $this->assertArrayNotHasKey('password', $entry->changes);
        $this->assertArrayNotHasKey('remember_token', $entry->changes);
        $this->assertSame(['extra_permissions' => ['added' => ['users.impersonate'], 'removed' => []]], $entry->relation_changes);
    }

    public function test_editing_a_user_records_old_and_new_values(): void
    {
        $user = UserModel::factory()->create(['phone' => '111']);
        DB::table('audit_changes')->delete();

        $this->update($this->super, $user, ['phone' => '222', 'city' => 'Łódź']);

        $entry = $this->entry();
        $this->assertSame(ChangeKind::Changed, $entry->kind);
        $this->assertSame($this->super->id, $entry->actor_id);
        $this->assertSame(['phone' => ['old' => '111', 'new' => '222'], 'city' => ['old' => null, 'new' => 'Łódź']], $entry->changes);
    }

    public function test_deactivating_and_reactivating_each_record_the_state(): void
    {
        $user = UserModel::factory()->create();
        DB::table('audit_changes')->delete();

        $this->send(new DeactivateUserCommand(Actor::user($this->super->id), $user->id));
        $deactivated = $this->entry();
        $this->assertNull($deactivated->changes['deactivated_at']['old']);
        $this->assertNotNull($deactivated->changes['deactivated_at']['new']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $deactivated->changes['deactivated_at']['new']);

        DB::table('audit_changes')->delete();
        $this->send(new ReactivateUserCommand(Actor::user($this->super->id), $user->id));
        $reactivated = $this->entry();
        $this->assertSame($deactivated->changes['deactivated_at']['new'], $reactivated->changes['deactivated_at']['old']);
        $this->assertNull($reactivated->changes['deactivated_at']['new']);
        $this->assertSame($this->super->id, $reactivated->actor_id);
    }

    public function test_changing_the_account_type_records_it_with_the_declaration_date_and_the_user_as_actor(): void
    {
        $user = UserModel::factory()->create();
        DB::table('audit_changes')->delete();

        $this->update($user, $user, ['accountType' => 'company', 'sellerDeclarationAccepted' => true, 'companyNip' => '5260001246', 'companyAddress' => 'Warszawa']);

        $entry = $this->entry();
        $this->assertSame($user->id, $entry->actor_id);
        $this->assertSame(['old' => 'private', 'new' => 'company'], $entry->changes['account_type']);
        $this->assertArrayHasKey('seller_declaration_accepted_at', $entry->changes);
        $this->assertArrayNotHasKey('account_type_changed_at', $entry->changes);

        $lines = app(ChangePresenter::class)->present($entry->record_type, $entry->changes, []);
        $line = collect($lines)->first(fn ($l) => $l->label === 'Typ konta');
        $this->assertSame('Prywatne', $line->old->text);
        $this->assertSame('Firmowe', $line->new->text);
    }

    public function test_granting_and_revoking_an_extra_permission_are_list_changes(): void
    {
        $user = UserModel::factory()->admin()->create();
        DB::table('audit_changes')->delete();

        $this->update($this->super, $user, ['extraPermissions' => ['users.impersonate']]);
        $granted = $this->entry();
        $this->assertSame([], $granted->changes);
        $this->assertSame(['extra_permissions' => ['added' => ['users.impersonate'], 'removed' => []]], $granted->relation_changes);

        $lines = app(ChangePresenter::class)->present($granted->record_type, [], $granted->relation_changes);
        $this->assertSame('Dodatkowe uprawnienia', $lines[0]->label);
        $this->assertSame('Wchodzenie na konta innych użytkowników', $lines[0]->added[0]->text);

        DB::table('audit_changes')->delete();
        $this->update($this->super, $user, ['extraPermissions' => []]);
        $this->assertSame(['extra_permissions' => ['added' => [], 'removed' => ['users.impersonate']]], $this->entry()->relation_changes);
    }

    public function test_setting_and_clearing_the_password_change_marker_are_recorded(): void
    {
        $user = UserModel::factory()->create();
        DB::table('audit_changes')->delete();

        $this->send(new SetPasswordChangeRequirementCommand(Actor::user($this->super->id), $user->id, true));
        $this->assertSame(['password_change_required' => ['old' => 0, 'new' => 1]], $this->entry()->changes);

        DB::table('audit_changes')->delete();
        $this->send(new SetPasswordChangeRequirementCommand(Actor::user($this->super->id), $user->id, false));
        $this->assertSame(['password_change_required' => ['old' => 1, 'new' => 0]], $this->entry()->changes);
    }

    public function test_a_password_change_leaves_no_password_value(): void
    {
        $user = UserModel::factory()->create();
        DB::table('audit_changes')->delete();

        $this->send(new ChangeUserPasswordCommand(Actor::user($this->super->id), $user->id, 'Nowe-haslo-12345'));

        $entry = $this->entry();
        $this->assertSame(['password_change_required' => ['old' => 0, 'new' => 1]], $entry->changes);
        $this->assertStringNotContainsString('Nowe-haslo', json_encode($entry->changes));
    }

    public function test_the_session_guards_remember_token_write_needs_no_command_and_leaves_no_entry(): void
    {
        $user = UserModel::query()->findOrFail($this->super->id);

        $user->setRememberToken('abc');
        $user->save();

        $this->assertSame('abc', $user->fresh()->remember_token);
        $this->assertSame(0, ChangeModel::query()->count());
    }

    public function test_a_guest_redeeming_a_link_is_recorded_as_the_system_with_a_named_reason(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->passwordChangeRequired()->create();
        $this->send(new SendPasswordLinkCommand(Actor::user($this->super->id), $user->id, 'reset'));
        $link = $this->sentLink($user, PasswordLinkKind::Reset);
        DB::table('audit_changes')->delete();

        $this->send(new SetPasswordFromLinkCommand(Actor::system('users::actors.password_reset_redeemed'), $link['email'], $link['token'], 'reset', 'brand-new-pass'));

        $entry = $this->entry();
        $this->assertNull($entry->actor_id);
        $this->assertSame('users::actors.password_reset_redeemed', $entry->actor_reason);
        $this->assertSame(['password_change_required' => ['old' => 1, 'new' => 0]], $entry->changes);
    }

    public function test_the_dev_user_console_command_runs_as_a_named_system_actor(): void
    {
        $this->artisan('dev:create-user')
            ->expectsQuestion('Name', 'Dev')
            ->expectsQuestion('Email', 'dev@example.com')
            ->expectsQuestion('Password (leave blank to use "password")', 'password')
            ->assertSuccessful();

        $entry = $this->entry();
        $this->assertSame(ChangeKind::Created, $entry->kind);
        $this->assertSame('core::common.actors.dev_create_user', $entry->actor_reason);
    }
}
