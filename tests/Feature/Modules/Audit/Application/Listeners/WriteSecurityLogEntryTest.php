<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Listeners;

use App\Core\Domain\Actor;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use App\Modules\Users\Application\Commands\SendPasswordLink\SendPasswordLinkCommand;
use App\Modules\Users\Application\Commands\SetPasswordFromLink\SetPasswordFromLinkCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use App\Modules\Users\UserInterface\Filament\Auth\Login;
use App\Modules\Users\UserInterface\Filament\Resources\Users\Pages\ViewUser;
use App\Modules\Users\UserInterface\Livewire\ImpersonationBanner;
use Ecotone\Modelling\CommandBus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** FR-17, RULE-01: each security event is one entry in the bezpieczeństwo channel, with no secret in it. */
class WriteSecurityLogEntryTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Filament::setCurrentPanel('admin');
        RateLimiter::clear('panel-login:seller@example.com|127.0.0.1');

        config(['logging.channels.bezpieczenstwo' => ['driver' => 'monolog', 'handler' => TestHandler::class]]);
        Log::forgetChannel('bezpieczenstwo');
        $this->handler = Log::channel('bezpieczenstwo')->getHandlers()[0];
    }

    /** @return list<LogRecord> */
    private function entries(): array
    {
        return $this->handler->getRecords();
    }

    private function assertOneEntry(string $event): LogRecord
    {
        $this->assertCount(1, $this->entries());
        $entry = $this->entries()[0];
        $this->assertSame($event, $entry->context['event']);
        $this->assertArrayHasKey('ip', $entry->context);
        $this->assertSame('127.0.0.1', $entry->context['ip']);
        $this->assertNotNull($entry->datetime);

        return $entry;
    }

    private function assertNoSecret(string ...$secrets): void
    {
        $dump = json_encode(array_map(fn (LogRecord $r): array => ['m' => $r->message, 'c' => $r->context, 'e' => $r->extra], $this->entries()), JSON_THROW_ON_ERROR);

        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $dump);
        }

        foreach ($this->entries() as $entry) {
            foreach (array_keys($entry->context) as $key) {
                $this->assertNotContains($key, ['password', 'token', 'remember_token', 'credentials']);
            }
        }
    }

    public function test_a_sign_in_is_logged_with_who_and_ip(): void
    {
        $user = UserModel::factory()->create(['email' => 'seller@example.com', 'password' => 'correct-pass']);

        Livewire::test(Login::class)->fillForm(['email' => 'seller@example.com', 'password' => 'correct-pass', 'remember' => true])->call('authenticate');

        $entry = $this->assertOneEntry('sign_in');
        $this->assertSame($user->id, $entry->context['user_id']);
        $this->assertSame('seller@example.com', $entry->context['email']);
        $this->assertSame('Logowanie', $entry->message);
        $this->assertNoSecret('correct-pass', (string) $user->fresh()->remember_token);
    }

    public function test_a_failed_sign_in_is_logged_with_the_tried_email(): void
    {
        UserModel::factory()->create(['email' => 'seller@example.com', 'password' => 'correct-pass']);

        Livewire::test(Login::class)->fillForm(['email' => 'seller@example.com', 'password' => 'wrong-pass'])->call('authenticate');

        $entry = $this->assertOneEntry('sign_in_failed');
        $this->assertNull($entry->context['user_id']);
        $this->assertSame('seller@example.com', $entry->context['email']);
        $this->assertNoSecret('wrong-pass', 'correct-pass');
    }

    public function test_a_failed_sign_in_for_an_unknown_email_is_logged_too(): void
    {
        Livewire::test(Login::class)->fillForm(['email' => 'nobody@example.com', 'password' => 'whatever-pass'])->call('authenticate');

        $this->assertSame('nobody@example.com', $this->assertOneEntry('sign_in_failed')->context['email']);
        $this->assertNoSecret('whatever-pass');
    }

    public function test_a_refused_deactivated_user_counts_as_a_failed_sign_in(): void
    {
        UserModel::factory()->deactivated()->create(['email' => 'seller@example.com', 'password' => 'correct-pass']);

        Livewire::test(Login::class)->fillForm(['email' => 'seller@example.com', 'password' => 'correct-pass'])->call('authenticate');

        $this->assertOneEntry('sign_in_failed');
        $this->assertNoSecret('correct-pass');
    }

    public function test_a_sign_out_is_logged(): void
    {
        $user = UserModel::factory()->admin()->create(['email' => 'seller@example.com']);
        $this->actingAs($user);
        $this->handler->clear();

        $this->post(Filament::getLogoutUrl())->assertRedirect();

        $entry = $this->assertOneEntry('sign_out');
        $this->assertSame($user->id, $entry->context['user_id']);
        $this->assertSame('seller@example.com', $entry->context['email']);
        $this->assertNoSecret((string) $user->remember_token);
    }

    public function test_a_password_reset_request_is_logged_only_when_a_link_goes_out(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create(['email' => 'jan@example.com']);

        app(CommandBus::class)->send(new RequestPasswordResetCommand(Actor::system('test'), 'nobody@example.com'));
        $this->assertSame([], $this->entries());

        app(CommandBus::class)->send(new RequestPasswordResetCommand(Actor::system('test'), 'jan@example.com'));

        $entry = $this->assertOneEntry('password_reset_requested');
        $this->assertSame($user->id, $entry->context['user_id']);
        $this->assertSame('jan@example.com', $entry->context['email']);
        $this->assertNoSecret($this->sentLink($user, PasswordLinkKind::Reset)['token']);
    }

    public function test_a_completed_password_reset_is_logged_and_an_invitation_is_not(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create(['email' => 'jan@example.com']);
        $admin = UserModel::factory()->admin()->create();
        $bus = app(CommandBus::class);

        $bus->send(new SendPasswordLinkCommand(Actor::user($admin->id), $user->id, 'invitation'));
        $invitation = $this->sentLink($user, PasswordLinkKind::Invitation);
        $bus->send(new SetPasswordFromLinkCommand(Actor::system('test'), $invitation['email'], $invitation['token'], 'invitation', 'invited-pass-1'));
        $this->assertSame([], $this->entries());

        $bus->send(new SendPasswordLinkCommand(Actor::user($admin->id), $user->id, 'reset'));
        $link = $this->sentLink($user, PasswordLinkKind::Reset);
        $this->handler->clear();
        $bus->send(new SetPasswordFromLinkCommand(Actor::system('test'), $link['email'], $link['token'], 'reset', 'brand-new-pass'));

        $entry = $this->assertOneEntry('password_reset_completed');
        $this->assertSame($user->id, $entry->context['user_id']);
        $this->assertNoSecret('brand-new-pass', $link['token'], $invitation['token']);
    }

    public function test_impersonation_start_and_end_name_both_users_and_log_no_sign_in(): void
    {
        $super = UserModel::factory()->superAdmin()->create(['email' => 'super@example.com']);
        $target = UserModel::factory()->admin()->create(['email' => 'target@example.com']);
        $this->actingAs($super);
        $this->handler->clear();

        Livewire::test(ViewUser::class, ['record' => $target->getKey()])->callAction('impersonate');

        $started = $this->assertOneEntry('impersonation_started');
        $this->assertSame($super->id, $started->context['user_id']);
        $this->assertSame('super@example.com', $started->context['email']);
        $this->assertSame($target->id, $started->context['target_user_id']);
        $this->assertSame('target@example.com', $started->context['target_email']);
        $this->handler->clear();

        Livewire::test(ImpersonationBanner::class)->call('leave');

        $ended = $this->assertOneEntry('impersonation_ended');
        $this->assertSame($super->id, $ended->context['user_id']);
        $this->assertSame($target->id, $ended->context['target_user_id']);
        $this->assertSame($super->id, Auth::id());
        $this->assertNoSecret((string) $super->remember_token, (string) $target->remember_token);
    }

    public function test_the_listener_writes_no_change_entry(): void
    {
        UserModel::factory()->create(['email' => 'seller@example.com', 'password' => 'correct-pass']);
        $before = ChangeModel::count();

        Livewire::test(Login::class)->fillForm(['email' => 'seller@example.com', 'password' => 'correct-pass'])->call('authenticate');

        $this->assertSame($before, ChangeModel::count());
    }
}
