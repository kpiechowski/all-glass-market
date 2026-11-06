<?php

declare(strict_types=1);

namespace App\Core\UserInterface\Filament\Pages\Styleguide\Tables;

use App\Core\UserInterface\Filament\Pages\Styleguide;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Indicator;
use Illuminate\Support\Carbon;

/**
 * Template A filters. Inline above the table: the three simple, frequent ones. The panel
 * demo ("Filtry w panelu") adds two more, as a list with more than three filters would.
 * DemoUsers applies them.
 */
final class StyleguideUsersTableFilters
{
    /** @return list<BaseFilter> */
    public static function make(): array
    {
        return [
            SelectFilter::make('account_type')
                ->label('Typ konta')
                ->options(DemoUsers::ACCOUNT_TYPES),
            SelectFilter::make('role')
                ->label('Rola')
                ->options(DemoUsers::ROLES),
            Filter::make('must_change_password')
                ->label('Wymaga zmiany hasła')
                ->toggle(),
            Filter::make('joined_from')
                ->schema([
                    DatePicker::make('joined_from')->label('Dołączył od')->native(false)->displayFormat('d.m.Y'),
                ])
                ->indicateUsing(fn (array $data): ?Indicator => filled($data['joined_from'] ?? null)
                    ? Indicator::make('Dołączył od '.Carbon::parse($data['joined_from'])->format('d.m.Y'))->removeField('joined_from')
                    : null)
                ->visible(fn (Styleguide $livewire): bool => $livewire->showsFilterPanel),
            TernaryFilter::make('has_orders')
                ->label('Zamówienia')
                ->placeholder('Wszyscy')
                ->trueLabel('Z zamówieniami')
                ->falseLabel('Bez zamówień')
                ->visible(fn (Styleguide $livewire): bool => $livewire->showsFilterPanel),
        ];
    }
}
