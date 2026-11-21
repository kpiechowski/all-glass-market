<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use Filament\Forms\Components\Select;

/**
 * Panel-wide select defaults, registered through `Select::configureUsing()` in `ComponentDefaults`.
 * A select may still override each of them.
 *
 * - Selects use the searchable JavaScript dropdown instead of the browser's native `<select>`.
 */
final class SelectDefaults
{
    public static function apply(Select $select): void
    {
        $select->native(false);
    }
}
