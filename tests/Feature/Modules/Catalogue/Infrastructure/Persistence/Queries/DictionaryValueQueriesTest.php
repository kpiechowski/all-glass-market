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

    public function test_choosable_offers_active_values_and_the_withdrawn_ones_already_held_only(): void
    {
        $active = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->create();
        $held = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->withdrawn()->create();
        $otherDictionary = DictionaryValueModelFactory::new()->of(Dictionary::Position)->create();

        $ids = app(DictionaryValueQueries::class)->choosable(Dictionary::Colour, [$held->id])->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$active->id, $held->id], $ids);
        $this->assertNotContains($withdrawn->id, $ids);
        $this->assertNotContains($otherDictionary->id, $ids);
    }
}
