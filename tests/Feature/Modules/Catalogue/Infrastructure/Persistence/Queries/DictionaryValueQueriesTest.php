<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Infrastructure\Persistence\Queries;

use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DictionaryValueQueriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_of_dictionary_returns_only_that_dictionary(): void
    {
        $colour = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $producer = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->create();

        $ids = app(DictionaryValueQueries::class)->ofDictionary(Dictionary::Colour)->pluck('id');

        $this->assertTrue($ids->contains($colour->id));
        $this->assertFalse($ids->contains($producer->id));
    }

    public function test_only_withdrawn_and_only_active_split_the_values(): void
    {
        $active = DictionaryValueModelFactory::new()->create();
        $withdrawn = DictionaryValueModelFactory::new()->withdrawn()->create();
        $queries = app(DictionaryValueQueries::class);

        $this->assertSame([$withdrawn->id], $queries->onlyWithdrawn($queries->listing())->pluck('id')->all());
        $this->assertSame([$active->id], $queries->onlyActive($queries->listing())->pluck('id')->all());
    }
}
