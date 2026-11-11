<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelUsages;
use App\Modules\Catalogue\Domain\Contracts\DictionaryUse;
use App\Modules\Catalogue\Domain\Contracts\GlassModelUsage;
use Tests\TestCase;

class GlassModelUsagesTest extends TestCase
{
    public function test_nothing_registered_means_nothing_uses_the_model(): void
    {
        $this->assertSame([], app(GlassModelUsages::class)->of(1));
    }

    public function test_it_collects_the_uses_of_registered_usages_and_skips_empty_ones(): void
    {
        $usages = new GlassModelUsages;
        $usages->register(new class implements GlassModelUsage
        {
            public function usesOf(int $glassModelId): array
            {
                return [new DictionaryUse('catalogue::uses.a', 2), new DictionaryUse('catalogue::uses.zero', 0)];
            }
        });

        $this->assertSame(['catalogue::uses.a'], array_map(fn (DictionaryUse $u): string => $u->place, $usages->of(9)));
    }
}
