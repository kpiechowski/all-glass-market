<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Catalogue\Application\ModuleApi;

use App\Modules\Catalogue\Application\ModuleApi\EurocodeSuggestions;
use Tests\TestCase;

/** FR-20, RULE-03. */
class EurocodeSuggestionsTest extends TestCase
{
    public function test_the_hint_is_normalised_into_a_suggested_eurocode(): void
    {
        $this->assertSame('8613AGSMV1B', app(EurocodeSuggestions::class)->from(' 8613 ags-mv.1b '));
    }

    public function test_no_hint_suggests_nothing(): void
    {
        $this->assertSame('', app(EurocodeSuggestions::class)->from(null));
        $this->assertSame('', app(EurocodeSuggestions::class)->from(' - '));
    }
}
