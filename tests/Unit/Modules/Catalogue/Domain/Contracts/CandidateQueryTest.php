<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Contracts;

use App\Modules\Catalogue\Domain\Contracts\CandidateQuery;
use PHPUnit\Framework\TestCase;

/** D-49: how the candidate search reads what was typed. */
class CandidateQueryTest extends TestCase
{
    public function test_text_shorter_than_the_minimum_fragment_searches_nothing(): void
    {
        $this->assertFalse((new CandidateQuery('  86 '))->isSearchable());
        $this->assertTrue((new CandidateQuery('  861 '))->isSearchable());
        $this->assertFalse((new CandidateQuery(''))->isSearchable());
    }

    public function test_the_eurocode_fragment_is_uppercase_letters_and_digits_only(): void
    {
        $this->assertSame('8613AGS', (new CandidateQuery(' 8613-ags '))->eurocodeFragment());
    }

    public function test_the_oem_fragment_ignores_spaces_dashes_and_dots(): void
    {
        $this->assertSame('1K0845011A', (new CandidateQuery('1k0 845-011.a'))->oemFragment());
    }

    public function test_a_route_whose_normalised_fragment_is_too_short_is_skipped(): void
    {
        $query = new CandidateQuery('a-4 ');

        $this->assertNull($query->eurocodeFragment());
        $this->assertNull($query->oemFragment());
        $this->assertSame(['a-4'], $query->vehicleWords());
    }

    public function test_the_vehicle_words_are_the_typed_words(): void
    {
        $this->assertSame(['Audi', 'A4'], (new CandidateQuery('  Audi   A4 '))->vehicleWords());
    }

    public function test_like_wildcards_are_not_searchable_characters(): void
    {
        $this->assertSame(['audi'], (new CandidateQuery('au%di_'))->vehicleWords());
        $this->assertNull((new CandidateQuery('%%%_'))->oemFragment());
    }

    public function test_the_default_limit_is_twenty_and_it_can_be_lifted(): void
    {
        $this->assertSame(20, (new CandidateQuery('audi'))->limit);
        $this->assertNull((new CandidateQuery('audi'))->withoutLimit()->limit);
    }
}
