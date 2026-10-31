<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Domain;

use App\Core\Domain\Actor;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ActorTest extends TestCase
{
    public function test_a_user_actor_carries_the_user_id_and_is_not_system(): void
    {
        $actor = Actor::user(7);

        $this->assertSame(7, $actor->id);
        $this->assertNull($actor->reason);
        $this->assertFalse($actor->isSystem());
    }

    public function test_a_system_actor_carries_its_reason_and_no_id(): void
    {
        $actor = Actor::system('offers:expire-stale');

        $this->assertNull($actor->id);
        $this->assertSame('offers:expire-stale', $actor->reason);
        $this->assertTrue($actor->isSystem());
    }

    public function test_a_system_actor_must_name_its_reason(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Actor::system('  ');
    }

    public function test_a_user_actor_must_have_a_real_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Actor::user(0);
    }
}
