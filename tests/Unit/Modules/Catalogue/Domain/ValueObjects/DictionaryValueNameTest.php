<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\ValueObjects;

use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use PHPUnit\Framework\TestCase;

/** RULE-08. */
class DictionaryValueNameTest extends TestCase
{
    public function test_it_trims_and_keeps_any_characters(): void
    {
        $this->assertSame('TYLNA_OPUSZCZANA LEWA', DictionaryValueName::from('  TYLNA_OPUSZCZANA LEWA ')->value);
    }

    public function test_two_names_compare_by_the_same_key_ignoring_case(): void
    {
        $this->assertSame(DictionaryValueName::from('Zielony')->key(), DictionaryValueName::from('ZIELONY')->key());
        $this->assertSame(DictionaryValueName::from('Żółty')->key(), DictionaryValueName::from('ŻÓŁTY')->key());
    }

    public function test_it_rejects_a_blank_name(): void
    {
        $this->expectException(DictionaryValueRefused::class);

        DictionaryValueName::from('   ');
    }

    public function test_it_rejects_a_name_over_255_characters(): void
    {
        $this->expectException(DictionaryValueRefused::class);

        DictionaryValueName::from(str_repeat('a', 256));
    }
}
