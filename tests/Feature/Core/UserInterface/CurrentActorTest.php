<?php

declare(strict_types=1);

namespace Tests\Feature\Core\UserInterface;

use App\Core\Contracts\Authorizer;
use App\Core\Exceptions\AuthorizationException;
use App\Core\UserInterface\CurrentActor;
use App\Modules\Users\Domain\Models\User;
use Tests\Fixtures\Core\ArrayAuthorizer;
use Tests\Fixtures\Core\TestPermission;
use Tests\TestCase;

class CurrentActorTest extends TestCase
{
    public function test_get_returns_a_user_actor_for_the_logged_in_user(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 42]));

        $actor = CurrentActor::get();

        $this->assertSame(42, $actor->id);
        $this->assertFalse($actor->isSystem());
    }

    public function test_get_refuses_when_nobody_is_logged_in(): void
    {
        $this->expectException(AuthorizationException::class);

        CurrentActor::get();
    }

    public function test_can_asks_the_authorizer_about_the_logged_in_user(): void
    {
        $this->app->instance(Authorizer::class, new ArrayAuthorizer([TestPermission::Activate]));
        $this->actingAs((new User)->forceFill(['id' => 42]));

        $this->assertTrue(CurrentActor::can(TestPermission::Activate));
        $this->assertFalse(CurrentActor::can(TestPermission::Delete));
    }

    public function test_can_is_false_for_a_guest(): void
    {
        $this->app->instance(Authorizer::class, new ArrayAuthorizer([TestPermission::Activate]));

        $this->assertFalse(CurrentActor::can(TestPermission::Activate));
    }
}
