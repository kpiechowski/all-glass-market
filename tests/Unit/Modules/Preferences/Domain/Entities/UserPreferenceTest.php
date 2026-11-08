<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Preferences\Domain\Entities;

use App\Modules\Preferences\Domain\Entities\UserPreference;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use PHPUnit\Framework\TestCase;

class UserPreferenceTest extends TestCase
{
    public function test_a_new_preference_belongs_to_its_user_and_keeps_its_value(): void
    {
        $preference = UserPreference::choose(7, 'quick_access', ['users.list']);

        $this->assertNull($preference->id());
        $this->assertSame(7, $preference->userId());
        $this->assertSame('quick_access', $preference->key());
        $this->assertSame(['users.list'], $preference->value());
    }

    public function test_changing_replaces_the_value(): void
    {
        $preference = UserPreference::reconstitute(3, 7, 'quick_access', ['users.list']);

        $preference->change(['audit.log', 'users.list']);

        $this->assertSame(['audit.log', 'users.list'], $preference->value());
        $this->assertSame(3, $preference->id());
    }

    public function test_it_needs_a_real_user(): void
    {
        $this->expectException(PreferenceRefused::class);

        UserPreference::choose(0, 'quick_access', []);
    }

    public function test_it_needs_a_key(): void
    {
        $this->expectException(PreferenceRefused::class);

        UserPreference::choose(7, ' ', []);
    }
}
