<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Audit\Application\Commands\RemoveFailedJob;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Audit\Application\Commands\RemoveFailedJob\RemoveFailedJobCommand;
use App\Modules\Audit\Domain\Exceptions\FailedJobNotFound;
use App\Modules\Audit\Infrastructure\Persistence\Models\ChangeModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RemoveFailedJobCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'database']);
    }

    private function failJob(): string
    {
        $payload = json_encode(['uuid' => uniqid(), 'displayName' => 'App\\Jobs\\SyncOffer', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call', 'data' => ['commandName' => 'stdClass', 'command' => serialize(new \stdClass)]]);

        return (string) app(FailedJobProviderInterface::class)->log('database', 'default', $payload, new RuntimeException('Boom'));
    }

    private function send(string $id, Actor $actor): void
    {
        app(CommandBus::class)->send(new RemoveFailedJobCommand($actor, $id));
    }

    public function test_a_holder_of_the_permission_removes_the_job_and_writes_no_change_entry(): void
    {
        $id = $this->failJob();
        $user = UserModel::factory()->superAdmin()->create();
        $changes = ChangeModel::query()->count();

        $this->send($id, Actor::user($user->id));

        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame($changes, ChangeModel::query()->count());
    }

    public function test_without_the_permission_it_is_refused_and_the_job_stays(): void
    {
        $id = $this->failJob();
        $user = UserModel::factory()->create();

        try {
            $this->send($id, Actor::user($user->id));
            $this->fail('expected a refusal');
        } catch (AuthorizationException) {
        }

        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('jobs')->count());
    }

    public function test_a_job_that_is_gone_is_a_domain_refusal(): void
    {
        $user = UserModel::factory()->superAdmin()->create();

        $this->expectException(FailedJobNotFound::class);

        $this->send('missing', Actor::user($user->id));
    }
}
