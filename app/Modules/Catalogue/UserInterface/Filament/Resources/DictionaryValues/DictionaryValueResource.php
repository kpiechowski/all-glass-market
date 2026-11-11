<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\DictionaryValueModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\DictionaryValueQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages\ListDictionaryValues;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Pages\ViewDictionaryValue;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Schemas\DictionaryValueInfolist;
use App\Modules\Catalogue\UserInterface\Filament\Resources\DictionaryValues\Tables\DictionaryValuesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The six dictionaries (FR-12) as one resource: a list tab per dictionary. Writes are modal actions
 * sending commands, so there are no create or edit pages. D-42: Admin and Super Admin only; a
 * Seller sees nothing of it. Listed in the "Katalog" group of `PanelNavigation`.
 */
class DictionaryValueResource extends Resource
{
    protected static ?string $model = DictionaryValueModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('catalogue::resources.dictionary_value.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalogue::resources.dictionary_value.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(DictionaryValueQueries::class)->listing();
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(CataloguePermission::Manage);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return DictionaryValueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DictionaryValuesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecordHistoryRelationManager::make(['recordType' => 'catalogue.dictionary_value']),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDictionaryValues::route('/'),
            'view' => ViewDictionaryValue::route('/{record}'),
        ];
    }
}
