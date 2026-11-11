<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue\DeleteDictionaryValueCommand;
use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\VehicleModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24: the delete seam knows the vehicles' use of make and body. */
class VehicleDictionaryUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_make_and_a_body_used_by_vehicles_report_their_use(): void
    {
        $body = DictionaryValueModelFactory::new()->of(Dictionary::VehicleBody)->create();
        $vehicle = VehicleModelFactory::new()->withBody($body->id)->create();
        VehicleModelFactory::new()->ofMake($vehicle->make_id)->create();
        $usages = app(DictionaryValueUsages::class);

        $makeUses = $usages->of(Dictionary::VehicleMake, $vehicle->make_id);
        $bodyUses = $usages->of(Dictionary::VehicleBody, $body->id);

        $this->assertSame([['catalogue::uses.vehicles', 2]], array_map(fn ($use): array => [$use->place, $use->count], $makeUses));
        $this->assertSame(1, $bodyUses[0]->count);
    }

    public function test_an_unused_value_and_other_dictionaries_report_nothing(): void
    {
        $unused = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();
        $vehicle = VehicleModelFactory::new()->create();
        $usages = app(DictionaryValueUsages::class);

        $this->assertSame([], $usages->of(Dictionary::VehicleMake, $unused->id));
        $this->assertSame([], $usages->of(Dictionary::Colour, $vehicle->make_id));
    }

    public function test_a_make_in_use_cannot_be_deleted_but_a_free_one_can(): void
    {
        $admin = Actor::user(UserModel::factory()->admin()->create()->id);
        $vehicle = VehicleModelFactory::new()->create();
        $free = DictionaryValueModelFactory::new()->of(Dictionary::VehicleMake)->create();

        try {
            app(CommandBus::class)->send(new DeleteDictionaryValueCommand($admin, $vehicle->make_id));
            $this->fail('A make in use was deleted');
        } catch (DictionaryValueRefused $refusal) {
            $this->assertSame('catalogue::exceptions.dictionary_value.in_use', $refusal->translationKey);
            $this->assertStringContainsString('Pojazdy (1)', $refusal->parameters['places']);
        }

        app(CommandBus::class)->send(new DeleteDictionaryValueCommand($admin, $free->id));
        $this->assertNull(DictionaryValueModel::find($free->id));
    }
}
