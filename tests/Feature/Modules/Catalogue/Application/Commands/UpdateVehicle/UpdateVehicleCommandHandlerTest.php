<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\UpdateVehicle;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\UpdateVehicle\UpdateVehicleCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-07, FR-08 (name never rewritten), RULE-09, FR-24 (withdrawn values survive a save). */
class UpdateVehicleCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    private function value(Dictionary $dictionary, bool $withdrawn = false): DictionaryValueModel
    {
        $factory = DictionaryValueModelFactory::new()->of($dictionary);

        return ($withdrawn ? $factory->withdrawn() : $factory)->create();
    }

    private function send(Actor $actor, VehicleModel $vehicle, string $name, ?int $makeId = null, ?int $bodyId = null, ?int $yearFrom = null, ?int $yearTo = null): void
    {
        app(CommandBus::class)->send(new UpdateVehicleCommand(
            $actor, $vehicle->id, $name, $makeId ?? $vehicle->make_id, 'Nowy model', $bodyId, $yearFrom, $yearTo, null, null,
        ));
    }

    public function test_an_admin_changes_the_fields_and_the_name_is_exactly_what_was_given(): void
    {
        $vehicle = VehicleModelFactory::new()->named('Stara nazwa')->create();
        $newMake = $this->value(Dictionary::VehicleMake);

        $this->send($this->actor(), $vehicle, 'Moja nazwa', $newMake->id, null, 2001, 2003);

        $fresh = $vehicle->fresh();
        $this->assertSame('Moja nazwa', $fresh->name);
        $this->assertSame($newMake->id, $fresh->make_id);
        $this->assertSame('Nowy model', $fresh->model);
        $this->assertSame(2001, $fresh->year_from);
    }

    public function test_changing_a_field_does_not_rewrite_the_name(): void
    {
        $vehicle = VehicleModelFactory::new()->named('Ręczna nazwa')->create();

        $this->send($this->actor(), $vehicle, 'Ręczna nazwa', null, null, 2001, 2003);

        $this->assertSame('Ręczna nazwa', $vehicle->fresh()->name);
    }

    public function test_a_seller_is_refused(): void
    {
        $vehicle = VehicleModelFactory::new()->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user(UserModel::factory()->create()->id), $vehicle, 'x');
    }

    public function test_the_vehicle_may_keep_its_own_name_but_not_another_vehicles(): void
    {
        $vehicle = VehicleModelFactory::new()->named('Audi A4')->create();
        VehicleModelFactory::new()->named('BMW 3')->create();

        $this->send($this->actor(), $vehicle, 'AUDI A4');
        $this->assertSame('AUDI A4', $vehicle->fresh()->name);

        $this->assertRefused('name_is_taken', fn () => $this->send($this->actor(), $vehicle, 'bmw 3'));
        $this->assertSame('AUDI A4', $vehicle->fresh()->name);
    }

    public function test_years_out_of_order_are_refused(): void
    {
        $vehicle = VehicleModelFactory::new()->create();

        $this->assertRefused('years_are_out_of_order', fn () => $this->send($this->actor(), $vehicle, 'x', null, null, 2010, 2008));
    }

    public function test_a_withdrawn_make_and_body_already_on_the_vehicle_survive_a_save(): void
    {
        $make = $this->value(Dictionary::VehicleMake, withdrawn: true);
        $body = $this->value(Dictionary::VehicleBody, withdrawn: true);
        $vehicle = VehicleModelFactory::new()->ofMake($make->id)->withBody($body->id)->create();

        $this->send($this->actor(), $vehicle, 'Zmieniona', $make->id, $body->id, 2001);

        $fresh = $vehicle->fresh();
        $this->assertSame($make->id, $fresh->make_id);
        $this->assertSame($body->id, $fresh->body_id);
        $this->assertSame('Zmieniona', $fresh->name);
    }

    public function test_a_withdrawn_make_or_body_cannot_be_newly_chosen(): void
    {
        $vehicle = VehicleModelFactory::new()->create();

        $this->assertRefused('make_is_withdrawn', fn () => $this->send($this->actor(), $vehicle, 'x', $this->value(Dictionary::VehicleMake, withdrawn: true)->id));
        $this->assertRefused('body_is_withdrawn', fn () => $this->send($this->actor(), $vehicle, 'x', null, $this->value(Dictionary::VehicleBody, withdrawn: true)->id));
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
