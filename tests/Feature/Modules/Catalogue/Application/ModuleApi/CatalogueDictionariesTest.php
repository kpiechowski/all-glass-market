<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\CatalogueDictionaries;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Infrastructure\Factories\DictionaryValueModelFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** FR-23, D-50. */
class CatalogueDictionariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_offer_only_active_values_of_their_dictionary_by_name(): void
    {
        DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Pilkington')->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('AGC')->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Stara')->withdrawn()->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Position)->named('Przód')->create();
        DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielona')->create();
        $api = app(CatalogueDictionaries::class);

        $this->assertSame(['AGC', 'Pilkington'], array_map(fn ($entry) => $entry->name, $api->producers()));
        $this->assertSame(['Przód'], array_map(fn ($entry) => $entry->name, $api->positions()));
        $this->assertSame(['Zielona'], array_map(fn ($entry) => $entry->name, $api->colours()));
    }

    public function test_a_withdrawn_value_keeps_its_name_readable_by_id(): void
    {
        $withdrawn = DictionaryValueModelFactory::new()->of(Dictionary::Producer)->named('Stara')->withdrawn()->create();
        $active = DictionaryValueModelFactory::new()->of(Dictionary::Colour)->named('Zielona')->create();

        $names = app(CatalogueDictionaries::class)->namesOf([$withdrawn->id, $active->id, 999]);

        $this->assertSame([$withdrawn->id => 'Stara', $active->id => 'Zielona'], $names);
    }
}
