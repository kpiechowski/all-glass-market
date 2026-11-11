<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Domain\Repositories\ThumbnailMappingRepository;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentThumbnailMappingRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_the_mapping_of_a_value_and_not_another(): void
    {
        $mapped = DictionaryValueModelFactory::new()->create();
        $unmapped = DictionaryValueModelFactory::new()->create();
        ThumbnailMappingModelFactory::new()->forPosition($mapped, GlassShape::Roof)->create();
        $repository = app(ThumbnailMappingRepository::class);

        $this->assertSame(GlassShape::Roof, $repository->findForValue($mapped->id)->shape());
        $this->assertNull($repository->findForValue($unmapped->id));
    }

    public function test_all_returns_every_mapping(): void
    {
        ThumbnailMappingModelFactory::new()->forPosition(DictionaryValueModelFactory::new()->create(), GlassShape::Roof)->create();
        ThumbnailMappingModelFactory::new()->forPosition(DictionaryValueModelFactory::new()->create(), GlassShape::Rear)->create();

        $this->assertCount(2, app(ThumbnailMappingRepository::class)->all());
    }
}
