<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\GlassShape;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Factories\ThumbnailMappingModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\ThumbnailMappingQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThumbnailMappingQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_listing_holds_the_mapped_values_and_not_the_unmapped_ones(): void
    {
        $mapped = DictionaryValueModelFactory::new()->create();
        DictionaryValueModelFactory::new()->create();
        ThumbnailMappingModelFactory::new()->forPosition($mapped, GlassShape::Roof)->create();

        $this->assertSame([$mapped->id], app(ThumbnailMappingQueries::class)->listing()->pluck('dictionary_value_id')->all());
    }
}
