<?php

declare(strict_types=1);

namespace App\Filament\Configuration;

use App\Filament\Tables\TableDefaults;
use Filament\Tables\Table;

/**
 * D-45: panel-wide Filament component defaults (`configureUsing`). Called once from
 * `AdminPanelProvider::boot()`; never registered in CoreServiceProvider or a module provider.
 * A new component default gets its own `{Component}Defaults` class and one line here.
 */
final class ComponentDefaults
{
    public static function apply(): void
    {
        Table::configureUsing(TableDefaults::apply(...));
    }
}
