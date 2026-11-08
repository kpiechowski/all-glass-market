<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Commands\CleanUpExpiredChanges;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Audit\Application\Commands\CleanUpExpiredChanges\CleanUpExpiredChangesCommand;
use App\Modules\Audit\Domain\Contracts\RetentionPeriod;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CleanUpExpiredChangesCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function entryAged(int $days, int $extraSeconds = 0): ChangeModel
    {
        return ChangeModel::factory()->bySystem()->create(['occurred_at' => now()->subDays($days)->subSeconds($extraSeconds)]);
    }

    private function cleanUp(?Actor $actor = null): int
    {
        return app(CommandBus::class)->send(new CleanUpExpiredChangesCommand($actor ?? Actor::system('audit::actors.retention_cleanup')));
    }

    public function test_it_removes_entries_older_than_the_period_and_keeps_newer_ones(): void
    {
        $old = $this->entryAged(366);
        $recent = $this->entryAged(364);

        $removed = $this->cleanUp();

        $this->assertSame(1, $removed);
        $this->assertNull(ChangeModel::find($old->id));
        $this->assertNotNull(ChangeModel::find($recent->id));
    }

    public function test_the_period_is_counted_from_each_entrys_time_and_is_configurable(): void
    {
        config(['audit.retention_days' => 30]);
        $old = $this->entryAged(31);
        $recent = $this->entryAged(29);

        $this->cleanUp();

        $this->assertNull(ChangeModel::find($old->id));
        $this->assertNotNull(ChangeModel::find($recent->id));
    }

    public function test_the_period_comes_from_the_retention_period_port(): void
    {
        $this->app->bind(RetentionPeriod::class, fn () => new class implements RetentionPeriod
        {
            public function days(): int
            {
                return 5;
            }
        });
        $old = $this->entryAged(6);
        $recent = $this->entryAged(4);

        $this->cleanUp();

        $this->assertNull(ChangeModel::find($old->id));
        $this->assertNotNull(ChangeModel::find($recent->id));
    }

    public function test_it_removes_a_restore_source_without_touching_the_restoring_entry(): void
    {
        $source = $this->entryAged(400);
        $restore = ChangeModel::factory()->bySystem()->restoredFrom($source)->create(['occurred_at' => now()]);

        $this->cleanUp();

        $this->assertNull(ChangeModel::find($source->id));
        $this->assertNotNull(ChangeModel::find($restore->id));
    }

    public function test_it_is_a_no_op_when_nothing_expired(): void
    {
        $this->entryAged(1);

        $this->assertSame(0, $this->cleanUp());
        $this->assertSame(1, ChangeModel::count());
    }

    public function test_it_refuses_a_user_actor(): void
    {
        $old = $this->entryAged(400);
        $user = UserModel::factory()->superAdmin()->create();

        try {
            $this->cleanUp(Actor::user($user->id));
            $this->fail('A user must not run the retention cleanup.');
        } catch (AuthorizationException) {
            $this->assertNotNull(ChangeModel::find($old->id));
        }
    }
}
