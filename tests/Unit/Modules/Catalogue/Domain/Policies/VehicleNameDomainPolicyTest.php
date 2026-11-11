<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Policies;

use App\Modules\Catalogue\Domain\Policies\VehicleNameDomainPolicy;
use PHPUnit\Framework\TestCase;

class VehicleNameDomainPolicyTest extends TestCase
{
    public function test_it_proposes_every_field_in_the_fr08_format(): void
    {
        $this->assertSame(
            'AUDI | A4 | SEDAN | B8 2008-2015 | 1234',
            VehicleNameDomainPolicy::propose('AUDI', 'A4', 'SEDAN', 'B8', 2008, 2015, '1234'),
        );
    }

    public function test_it_leaves_out_empty_fields(): void
    {
        $this->assertSame('AUDI', VehicleNameDomainPolicy::propose('AUDI', null, null, null, null, null, null));
        $this->assertSame('AUDI | A4 | 1234', VehicleNameDomainPolicy::propose('AUDI', ' A4 ', '', '', null, null, '1234'));
        $this->assertSame('AUDI | 2008-2015', VehicleNameDomainPolicy::propose('AUDI', null, null, null, 2008, 2015, null));
        $this->assertSame('AUDI | B8', VehicleNameDomainPolicy::propose('AUDI', null, null, 'B8', null, null, null));
    }

    public function test_it_writes_an_open_end_as_from_dash(): void
    {
        $this->assertSame('AUDI | A4 | B8 2008-', VehicleNameDomainPolicy::propose('AUDI', 'A4', null, 'B8', 2008, null, null));
    }

    public function test_it_proposes_nothing_without_a_make(): void
    {
        $this->assertSame('', VehicleNameDomainPolicy::propose(null, 'A4', null, null, null, null, null));
    }

    public function test_years_from_must_not_be_after_years_to(): void
    {
        $this->assertTrue(VehicleNameDomainPolicy::yearsAreOrdered(2008, 2008));
        $this->assertTrue(VehicleNameDomainPolicy::yearsAreOrdered(2008, null));
        $this->assertTrue(VehicleNameDomainPolicy::yearsAreOrdered(null, 2010));
        $this->assertFalse(VehicleNameDomainPolicy::yearsAreOrdered(2010, 2008));
    }
}
