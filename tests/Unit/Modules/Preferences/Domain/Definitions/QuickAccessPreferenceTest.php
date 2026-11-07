<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Preferences\Domain\Definitions;

use App\Core\Domain\QuickAccess\QuickAccessGroup;
use App\Core\Domain\QuickAccess\QuickAccessItem;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use App\Modules\Preferences\Domain\Definitions\QuickAccessPreference;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;
use PHPUnit\Framework\TestCase;

class QuickAccessPreferenceTest extends TestCase
{
    private function preference(int $registered = 12): QuickAccessPreference
    {
        $items = new QuickAccessItems;
        $items->registerGroup(new QuickAccessGroup('test', 'test::group', 'heroicon-o-squares-2x2'));

        for ($i = 1; $i <= $registered; $i++) {
            $items->register(new QuickAccessItem(
                key: 'test.item_'.$i,
                group: 'test',
                label: fn (): string => 'Item',
                icon: fn (): ?string => null,
                url: fn (): string => '/',
                visible: fn (): bool => true,
            ));
        }

        return new QuickAccessPreference($items);
    }

    private function assertRefused(string $translationKey, mixed $value): void
    {
        try {
            $this->preference()->normalise($value);
            $this->fail('The value was accepted');
        } catch (PreferenceRefused $refusal) {
            $this->assertSame($translationKey, $refusal->translationKey);
        }
    }

    public function test_its_key_is_quick_access_and_its_default_is_an_empty_grid(): void
    {
        $this->assertSame('quick_access', $this->preference()->key());
        $this->assertSame([], $this->preference()->default());
    }

    public function test_it_keeps_registered_keys_in_the_given_order(): void
    {
        $this->assertSame(
            ['test.item_3', 'test.item_1', 'test.item_2'],
            $this->preference()->normalise(['test.item_3', 'test.item_1', 'test.item_2']),
        );
    }

    public function test_it_accepts_an_empty_list(): void
    {
        $this->assertSame([], $this->preference()->normalise([]));
    }

    public function test_it_accepts_ten_items(): void
    {
        $keys = array_map(fn (int $i): string => 'test.item_'.$i, range(1, 10));

        $this->assertSame($keys, $this->preference()->normalise($keys));
    }

    public function test_it_refuses_eleven_items(): void
    {
        $this->assertRefused('preferences::exceptions.quick_access.too_many', array_map(fn (int $i): string => 'test.item_'.$i, range(1, 11)));
    }

    public function test_it_refuses_a_duplicate(): void
    {
        $this->assertRefused('preferences::exceptions.quick_access.duplicate', ['test.item_1', 'test.item_1']);
    }

    public function test_it_refuses_a_key_nobody_registered(): void
    {
        $this->assertRefused('preferences::exceptions.quick_access.unknown_item', ['test.item_1', 'https://evil.example']);
    }

    public function test_it_refuses_something_that_is_not_a_list_of_keys(): void
    {
        $this->assertRefused('preferences::exceptions.quick_access.invalid', 'test.item_1');
        $this->assertRefused('preferences::exceptions.quick_access.invalid', ['a' => 'test.item_1']);
        $this->assertRefused('preferences::exceptions.quick_access.invalid', [1, 2]);
    }
}
