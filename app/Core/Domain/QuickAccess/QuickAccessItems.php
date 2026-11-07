<?php

declare(strict_types=1);

namespace App\Core\Domain\QuickAccess;

use LogicException;

/**
 * The registry of quick-access items and their groups, fed by every module from its provider's
 * boot(), the group first:
 *
 *     $items = $this->app->make(QuickAccessItems::class);
 *     $items->registerGroup(new QuickAccessGroup('offers', 'offers::quick_access.group', Heroicon::OutlinedTag));
 *     $items->register(FilamentQuickAccessItem::for('offers.list', OfferResource::class, 'offers'));
 *
 * A singleton bound by the Core provider. Registration lives in memory: a module that is not
 * installed registers nothing, and a stored key whose module is gone is simply not found.
 */
final class QuickAccessItems
{
    /** @var array<string, QuickAccessItem> */
    private array $items = [];

    /** @var array<string, QuickAccessGroup> */
    private array $groups = [];

    public function registerGroup(QuickAccessGroup $group): void
    {
        if (isset($this->groups[$group->key])) {
            throw new LogicException("The quick-access group [{$group->key}] is already registered.");
        }

        $this->groups[$group->key] = $group;
    }

    public function group(string $key): ?QuickAccessGroup
    {
        return $this->groups[$key] ?? null;
    }

    public function register(QuickAccessItem $item): void
    {
        if (isset($this->items[$item->key])) {
            throw new LogicException("The quick-access item [{$item->key}] is already registered.");
        }

        if (! isset($this->groups[$item->group])) {
            throw new LogicException("The quick-access item [{$item->key}] names the group [{$item->group}], which is not registered.");
        }

        $this->items[$item->key] = $item;
    }

    public function has(string $key): bool
    {
        return isset($this->items[$key]);
    }

    public function find(string $key): ?QuickAccessItem
    {
        return $this->items[$key] ?? null;
    }

    /** @return list<QuickAccessItem> in registration order */
    public function all(): array
    {
        return array_values($this->items);
    }

    /** @return list<QuickAccessItem> the items the current user may open, in registration order */
    public function visible(): array
    {
        return array_values(array_filter($this->items, fn (QuickAccessItem $item): bool => $item->isVisible()));
    }

    /**
     * Sorts the given items under their groups, the groups in registration order and the items in
     * the order given. A group none of the items belongs to is left out.
     *
     * @param  list<QuickAccessItem>  $items
     * @return list<array{group: QuickAccessGroup, items: list<QuickAccessItem>}>
     */
    public function inGroups(array $items): array
    {
        $grouped = [];

        foreach ($this->groups as $key => $group) {
            $members = array_values(array_filter($items, fn (QuickAccessItem $item): bool => $item->group === $key));

            if ($members !== []) {
                $grouped[] = ['group' => $group, 'items' => $members];
            }
        }

        return $grouped;
    }
}
