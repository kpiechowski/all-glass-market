<?php

declare(strict_types=1);

namespace App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles;

use App\Core\UserInterface\CurrentActor;
use App\Modules\Audit\UserInterface\Filament\RelationManagers\RecordHistoryRelationManager;
use App\Modules\Catalogue\Domain\Enums\CataloguePermission;
use App\Modules\Catalogue\Infrastructure\Persistence\Models\VehicleModel;
use App\Modules\Catalogue\Infrastructure\Persistence\Queries\VehicleQueries;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\EditVehicle;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\ListVehicles;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Pages\ViewVehicle;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\RelationManagers\FittedGlassModelsRelationManager;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Schemas\VehicleForm;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Schemas\VehicleInfolist;
use App\Modules\Catalogue\UserInterface\Filament\Resources\Vehicles\Tables\VehiclesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Vehicles (FR-07 to FR-09, FR-11). Writes are commands from the create and edit pages and the
 * delete action. Deleting is not hidden by a Filament check: the action sends the command.
 * D-42: Admin and Super Admin only. Listed in the "Katalog" group of `PanelNavigation`.
 */
class VehicleResource extends Resource
{
    protected static ?string $model = VehicleModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('catalogue::resources.vehicle.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalogue::resources.vehicle.plural_label');
    }

    public static function getEloquentQuery(): Builder
    {
        return app(VehicleQueries::class)->listing();
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

    /** The delete action sends the command itself; Filament's own delete is not used. */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VehicleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiclesTable::configure($table);
    }

    /** The fitted Glass models (FR-09) and the audit history tabs. */
    public static function getRelations(): array
    {
        return [
            FittedGlassModelsRelationManager::class,
            RecordHistoryRelationManager::make(['recordType' => 'catalogue.vehicle']),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'view' => ViewVehicle::route('/{record}'),
            'edit' => EditVehicle::route('/{record}/edit'),
        ];
    }
}
