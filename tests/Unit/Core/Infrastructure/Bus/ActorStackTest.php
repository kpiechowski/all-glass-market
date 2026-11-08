<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Infrastructure\Bus;

use App\Core\Domain\Actor;
use App\Core\Exceptions\NoCurrentActorException;
use App\Core\Infrastructure\Bus\ActorStack;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ActorStackTest extends TestCase
{
    public function test_current_fails_outside_a_command(): void
    {
        $this->expectException(NoCurrentActorException::class);

        (new ActorStack)->current();
    }

    public function test_current_is_the_actor_of_the_running_command(): void
    {
        $stack = new ActorStack;
        $actor = Actor::user(1);

        $this->assertSame($actor, $stack->run($actor, fn () => $stack->current()));
    }

    public function test_a_nested_command_has_its_own_actor_and_the_outer_one_returns(): void
    {
        $stack = new ActorStack;
        $outer = Actor::user(1);
        $inner = Actor::system('nightly job');

        $seen = $stack->run($outer, fn () => [
            $stack->current(),
            $stack->run($inner, fn () => $stack->current()),
            $stack->current(),
        ]);

        $this->assertSame([$outer, $inner, $outer], $seen);
    }

    public function test_the_actor_is_released_when_the_command_throws(): void
    {
        $stack = new ActorStack;

        try {
            $stack->run(Actor::user(1), fn () => throw new RuntimeException('boom'));
        } catch (RuntimeException) {
        }

        $this->expectException(NoCurrentActorException::class);
        $stack->current();
    }

    public function test_a_command_without_an_actor_makes_none_current_even_inside_another(): void
    {
        $stack = new ActorStack;

        $this->expectException(NoCurrentActorException::class);

        $stack->run(Actor::user(1), fn () => $stack->run(null, fn () => $stack->current()));
    }
}
