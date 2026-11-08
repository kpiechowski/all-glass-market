<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\UserInterface;

use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Audit\UserInterface\Filament\Pages\HistoryAndLogs\HistoryAndLogs;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Tests\TestCase;

/** The Nieudane zadania tab of "Historia i logi" (FR-18, D-24a). */
class FailedJobsTabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        /* Retry re-queues the payload: it must land in the jobs table, not run inline. */
        Testable::macro('assertTableTotal', function (int $expected): Testable {
            Assert::assertSame($expected, $this->instance()->getTableRecords()->total());

            return $this;
        });
        config(['queue.default' => 'database']);
    }

    private function failJob(string $message = 'Boom'): string
    {
        $payload = json_encode(['uuid' => uniqid(), 'displayName' => 'App\\Modules\\Offers\\Application\\Jobs\\SyncOffer', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => ['commandName' => 'stdClass', 'command' => serialize(new \stdClass)]]);

        return (string) app(FailedJobProviderInterface::class)->log('database', 'default', $payload, new RuntimeException($message));
    }

    private function tab(?UserModel $user = null): Testable
    {
        $this->actingAs($user ?? UserModel::factory()->superAdmin()->create());

        return Livewire::withQueryParams(['zakladka' => 'zadania'])->test(HistoryAndLogs::class);
    }

    public function test_it_lists_failed_jobs_with_name_time_and_error(): void
    {
        $id = $this->failJob('Boom');

        $this->tab()
            ->assertSee('SyncOffer')
            ->assertSee('Boom')
            ->assertTableTotal(1)
            ->mountTableAction('details', $id)
            ->assertMountedActionModalSee(['Offers', 'RuntimeException: Boom', 'default']);
    }

    public function test_it_shows_an_empty_state(): void
    {
        $this->tab()->assertSee('Brak nieudanych zadań');
    }

    public function test_retry_puts_the_job_back_on_the_queue_without_confirmation(): void
    {
        $id = $this->failJob();
        $page = $this->tab();
        $changes = ChangeModel::query()->count();

        $page->callTableAction('retry', $id);

        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame($changes, ChangeModel::query()->count());
    }

    public function test_a_retried_job_that_fails_again_shows_the_new_error(): void
    {
        $first = $this->failJob('First');
        $this->tab()->callTableAction('retry', $first);
        $this->failJob('Second');

        $this->tab()->assertSee('Second')->assertDontSee('First')->assertTableTotal(1);
    }

    public function test_remove_asks_for_confirmation_then_forgets_the_job(): void
    {
        $id = $this->failJob();

        $this->tab()
            ->mountTableAction('remove', $id)
            ->assertMountedActionModalSee(['Usunąć nieudane zadanie?', 'Usuń zadanie']);

        $this->assertSame(1, DB::table('failed_jobs')->count());

        Livewire::withQueryParams(['zakladka' => 'zadania'])->test(HistoryAndLogs::class)
            ->callTableAction('remove', $id);

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_the_tab_is_closed_without_the_permission(): void
    {
        $this->failJob();
        $this->actingAs(UserModel::factory()->create());

        Livewire::withQueryParams(['zakladka' => 'zadania'])->test(HistoryAndLogs::class)->assertForbidden();
    }
}
