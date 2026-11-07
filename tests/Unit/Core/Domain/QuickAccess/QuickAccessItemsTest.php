<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Domain\QuickAccess;

use App\Core\Domain\QuickAccess\QuickAccessGroup;
use App\Core\Domain\QuickAccess\QuickAccessItem;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

class QuickAccessItemsTest extends TestCase
{
    private function registry(string ...$groups): QuickAccessItems
    {
        $items = new QuickAccessItems;

        foreach ($groups === [] ? ['users', 'audit', 'a', 'b'] : $groups as $group) {
            $items->registerGroup(new QuickAccessGroup($group, $group.'::quick_access.group', 'heroicon-o-'.$group));
        }

        return $items;
    }

    private function item(string $key, bool $visible = true, ?string $group = null): QuickAccessItem
    {
        return new QuickAccessItem(
            key: $key,
            group: $group ?? explode('.', $key)[0],
            label: fn (): string => 'Label '.$key,
            icon: fn (): string => 'heroicon-o-users',
            url: fn (): string => '/admin/'.$key,
            visible: fn (): bool => $visible,
        );
    }

    public function test_it_finds_a_registered_item_by_its_key(): void
    {
        $items = $this->registry();
        $items->register($this->item('users.list'));

        $this->assertTrue($items->has('users.list'));
        $this->assertSame('Label users.list', $items->find('users.list')?->label());
        $this->assertSame('/admin/users.list', $items->find('users.list')?->url());
    }

    public function test_an_unknown_key_is_not_found(): void
    {
        $items = $this->registry();
        $items->register($this->item('users.list'));

        $this->assertFalse($items->has('audit.log'));
        $this->assertNull($items->find('audit.log'));
    }

    public function test_all_keeps_the_registration_order(): void
    {
        $items = $this->registry();
        $items->register($this->item('b.second'));
        $items->register($this->item('a.first'));

        $this->assertSame(['b.second', 'a.first'], array_map(fn (QuickAccessItem $item): string => $item->key, $items->all()));
    }

    public function test_visible_leaves_out_items_the_user_may_not_open(): void
    {
        $items = $this->registry();
        $items->register($this->item('users.list'));
        $items->register($this->item('audit.log', visible: false));

        $this->assertSame(['users.list'], array_map(fn (QuickAccessItem $item): string => $item->key, $items->visible()));
    }

    public function test_a_key_cannot_be_registered_twice(): void
    {
        $items = $this->registry();
        $items->register($this->item('users.list'));

        $this->expectException(LogicException::class);

        $items->register($this->item('users.list'));
    }

    public function test_a_key_must_be_a_lowercase_dotted_name(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->item('Users List');
    }

    public function test_an_item_is_not_suggested_unless_flagged(): void
    {
        $this->assertFalse($this->item('users.list')->suggested);
    }

    public function test_a_group_carries_its_key_and_icon(): void
    {
        $items = $this->registry('users');

        $this->assertSame('heroicon-o-users', $items->group('users')?->icon);
        $this->assertSame('users::quick_access.group', $items->group('users')?->labelKey);
        $this->assertNull($items->group('offers'));
    }

    public function test_a_group_cannot_be_registered_twice(): void
    {
        $items = $this->registry('users');

        $this->expectException(LogicException::class);

        $items->registerGroup(new QuickAccessGroup('users', 'users::other', 'heroicon-o-users'));
    }

    public function test_a_group_key_must_be_lowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new QuickAccessGroup('Users list', 'users::quick_access.group', 'heroicon-o-users');
    }

    public function test_an_item_needs_its_group_registered_first(): void
    {
        $items = $this->registry('users');

        $this->expectException(LogicException::class);

        $items->register($this->item('offers.list'));
    }

    public function test_in_groups_follows_the_group_order_and_leaves_out_empty_groups(): void
    {
        $items = $this->registry('users', 'audit', 'offers');
        $items->register($this->item('audit.log'));
        $items->register($this->item('users.list'));
        $items->register($this->item('users.profile'));

        $grouped = array_map(
            fn (array $entry): array => [$entry['group']->key, array_map(fn (QuickAccessItem $item): string => $item->key, $entry['items'])],
            $items->inGroups([$items->find('audit.log'), $items->find('users.profile'), $items->find('users.list')]),
        );

        $this->assertSame([['users', ['users.profile', 'users.list']], ['audit', ['audit.log']]], $grouped);
    }
}
