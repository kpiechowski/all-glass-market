<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\Commands\SaveThumbnailSettings;

use App\Core\Domain\Actor;
use App\Core\Exceptions\AuthorizationException;
use App\Modules\Catalogue\Application\Commands\SaveThumbnailSettings\SaveThumbnailSettingsCommand;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Enums\Pictogram;
use App\Modules\Catalogue\Domain\Events\GlassModelChanged;
use App\Modules\Catalogue\Domain\Exceptions\ThumbnailMappingRefused;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\GlassModelModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\ThumbnailMappingModel;
use App\Modules\Users\Infrastructure\Persistence\Models\UserModel;
use Ecotone\Modelling\CommandBus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/** FR-15, FR-16, FR-17, RULE-12, D-57. */
class SaveThumbnailSettingsCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function send(Actor $actor, array $positions = [], array $colours = [], array $equipment = []): void
    {
        app(CommandBus::class)->send(new SaveThumbnailSettingsCommand($actor, $positions, $colours, $equipment));
    }

    private function admin(): Actor
    {
        return Actor::user(UserModel::factory()->admin()->create()->id);
    }

    public function test_an_admin_maps_a_position_a_colour_and_an_equipment_value(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $equipment = DictionaryValueModelFactory::new()->of(Dictionary::Equipment)->create();

        $this->send(
            $this->admin(),
            [$position->id => ['shape' => 'side_drop', 'mirrored' => true]],
            [$colour->id => ['fill' => '#1A8A3C', 'opacity' => 40]],
            [$equipment->id => 'camera'],
        );

        $mapped = ThumbnailMappingModel::query()->where('dictionary_value_id', $position->id)->sole();
        $this->assertSame(GlassShape::SideDrop, $mapped->shape);
        $this->assertTrue($mapped->mirrored);
        $tinted = ThumbnailMappingModel::query()->where('dictionary_value_id', $colour->id)->sole();
        $this->assertSame('#1a8a3c', $tinted->fill);
        $this->assertSame(40, $tinted->opacity);
        $this->assertSame(Pictogram::Camera, ThumbnailMappingModel::query()->where('dictionary_value_id', $equipment->id)->sole()->pictogram);
    }

    public function test_saving_again_changes_the_mapping_in_place(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $existing = ThumbnailMappingModelFactory::new()->forPosition($position, GlassShape::Rear)->create();

        $this->send($this->admin(), [$position->id => ['shape' => 'roof', 'mirrored' => false]]);

        $this->assertSame(1, ThumbnailMappingModel::query()->count());
        $this->assertSame(GlassShape::Roof, $existing->fresh()->shape);
    }

    public function test_an_empty_entry_removes_the_mapping(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $kept = ThumbnailMappingModelFactory::new()->forPosition(DictionaryValueModelFactory::new()->of(Dictionary::Position)->create(), GlassShape::Rear)->create();
        ThumbnailMappingModelFactory::new()->forPosition($position, GlassShape::Rear)->create();

        $this->send($this->admin(), [$position->id => ['shape' => null, 'mirrored' => false]]);

        $this->assertSame([$kept->id], ThumbnailMappingModel::query()->pluck('id')->all());
    }

    public function test_a_mapping_change_changes_no_glass_model_and_announces_nothing(): void
    {
        Event::fake([GlassModelChanged::class]);
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();
        $glassModel = GlassModelModelFactory::new()->create(['position_id' => $position->id]);
        $before = $glassModel->fresh()->updated_at;

        $this->send($this->admin(), [$position->id => ['shape' => 'roof', 'mirrored' => true]]);

        Event::assertNotDispatched(GlassModelChanged::class);
        $this->assertEquals($before, $glassModel->fresh()->updated_at);
    }

    public function test_it_refuses_a_shape_for_a_colour_value(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->expectException(ThumbnailMappingRefused::class);

        $this->send($this->admin(), [$colour->id => ['shape' => 'roof', 'mirrored' => false]]);
    }

    public function test_it_refuses_an_opacity_out_of_range(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();

        $this->expectException(ThumbnailMappingRefused::class);

        $this->send($this->admin(), [], [$colour->id => ['fill' => '#ffffff', 'opacity' => 101]]);
    }

    public function test_it_refuses_a_seller(): void
    {
        $position = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();

        $this->expectException(AuthorizationException::class);

        $this->send(Actor::user(UserModel::factory()->create()->id), [$position->id => ['shape' => 'roof', 'mirrored' => false]]);
    }
}
