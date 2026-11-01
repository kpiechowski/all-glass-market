<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Auth;

use App\Modules\Users\Domain\Contracts\PasswordLinks;
use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CapturesPasswordLinks;
use Tests\TestCase;

/** RULE-14: reset and invitation links have separate tokens and lifetimes. */
class LaravelPasswordLinksTest extends TestCase
{
    use CapturesPasswordLinks, RefreshDatabase;

    public function test_a_sent_link_is_stored_in_the_table_of_its_kind(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create();
        $links = app(PasswordLinks::class);

        $links->send($user->id, PasswordLinkKind::Reset);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('invitation_tokens', ['email' => $user->email]);

        $links->send($user->id, PasswordLinkKind::Invitation);
        $this->assertDatabaseHas('invitation_tokens', ['email' => $user->email]);
    }

    public function test_redeeming_returns_the_user_id_once_and_null_for_a_wrong_token_or_email(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create();
        $links = app(PasswordLinks::class);
        $links->send($user->id, PasswordLinkKind::Reset);
        $link = $this->sentLink($user, PasswordLinkKind::Reset);

        $this->assertNull($links->redeem($link['email'], 'wrong-token', PasswordLinkKind::Reset));
        $this->assertNull($links->redeem('other@example.com', $link['token'], PasswordLinkKind::Reset));
        $this->assertSame($user->id, $links->redeem($link['email'], $link['token'], PasswordLinkKind::Reset));
        $this->assertNull($links->redeem($link['email'], $link['token'], PasswordLinkKind::Reset));
    }

    public function test_the_link_in_the_mail_is_signed(): void
    {
        $this->emailSendingWorks();
        $user = UserModel::factory()->create();

        app(PasswordLinks::class)->send($user->id, PasswordLinkKind::Invitation);

        $this->assertStringContainsString('signature=', $this->sentLink($user, PasswordLinkKind::Invitation)['url']);
    }
}
