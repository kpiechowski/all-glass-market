<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\DictionaryValueUsages;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\DictionaryValueUsage;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use Tests\TestCase;

class DictionaryValueUsagesTest extends TestCase
{
    public function test_nothing_registered_means_nothing_uses_the_value(): void
    {
        $this->assertSame([], app(DictionaryValueUsages::class)->of(Dictionary::Colour, 1));
    }

    public function test_it_collects_the_uses_of_every_registered_usage_and_skips_empty_ones(): void
    {
        $usages = new DictionaryValueUsages;
        $usages->register(new class implements DictionaryValueUsage
        {
            public function usesOf(Dictionary $dictionary, int $valueId): array
            {
                return [new DictionaryUse('catalogue::uses.a', 2), new DictionaryUse('catalogue::uses.zero', 0)];
            }
        });
        $usages->register(new class implements DictionaryValueUsage
        {
            public function usesOf(Dictionary $dictionary, int $valueId): array
            {
                return [new DictionaryUse('catalogue::uses.b', 1)];
            }
        });

        $uses = $usages->of(Dictionary::Producer, 5);

        $this->assertSame(['catalogue::uses.a', 'catalogue::uses.b'], array_map(fn (DictionaryUse $u): string => $u->place, $uses));
    }
}
