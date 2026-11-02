<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Users\Infrastructure\Notifications;

use App\Modules\Users\Domain\Enums\PasswordLinkKind;
use App\Modules\Users\Infrastructure\Notifications\PasswordLinkNotification;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordLinkNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_reset_mail_boilerplate_is_in_polish(): void
    {
        $html = (string) (new PasswordLinkNotification(PasswordLinkKind::Reset, 'https://example.test/reset'))
            ->toMail(UserModel::factory()->create())
            ->render();

        $this->assertStringContainsString('Pozdrawiamy,', $html);
        $this->assertStringContainsString('Wszelkie prawa zastrzeżone.', $html);
        $this->assertStringContainsString('Jeśli masz problem z kliknięciem przycisku', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
    }
}
