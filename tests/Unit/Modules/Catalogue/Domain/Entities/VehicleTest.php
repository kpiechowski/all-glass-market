<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Entities\Vehicle;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleDetails;
use App\Modules\Catalogue\Domain\ValueObjects\VehicleName;
use PHPUnit\Framework\TestCase;

class VehicleTest extends TestCase
{
    private function value(int $id, Dictionary $dictionary, bool $withdrawn = false): DictionaryValue
    {
        return DictionaryValue::reconstitute($id, $dictionary, DictionaryValueName::from('Wartość '.$id), $withdrawn);
    }

    private function details(): VehicleDetails
    {
        return VehicleDetails::from('A4', 2008, null, 'B8', '1234');
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::reconstitute(1, VehicleName::from('AUDI | A4'), $this->details(), 10, 20);
    }

    public function test_it_is_created_with_a_make_and_an_optional_body(): void
    {
        $vehicle = Vehicle::create(
            VehicleName::from('AUDI | A4'),
            $this->details(),
            $this->value(10, Dictionary::VehicleMake),
            null,
            nameIsTaken: false,
        );

        $this->assertNull($vehicle->id());
        $this->assertSame('AUDI | A4', $vehicle->name()->value);
        $this->assertSame(10, $vehicle->makeId());
        $this->assertNull($vehicle->bodyId());
    }

    public function test_a_taken_name_is_refused(): void
    {
        $this->expectException(VehicleRefused::class);

        Vehicle::create(VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::VehicleMake), null, nameIsTaken: true);
    }

    public function test_a_value_of_another_dictionary_is_refused_as_make_and_body(): void
    {
        $this->assertRefused('make_is_not_a_make', fn () => Vehicle::create(
            VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::Colour), null, false,
        ));
        $this->assertRefused('body_is_not_a_body', fn () => Vehicle::create(
            VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::VehicleMake), $this->value(20, Dictionary::VehicleMake), false,
        ));
    }

    public function test_a_withdrawn_make_or_body_cannot_be_newly_chosen(): void
    {
        $this->assertRefused('make_is_withdrawn', fn () => Vehicle::create(
            VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::VehicleMake, withdrawn: true), null, false,
        ));
        $this->assertRefused('body_is_withdrawn', fn () => $this->vehicle()->change(
            VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::VehicleMake), $this->value(21, Dictionary::VehicleBody, withdrawn: true), false,
        ));
        $this->assertRefused('make_is_withdrawn', fn () => $this->vehicle()->change(
            VehicleName::from('x'), $this->details(), $this->value(11, Dictionary::VehicleMake, withdrawn: true), null, false,
        ));
    }

    public function test_a_withdrawn_make_and_body_already_on_the_vehicle_survive_a_change(): void
    {
        $vehicle = $this->vehicle();

        $vehicle->change(
            VehicleName::from('Nowa nazwa'),
            VehicleDetails::from('A6', null, null, null, null),
            $this->value(10, Dictionary::VehicleMake, withdrawn: true),
            $this->value(20, Dictionary::VehicleBody, withdrawn: true),
            nameIsTaken: false,
        );

        $this->assertSame('Nowa nazwa', $vehicle->name()->value);
        $this->assertSame(10, $vehicle->makeId());
        $this->assertSame(20, $vehicle->bodyId());
        $this->assertSame('A6', $vehicle->details()->model);
    }

    public function test_the_body_can_be_removed(): void
    {
        $vehicle = $this->vehicle();

        $vehicle->change(VehicleName::from('x'), $this->details(), $this->value(10, Dictionary::VehicleMake), null, false);

        $this->assertNull($vehicle->bodyId());
    }

    private function assertRefused(string $key, callable $action): void
    {
        try {
            $action();
        } catch (VehicleRefused $exception) {
            $this->assertSame('catalogue::exceptions.vehicle.'.$key, $exception->translationKey);

            return;
        }

        $this->fail('Expected a refusal: '.$key);
    }
}
