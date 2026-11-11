<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\ValueObjects\Eurocode;
use PHPUnit\Framework\TestCase;

/** RULE-03. */
class EurocodeTest extends TestCase
{
    public function test_it_is_stored_uppercase_with_other_characters_removed(): void
    {
        $this->assertSame('8613AGSMV1B', Eurocode::from(' 8613 ags-mv.1b ')->value);
    }

    public function test_a_code_of_seventeen_characters_is_accepted(): void
    {
        $this->assertSame(str_repeat('A', 17), Eurocode::from(str_repeat('a', 17))->value);
    }

    public function test_a_code_with_nothing_left_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        Eurocode::from(' - . ');
    }

    public function test_a_code_over_seventeen_characters_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        Eurocode::from(str_repeat('A', 18));
    }
}
