<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\GlassModelCardLink;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelLinkProvider;
use App\Modules\Catalogue\Application\ModuleApi\GlassModelLinks;
use Tests\TestCase;

class GlassModelLinksTest extends TestCase
{
    public function test_a_page_of_cards_is_read_in_one_call_and_models_without_links_are_left_out(): void
    {
        $provider = new class implements GlassModelLinkProvider
        {
            public int $reads = 0;

            public function linksFor(array $glassModelIds): array
            {
                $this->reads++;

                return [2 => new GlassModelCardLink('2 ofert', '/two')];
            }
        };
        $links = new GlassModelLinks;
        $links->register($provider);

        $result = $links->ofMany([1, 2, 3]);

        $this->assertSame([2], array_keys($result));
        $this->assertSame('/two', $result[2][0]->url);
        $this->assertSame(1, $provider->reads);
    }
}
