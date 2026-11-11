<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use PHPUnit\Framework\TestCase;

class VehicleNameTest extends TestCase
{
    public function test_it_is_trimmed_and_compared_ignoring_case(): void
    {
        $name = VehicleName::from('  AUDI | A4 ');

        $this->assertSame('AUDI | A4', $name->value);
        $this->assertSame('audi | a4', $name->key());
    }

    public function test_a_blank_or_too_long_name_is_refused(): void
    {
        $this->expectException(VehicleRefused::class);
        VehicleName::from('   ');
    }

    public function test_a_name_over_the_limit_is_refused(): void
    {
        $this->expectException(VehicleRefused::class);
        VehicleName::from(str_repeat('a', 256));
    }
}
