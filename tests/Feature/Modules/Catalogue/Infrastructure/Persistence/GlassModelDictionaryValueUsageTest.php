<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Core\Domain\Actor;
use App\Modules\Catalogue\Application\Commands\DeleteDictionaryValue\DeleteDictionaryValueCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\GlassModelDictionaryValueUsage;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-24: Glass models register their use of position, colour and equipment with ticket 01's seam. */
class GlassModelDictionaryValueUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_the_models_using_each_kind_of_value(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();
        $unused = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        GlassModelModelFactory::new()->withColour($colour)->withEquipment($equipment)->count(2)->create();
        $usage = new GlassModelDictionaryValueUsage;

        $this->assertSame(2, $usage->usesOf(Dictionary::Colour, $colour->id)[0]->count);
        $this->assertSame(2, $usage->usesOf(Dictionary::Equipment, $equipment->id)[0]->count);
        $this->assertSame([], $usage->usesOf(Dictionary::Colour, $unused->id));
        $this->assertSame([], $usage->usesOf(Dictionary::Producer, $colour->id));
    }

    public function test_a_value_on_a_glass_model_cannot_be_deleted(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        GlassModelModelFactory::new()->withPosition($position)->create();

        $this->expectException(DictionaryValueRefused::class);

        app(CommandBus::class)->send(new DeleteDictionaryValueCommand(
            Actor::user(UserModel::factory()->admin()->create()->id),
            $position->id,
        ));
    }
}
