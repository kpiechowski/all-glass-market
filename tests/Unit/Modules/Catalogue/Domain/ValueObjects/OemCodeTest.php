<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\GlassModelRefused;
use App\Modules\Catalogue\Domain\ValueObjects\OemCode;
use PHPUnit\Framework\TestCase;

/** RULE-04. */
class OemCodeTest extends TestCase
{
    public function test_it_is_kept_as_typed_uppercase_and_trimmed(): void
    {
        $this->assertSame('1K0-845 011.A', OemCode::tryFrom('  1k0-845 011.a ')->value);
    }

    public function test_the_key_ignores_spaces_dashes_and_dots(): void
    {
        $this->assertSame(OemCode::tryFrom('1K0 845-011.A')->key(), OemCode::tryFrom('1k0845011a')->key());
    }

    public function test_an_empty_entry_is_no_code(): void
    {
        $this->assertNull(OemCode::tryFrom('   '));
    }

    public function test_an_over_long_code_is_refused(): void
    {
        $this->expectException(GlassModelRefused::class);

        OemCode::tryFrom(str_repeat('A', 256));
    }
}
