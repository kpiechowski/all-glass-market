<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\PlainPassword;
use PHPUnit\Framework\TestCase;

class PlainPasswordTest extends TestCase
{
    public function test_it_accepts_eight_characters(): void
    {
        $this->assertSame('12345678', PlainPassword::from('12345678')->value());
    }

    public function test_it_rejects_a_shorter_password(): void
    {
        $this->expectException(UserRefused::class);

        PlainPassword::from('1234567');
    }
}
