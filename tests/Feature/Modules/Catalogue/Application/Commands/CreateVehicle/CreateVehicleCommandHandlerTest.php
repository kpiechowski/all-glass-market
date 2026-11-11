<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\CreateVehicle;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\CreateVehicle\CreateVehicleCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\VehicleRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-07, FR-08, RULE-07, RULE-09. */
class CreateVehicleCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function make(bool $withdrawn = false): int
    {
        $factory = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake);

        return ($withdrawn ? $factory->withdrawn() : $factory)->create()->id;
    }

    private function actor(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    private function send(Actor $actor, string $name = 'AUDI | A4', ?int $makeId = null, ?int $bodyId = null, ?int $yearFrom = null, ?int $yearTo = null, ?string $argic = null): int
    {
        return app(CommandBus::class)->send(new CreateVehicleCommand(
            $actor, $name, $makeId ?? $this->make(), 'A4', $bodyId, $yearFrom, $yearTo, 'B8', $argic,
        ));
    }

    public function test_an_admin_creates_a_vehicle_with_its_fields(): void
    {
        $makeId = $this->make();
        $bodyId = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->create()->id;

        $id = $this->send($this->actor(), '  AUDI | A4 ', $makeId, $bodyId, 2008, null, '1234');

        $vehicle = VehicleModel::findOrFail($id);
        $this->assertSame('AUDI | A4', $vehicle->name);
        $this->assertSame($makeId, $vehicle->make_id);
        $this->assertSame($bodyId, $vehicle->body_id);
        $this->assertSame('A4', $vehicle->model);
        $this->assertSame('B8', $vehicle->generation);
        $this->assertSame(2008, $vehicle->year_from);
        $this->assertNull($vehicle->year_to);
        $this->assertSame('1234', $vehicle->argic_code);
    }

    public function test_a_super_admin_may_create_too(): void
    {
        $this->assertIsInt($this->send(Actor::user(UserModel::factory()->superAdmin()->create()->id)));
    }

    public function test_a_seller_is_refused(): void
    {
        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user(UserModel::factory()->create()->id));
    }

    public function test_a_name_taken_in_another_letter_case_is_refused(): void
    {
        VehicleModelFactory::new()->named('Audi | A4')->create();

        $this->assertRefused('name_is_taken', fn () => $this->send($this->actor(), 'AUDI | a4'));
        $this->assertSame(1, VehicleModel::count());
    }

    public function test_a_blank_name_is_refused(): void
    {
        $this->assertRefused('name_is_blank', fn () => $this->send($this->actor(), '  '));
    }

    public function test_years_from_after_years_to_are_refused(): void
    {
        $this->assertRefused('years_are_out_of_order', fn () => $this->send($this->actor(), yearFrom: 2010, yearTo: 2008));
    }

    public function test_an_argic_code_that_is_not_four_digits_is_refused(): void
    {
        $this->assertRefused('argic_code_is_invalid', fn () => $this->send($this->actor(), argic: '12A4'));
    }

    public function test_a_withdrawn_make_or_body_cannot_be_chosen_for_a_new_vehicle(): void
    {
        $this->assertRefused('make_is_withdrawn', fn () => $this->send($this->actor(), makeId: $this->make(withdrawn: true)));

        $body = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->withdrawn()->create()->id;
        $this->assertRefused('body_is_withdrawn', fn () => $this->send($this->actor(), bodyId: $body));
    }

    public function test_a_value_of_another_dictionary_is_refused_as_make(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create()->id;

        $this->assertRefused('make_is_not_a_make', fn () => $this->send($this->actor(), makeId: $colour));
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
