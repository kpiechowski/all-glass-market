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

    public function test_line_breaks_of_the_body_are_kept_and_the_rest_stays_escaped(): void
    {
        $user = UserModel::factory()->create();

        app(PanelNotifier::class)->notify([$user->id], 'Tytuł', "<i>a</i>\nb");

        $this->assertSame("&lt;i&gt;a&lt;/i&gt;<br />\nb", $user->notifications()->first()->data['body']);
    }

    public function test_retract_removes_only_the_notifications_linking_to_exactly_that_url(): void
    {
        $user = UserModel::factory()->create();
        $notifier = app(PanelNotifier::class);

        foreach (['A' => '/admin/journal_entries/1', 'B' => '/admin/journal_entries/10', 'C' => '/admin/journal_entries/1/edit'] as $title => $path) {
            $notifier->notify([$user->id], $title, '', 'info', [['label' => 'x', 'url' => 'http://localhost'.$path]]);
        }

        $notifier->retract('http://localhost/admin/journal_entries/1');

        $this->assertEqualsCanonicalizing(['B', 'C'], $user->notifications()->get()->pluck('data.title')->all());
    }
}
