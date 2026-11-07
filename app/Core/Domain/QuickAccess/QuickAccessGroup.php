<?php

declare(strict_types=1);

namespace App\Core\Domain\QuickAccess;

use BackedEnum;
use InvalidArgumentException;

/**
 * A heading quick-access items are shown under in the dashboard palette (issue 13). A module registers its group in `QuickAccessItems` before its items,
 * usually one group per module:
 *
 *     $items->registerGroup(new QuickAccessGroup('users', 'users::quick_access.group', Heroicon::OutlinedUsers));
 */
final readonly class QuickAccessGroup
{
    /**
     * @param  string  $key  referenced by the items' `group`, lowercase: `users`, `audit`
     * @param  string  $labelKey  translation key of the group label
     * @param  string|BackedEnum  $icon  a Heroicon; shown before the label
     */
    public function __construct(
        public string $key,
        public string $labelKey,
        public string|BackedEnum $icon,
    ) {
        if (preg_match('/^[a-z0-9_]+$/', $key) !== 1) {
            throw new InvalidArgumentException("The quick-access group key [{$key}] must be one lowercase word.");
        }
    }

    public function label(): string
    {
        return (string) __($this->labelKey);
    }
}
