<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Tables;

use App\Core\UserInterface\Filament\Tables\Columns\RecordCardColumn;
use App\Core\UserInterface\Filament\Tables\TableEmptyState;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\CreateDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\DeleteDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\RenameDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\RestoreDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Actions\WithdrawDictionaryValueAction;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\DictionaryValueResource;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Schemas\DictionaryValueInfolist;
use Filament\Actions\ActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

/** Template A (list). The dictionary split is the list tabs of `ListDictionaryValues`. */
class DictionaryValuesTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns(self::columns())
            ->filters(DictionaryValuesTableFilters::make(), layout: FiltersLayout::AboveContent)
            ->recordUrl(fn (DictionaryValueModel $record): string => DictionaryValueResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ActionGroup::make([
                    RenameDictionaryValueAction::make(),
                    WithdrawDictionaryValueAction::make(),
                    RestoreDictionaryValueAction::make(),
                    DeleteDictionaryValueAction::make(),
                ]),
            ])
            ->defaultSort('name');

        return TableEmptyState::configure(
            $table,
            icon: Heroicon::OutlinedBookOpen,
            heading: __('catalogue::resources.dictionary_value.empty.heading'),
            description: __('catalogue::resources.dictionary_value.empty.description'),
            actions: [CreateDictionaryValueAction::make()],
        );
    }

    /** @return array<mixed> */
    private static function columns(): array
    {
        return [
            RecordCardColumn::make()
                ->title(fn (DictionaryValueModel $record): string => (string) $record->name)
                ->status(fn (DictionaryValueModel $record): array => self::state($record)),

            TextColumn::make('name')
                ->label(__('catalogue::resources.dictionary_value.fields.name'))
                ->searchable()
                ->sortable()
                ->visibleFrom('md'),

            TextColumn::make('withdrawn')
                ->label(__('catalogue::resources.dictionary_value.fields.state'))
                ->badge()
                ->formatStateUsing(fn (bool $state): string => DictionaryValueInfolist::stateLabel($state))
                ->color(fn (bool $state): string => $state ? 'gray' : 'success')
                ->visibleFrom('md'),
        ];
    }

    /** @return array{label: string, color: string} */
    private static function state(DictionaryValueModel $record): array
    {
        return [
            'label' => DictionaryValueInfolist::stateLabel($record->withdrawn),
            'color' => $record->withdrawn ? 'gray' : 'success',
        ];
    }
}
