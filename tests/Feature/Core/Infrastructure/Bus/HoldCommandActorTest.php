<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Infrastructure\Bus;

use App\Core\Domain\Actor;
use App\Core\Infrastructure\Bus\ActorStack;
use App\Core\Infrastructure\Bus\HoldCommandActor;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Modelling\CommandBus;
use Tests\Fixtures\Core\ActedCommand;
use Tests\Fixtures\Core\ActedHandler;
use Tests\TestCase;

class HoldCommandActorTest extends TestCase
{
    private CommandBus $bus;

    private ActedHandler $handler;

    private ActorStack $stack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stack = new ActorStack;
        $this->handler = new ActedHandler($this->stack);

        $this->bus = EcotoneLite::bootstrapFlowTesting(
            [ActedHandler::class, HoldCommandActor::class],
            [$this->handler, new HoldCommandActor($this->stack)],
        )->getGateway(CommandBus::class);
    }

    public function test_the_handler_sees_the_actor_the_command_carries(): void
    {
        $actor = Actor::user(7);

        $this->bus->send(new ActedCommand($actor));

        $this->assertSame([$actor], $this->handler->seen);
    }

    public function test_a_nested_command_runs_as_its_own_actor_and_the_outer_one_comes_back(): void
    {
        $outer = Actor::user(7);

        $this->bus->send(new ActedCommand($outer, nested: true));

        $this->assertCount(3, $this->handler->seen);
        $this->assertSame($outer, $this->handler->seen[0]);
        $this->assertSame('inner job', $this->handler->seen[1]->reason);
        $this->assertSame($outer, $this->handler->seen[2]);
    }
}
