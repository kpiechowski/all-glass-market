<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Domain\Definitions;

use App\Core\Contracts\PreferenceDefinition;
use App\Core\Domain\QuickAccess\QuickAccessItems;
use App\Modules\Preferences\Domain\Exceptions\PreferenceRefused;

/**
 * The dashboard's "Szybki dostęp" tiles (issue 13): an ordered list of quick-access item keys,
 * at most ten, each registered and none twice. The default is the empty grid. Whether the user
 * may open an item is not checked here: that depends on who is looking, so the widget skips an
 * inaccessible tile when it draws the grid.
 */
final readonly class QuickAccessPreference implements PreferenceDefinition
{
    public const string KEY = 'quick_access';

    public const int MAX_ITEMS = 10;

    public function __construct(private QuickAccessItems $items) {}

    public function key(): string
    {
        return self::KEY;
    }

    /** @return list<string> */
    public function default(): array
    {
        return [];
    }

    /** @return list<string> */
    public function normalise(mixed $value): array
    {
        if (! is_array($value) || ! array_is_list($value)) {
            throw PreferenceRefused::quickAccessInvalid();
        }

        foreach ($value as $key) {
            if (! is_string($key)) {
                throw PreferenceRefused::quickAccessInvalid();
            }
        }

        if (count($value) > self::MAX_ITEMS) {
            throw PreferenceRefused::quickAccessTooMany(self::MAX_ITEMS);
        }

        if (count(array_unique($value)) !== count($value)) {
            throw PreferenceRefused::quickAccessDuplicate();
        }

        foreach ($value as $key) {
            if (! $this->items->has($key)) {
                throw PreferenceRefused::quickAccessUnknownItem();
            }
        }

        return $value;
    }
}
