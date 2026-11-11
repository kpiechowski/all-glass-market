<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\GlassModelModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\GlassModelQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\CreateGlassModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\EditGlassModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ListGlassModels;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Pages\ViewGlassModel;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\RelationManagers\FittedVehiclesRelationManager;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Schemas\GlassModelForm;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Schemas\GlassModelInfolist;
use App\Modules\Catalogue\UserInterface\Filament\Resources\GlassModels\Tables\GlassModelsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Glass models (ADR 0008): list, create, edit and view; withdraw, restore and delete are actions.
 * D-42: Admin and Super Admin only. Listed in the "Katalog" group of `PanelNavigation`.
 */
class GlassModelResource extends Resource
{
    protected static ?string $model = GlassModelModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('catalogue::resources.glass_model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalogue::resources.glass_model.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return static::getPluralModelLabel();
    }

    public static function getEloquentQuery(): Builder
    {
        return app(GlassModelQueries::class)->listing();
    }

    public static function canViewAny(): bool
    {
        return CurrentActor::can(CataloguePermission::Manage);
    }

    public static function canCreate(): bool
    {
        return CurrentActor::can(CataloguePermission::Manage);
    }

    public static function canEdit(Model $record): bool
    {
        return CurrentActor::can(CataloguePermission::Manage);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return GlassModelForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GlassModelInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlassModelsTable::configure($table);
    }

    /** FR-10: the fitment tab. */
    public static function getRelations(): array
    {
        return [
            FittedVehiclesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlassModels::route('/'),
            'create' => CreateGlassModel::route('/create'),
            'view' => ViewGlassModel::route('/{record}'),
            'edit' => EditGlassModel::route('/{record}/edit'),
        ];
    }
}
