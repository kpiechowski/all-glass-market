<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Users\Domain\ValueObjects;

use App\Modules\Users\Domain\Exceptions\UserRefused;
use App\Modules\Users\Domain\ValueObjects\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test_it_accepts_a_well_formed_address_and_lowercases_it(): void
    {
        $this->assertSame('jan@example.com', Email::from(' Jan@Example.com ')->value());
    }

    public function test_it_rejects_a_malformed_address(): void
    {
        $this->expectException(UserRefused::class);

        Email::from('not-an-email');
    }
}
