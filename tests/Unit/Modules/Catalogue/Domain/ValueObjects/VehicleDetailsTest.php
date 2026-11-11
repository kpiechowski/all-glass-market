<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use PHPUnit\Framework\TestCase;

class VehicleDetailsTest extends TestCase
{
    public function test_empty_text_becomes_null_and_an_open_end_is_allowed(): void
    {
        $details = VehicleDetails::from(' ', 2008, null, '', '');

        $this->assertNull($details->model);
        $this->assertNull($details->generation);
        $this->assertNull($details->argicCode);
        $this->assertSame(2008, $details->yearFrom);
        $this->assertNull($details->yearTo);
    }

    public function test_years_from_after_years_to_is_refused(): void
    {
        $this->expectException(VehicleRefused::class);
        VehicleDetails::from(null, 2010, 2008, null, null);
    }

    public function test_equal_years_are_accepted(): void
    {
        $this->assertSame(2008, VehicleDetails::from(null, 2008, 2008, null, null)->yearTo);
    }

    public function test_an_argic_code_must_be_four_digits(): void
    {
        $this->assertSame('0123', VehicleDetails::from(null, null, null, null, '0123')->argicCode);

        foreach (['123', '12345', 'ABCD', '12 4'] as $bad) {
            try {
                VehicleDetails::from(null, null, null, null, $bad);
                $this->fail('Expected refusal for '.$bad);
            } catch (VehicleRefused $exception) {
                $this->assertSame('catalogue::exceptions.vehicle.argic_code_is_invalid', $exception->translationKey);
            }
        }
    }

    public function test_a_year_must_have_four_digits(): void
    {
        $this->expectException(VehicleRefused::class);
        VehicleDetails::from(null, 99, null, null, null);
    }

    public function test_over_long_text_is_refused(): void
    {
        $this->expectException(VehicleRefused::class);
        VehicleDetails::from(str_repeat('a', 256), null, null, null, null);
    }
}
