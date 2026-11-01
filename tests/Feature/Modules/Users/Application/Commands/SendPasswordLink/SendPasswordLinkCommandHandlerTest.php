<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\SendPasswordLink;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\SendPasswordLink\SendPasswordLinkCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** FR-26, WF-03, WF-04. */
class SendPasswordLinkCommandHandlerTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    private function send(Actor $actor, UserModel $target, PasswordLinkKind $kind): void
    {
        app(CommandBus::class)->send(new SendPasswordLinkCommand($actor, $target->id, $kind->value));
    }

    public function test_an_admin_re_sends_an_invitation_and_a_reset_link(): void
    {
        $this->emailSendingWorks();
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();

        $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Invitation);
        $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Reset);

        $this->assertStringContainsString('invitation', $this->sentLink($user, PasswordLinkKind::Invitation)['url']);
        $this->assertStringContainsString('password-reset', $this->sentLink($user, PasswordLinkKind::Reset)['url']);
    }

    public function test_it_is_refused_while_email_sending_is_unavailable(): void
    {
        Notification::fake();
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();

        try {
            $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Reset);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.email_sending_unavailable', $e->translationKey);
        }

        Notification::assertNothingSent();
    }

    public function test_it_is_refused_for_a_user_with_an_unregistered_role(): void
    {
        $this->emailSendingWorks();
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->withRole('ghost')->create();

        $this->expectException(UserRefused::class);
        $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Reset);
    }

    public function test_an_admin_cannot_send_a_link_to_a_super_admin(): void
    {
        $this->emailSendingWorks();
        $admin = UserModel::factory()->admin()->create();
        $super = UserModel::factory()->superAdmin()->create();

        $this->expectException(UserRefused::class);
        $this->send(Actor::user($admin->id), $super, PasswordLinkKind::Reset);
    }

    public function test_a_user_without_the_permission_is_rejected(): void
    {
        $this->emailSendingWorks();
        $seller = UserModel::factory()->create();
        $other = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->send(Actor::user($seller->id), $other, PasswordLinkKind::Reset);
    }

    public function test_a_failed_send_is_reported_and_leaves_no_usable_token(): void
    {
        config(['mail.default' => 'smtp']);
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->create();
        $this->app->instance(Dispatcher::class, new class implements Dispatcher
        {
            public function send($notifiables, $notification): void
            {
                throw new \RuntimeException('smtp down');
            }

            public function sendNow($notifiables, $notification, ?array $channels = null): void
            {
                throw new \RuntimeException('smtp down');
            }
        });

        $this->expectException(UserRefused::class);

        try {
            $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Reset);
        } finally {
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        }
    }

    public function test_a_link_for_a_deactivated_user_is_refused(): void
    {
        $this->emailSendingWorks();
        $admin = UserModel::factory()->admin()->create();
        $user = UserModel::factory()->deactivated()->create();

        try {
            $this->send(Actor::user($admin->id), $user, PasswordLinkKind::Reset);
            $this->fail('Expected a refusal.');
        } catch (UserRefused $e) {
            $this->assertSame('users::exceptions.password_link_not_allowed', $e->translationKey);
        }

        Notification::assertNothingSent();
    }
}
