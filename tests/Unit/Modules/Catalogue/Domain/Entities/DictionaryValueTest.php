<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalogue\Domain\Entities;

use App\Modules\Catalogue\Domain\Entities\DictionaryValue;
use App\Modules\Catalogue\Domain\Enums\Dictionary;
use App\Modules\Catalogue\Domain\Exceptions\DictionaryValueRefused;
use App\Modules\Catalogue\Domain\ValueObjects\DictionaryValueName;
use PHPUnit\Framework\TestCase;

class DictionaryValueTest extends TestCase
{
    private function value(bool $withdrawn = false): DictionaryValue
    {
        return DictionaryValue::reconstitute(1, Dictionary::Colour, DictionaryValueName::from('Zielony'), $withdrawn);
    }

    public function test_a_new_value_is_active(): void
    {
        $value = DictionaryValue::create(Dictionary::Producer, DictionaryValueName::from('Pilkington'), nameIsTaken: false);

        $this->assertNull($value->id());
        $this->assertFalse($value->isWithdrawn());
        $this->assertSame(Dictionary::Producer, $value->dictionary());
    }

    public function test_a_taken_name_is_refused_on_create_and_rename(): void
    {
        try {
            DictionaryValue::create(Dictionary::Producer, DictionaryValueName::from('X'), nameIsTaken: true);
            $this->fail('A taken name was accepted');
        } catch (DictionaryValueRefused) {
        }

        $this->expectException(DictionaryValueRefused::class);

        $this->value()->rename(DictionaryValueName::from('Czerwony'), nameIsTaken: true);
    }

    public function test_rename_reports_whether_the_name_changed(): void
    {
        $value = $this->value();

        $this->assertFalse($value->rename(DictionaryValueName::from('Zielony'), false));
        $this->assertTrue($value->rename(DictionaryValueName::from('ZIELONY'), false));
        $this->assertSame('ZIELONY', $value->name()->value);
    }

    public function test_it_is_withdrawn_and_restored_but_never_twice(): void
    {
        $value = $this->value();

        $value->withdraw();
        $this->assertTrue($value->isWithdrawn());

        try {
            $value->withdraw();
            $this->fail('Withdrawn twice');
        } catch (DictionaryValueRefused) {
        }

        $value->restore();
        $this->assertFalse($value->isWithdrawn());

        $this->expectException(DictionaryValueRefused::class);

        $value->restore();
    }

    public function test_a_value_nothing_uses_may_be_deleted(): void
    {
        $this->value()->ensureCanBeDeleted([]);

        $this->addToAssertionCount(1);
    }
}
