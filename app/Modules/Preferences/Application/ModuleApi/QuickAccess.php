<?php

declare(strict_types=1);

namespace App\Modules\Preferences\Application\ModuleApi;

use App\Modules\Preferences\Domain\Definitions\QuickAccessPreference;

/**
 * The quick-access tiles a user pinned, as stored keys (issue 13). Readers resolve each key in
 * `Core\Domain\QuickAccess\QuickAccessItems` and skip what is unknown or not visible to the user.
 * To change the tiles, send `SavePreferenceCommand` with `QuickAccess::PREFERENCE`, or call
 * `UserPreferences::save()` from another module.
 */
final class QuickAccess
{
    public const string PREFERENCE = QuickAccessPreference::KEY;

    public const int MAX_ITEMS = QuickAccessPreference::MAX_ITEMS;

    public function __construct(private UserPreferences $preferences) {}

    /** @return list<string> in the user's order; may hold keys no longer registered */
    public function keysFor(int $userId): array
    {
        $keys = $this->preferences->get($userId, self::PREFERENCE);

        return is_array($keys) ? array_values(array_filter($keys, is_string(...))) : [];
    }
}
