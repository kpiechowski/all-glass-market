<?php

declare(strict_types=1);

namespace Tests\Feature\Core\UserInterface;

use App\Core\Contracts\PanelNotifier;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelNotifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_puts_a_notification_in_each_recipients_bell_and_skips_unknown_users(): void
    {
        $first = UserModel::factory()->create();
        $second = UserModel::factory()->admin()->create();
        $bystander = UserModel::factory()->create();

        app(PanelNotifier::class)->notify(
            [$first->id, $second->id, 9999], 'Tytuł', '<b>x</b>', 'warning',
            [['label' => 'Otwórz', 'url' => '/a', 'markAsRead' => true]],
        );

        $this->assertSame(1, $first->notifications()->count());
        $this->assertSame(1, $second->notifications()->count());
        $this->assertSame(0, $bystander->notifications()->count());
        $data = $first->notifications()->first()->data;
        $this->assertSame('Tytuł', $data['title']);
        $this->assertSame('warning', $data['status']);
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', $data['body']);
        $this->assertSame('/a', $data['actions'][0]['url']);
    }
}
