<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Application\Commands\RequestPasswordReset;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Users\Application\Commands\RequestPasswordReset\RequestPasswordResetCommand;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** FR-11, WF-03, RULE-14: the same quiet outcome whether or not a link goes out. */
class RequestPasswordResetCommandHandlerTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    private function request(string $email, ?Actor $actor = null): void
    {
        app(CommandBus::class)->send(new RequestPasswordResetCommand($actor ?? Actor::system('test'), $email));
    }

    public function test_a_known_email_gets_a_reset_link(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create(['email' => 'jan@example.com']);

        $this->request('jan@example.com');

        $this->assertStringContainsString('password-reset', $this->sentLink($user, PasswordLinkKind::Reset)['url']);
    }

    public function test_an_unknown_email_sends_nothing_and_says_nothing(): void
    {
        $this->emailSendingWorks();

        $this->request('nobody@example.com');

        Notification::assertNothingSent();
    }

    public function test_a_user_with_an_unregistered_role_gets_no_link(): void
    {
        $this->emailSendingWorks();
        UserModel::factory()->withRole('ghost')->create(['email' => 'ghost@example.com']);

        $this->request('ghost@example.com');

        Notification::assertNothingSent();
    }

    public function test_nothing_is_sent_while_email_sending_is_unavailable(): void
    {
        Notification::fake();
        UserModel::factory()->create(['email' => 'jan@example.com']);

        $this->request('jan@example.com');

        Notification::assertNothingSent();
    }

    public function test_a_person_cannot_send_it(): void
    {
        $user = UserModel::factory()->create();

        $this->expectException(AuthorizationException::class);
        $this->request('jan@example.com', Actor::user($user->id));
    }

    public function test_a_deactivated_user_gets_no_link_and_the_outcome_stays_the_same(): void
    {
        $this->emailSendingWorks();
        UserModel::factory()->deactivated()->create(['email' => 'off@example.com']);

        $this->request('off@example.com');

        Notification::assertNothingSent();
    }
}
